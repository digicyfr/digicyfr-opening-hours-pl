<?php
/**
 * One-location opening-hours settings and rendering.
 *
 * @package Digicyfr_Opening_Hours_PL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Isolated plugin implementation. */
final class Digicyfr_Opening_Hours_PL {
	private const OPTION = 'digicyfr_opening_hours_pl_schedule';
	private const DOMAIN = 'digicyfr-opening-hours-pl';

	/** Whether the latest sanitize attempt must be vetoed by update_option.
	 *
	 * @var bool
	 */
	private static bool $invalid_submission = false;

	/** Register WordPress hooks. */
	public static function boot(): void {
		add_action( 'init', array( __CLASS__, 'on_init' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_style' ) );
	}

	/** Load bundled Polish strings and register public display surfaces. */
	public static function on_init(): void {
		load_plugin_textdomain( self::DOMAIN, false, dirname( plugin_basename( DIGICYFR_OPENING_HOURS_PL_PATH . 'digicyfr-opening-hours-pl.php' ) ) . '/languages' );
		add_shortcode( 'digicyfr_opening_hours', array( __CLASS__, 'shortcode' ) );

		wp_register_script(
			'digicyfr-opening-hours-pl-editor',
			DIGICYFR_OPENING_HOURS_PL_URL . 'blocks/opening-hours/editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-components' ),
			DIGICYFR_OPENING_HOURS_PL_VERSION,
			true
		);
		wp_localize_script(
			'digicyfr-opening-hours-pl-editor',
			'digicyfrOpeningHoursLabels',
			array(
				'title'       => __( 'Opening Hours PL', 'digicyfr-opening-hours-pl' ),
				'description' => __( 'Show the next seven days of owner-entered opening hours.', 'digicyfr-opening-hours-pl' ),
				'preview'     => __( 'The saved schedule appears on the published page. Preview it under Settings → Opening Hours PL.', 'digicyfr-opening-hours-pl' ),
			)
		);
		wp_register_style(
			'digicyfr-opening-hours-pl-style',
			DIGICYFR_OPENING_HOURS_PL_URL . 'assets/opening-hours.css',
			array(),
			DIGICYFR_OPENING_HOURS_PL_VERSION
		);
		register_block_type(
			DIGICYFR_OPENING_HOURS_PL_PATH . 'blocks/opening-hours',
			array( 'render_callback' => array( __CLASS__, 'render_block' ) )
		);
	}

	/** Register one private per-site option with full validation. */
	public static function register_settings(): void {
		register_setting(
			'digicyfr_opening_hours_pl',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
		add_filter( 'pre_update_option_' . self::OPTION, array( __CLASS__, 'veto_invalid_update' ), 10, 2 );
	}

	/**
	 * Keep an invalid first submission truly absent, and preserve an existing valid option.
	 *
	 * @param mixed $value New sanitized value.
	 * @param mixed $old_value Existing option value.
	 * @return mixed Value for WordPress to compare and save.
	 */
	public static function veto_invalid_update( $value, $old_value ) {
		if ( self::$invalid_submission ) {
			self::$invalid_submission = false;
			return $old_value;
		}
		return $value;
	}

	/**
	 * Validate and normalize a complete settings submission.
	 *
	 * WordPress options.php already unslashes submitted values. This callback also accepts
	 * its own canonical output because WordPress sanitizes again when adding a new option.
	 *
	 * @param mixed $input Submitted or canonical option value.
	 * @return array Normalized schedule, or the previous value on failure.
	 * @throws InvalidArgumentException On invalid input.
	 */
	public static function sanitize_settings( $input ): array {
		self::$invalid_submission = false;
		$old                      = get_option( self::OPTION, false );
		try {
			if ( ! is_array( $input ) ) {
				throw new InvalidArgumentException( esc_html__( 'The schedule submission is invalid.', 'digicyfr-opening-hours-pl' ) );
			}
			if ( ! array_key_exists( 'weekly', $input ) ) {
				throw new InvalidArgumentException( esc_html__( 'Weekly hours are missing.', 'digicyfr-opening-hours-pl' ) );
			}
			$weekly     = self::parse_weekly( $input['weekly'] );
			$exceptions = array_key_exists( 'exceptions_text', $input )
				? self::parse_exception_lines( $input['exceptions_text'] )
				: self::parse_exception_rows( $input['exceptions'] ?? array() );
			$notice     = self::parse_notice( $input['notice'] ?? array() );
			return array(
				'schema_version' => 1,
				'weekly'         => $weekly,
				'exceptions'     => $exceptions,
				'notice'         => $notice,
			);
		} catch ( InvalidArgumentException $error ) {
			self::$invalid_submission = true;
			add_settings_error( self::OPTION, 'invalid_schedule', $error->getMessage(), 'error' );
			return is_array( $old ) ? $old : array();
		}
	}

	/**
	 * Normalize all seven weekdays, including explicitly closed days.
	 *
	 * @param mixed $input Submitted weekly rows.
	 * @return array Canonical weekly hours.
	 * @throws InvalidArgumentException On invalid input.
	 */
	private static function parse_weekly( $input ): array {
		if ( ! is_array( $input ) ) {
			throw new InvalidArgumentException( esc_html__( 'Weekly hours must be an array.', 'digicyfr-opening-hours-pl' ) );
		}
		$weekly = array();
		for ( $day = 1; $day <= 7; $day++ ) {
			$rows = $input[ (string) $day ] ?? array();
			if ( ! is_array( $rows ) ) {
				/* translators: %d is a weekday, exception row or line number. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Invalid hours for weekday %d.', 'digicyfr-opening-hours-pl' ), absint( $day ) ) );
			}
			/* translators: %d is a weekday, exception row or line number. */
			$weekly[ (string) $day ] = self::parse_intervals( $rows, self::weekday_names()[ $day ] );
		}
		return $weekly;
	}

	/**
	 * Parse up to four strict, non-overlapping wall-clock intervals.
	 *
	 * @param array  $rows Raw interval pairs.
	 * @param string $context Safe context for error messages.
	 * @return array Canonical pairs.
	 * @throws InvalidArgumentException On invalid input.
	 */
	private static function parse_intervals( array $rows, string $context ): array {
		if ( count( $rows ) > 4 ) {
			/* translators: %s is a weekday or exception row name. */
			throw new InvalidArgumentException( sprintf( esc_html__( 'Use at most four intervals in %s.', 'digicyfr-opening-hours-pl' ), esc_html( $context ) ) );
		}
		$intervals = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				/* translators: %s is a weekday or exception row name. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Invalid interval in %s.', 'digicyfr-opening-hours-pl' ), esc_html( $context ) ) );
			}
			$start = $row['start'] ?? $row[0] ?? '';
			$end   = $row['end'] ?? $row[1] ?? '';
			if ( ! is_string( $start ) || ! is_string( $end ) ) {
				/* translators: %s is a weekday or exception row name. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Invalid interval in %s.', 'digicyfr-opening-hours-pl' ), esc_html( $context ) ) );
			}
			$start = trim( $start );
			$end   = trim( $end );
			if ( '' === $start && '' === $end ) {
				continue;
			}
			if ( ! self::valid_time( $start, false ) || ! self::valid_time( $end, true ) || $start >= $end ) {
				/* translators: %s is a weekday or exception row name. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Invalid interval in %s. Use HH:MM, with start before end. Split overnight hours across two dates.', 'digicyfr-opening-hours-pl' ), esc_html( $context ) ) );
			}
			$intervals[] = array( $start, $end );
			if ( count( $intervals ) > 4 ) {
				/* translators: %s is a weekday or exception row name. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Use at most four intervals in %s.', 'digicyfr-opening-hours-pl' ), esc_html( $context ) ) );
			}
		}
		usort( $intervals, static fn( array $a, array $b ): int => strcmp( $a[0], $b[0] ) );
		$previous_end = '';
		foreach ( $intervals as $interval ) {
			if ( '' !== $previous_end && $interval[0] < $previous_end ) {
				/* translators: %s is a weekday or exception row name. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Intervals overlap in %s.', 'digicyfr-opening-hours-pl' ), esc_html( $context ) ) );
			}
			$previous_end = $interval[1];
		}
		return $intervals;
	}

	/**
	 * Check strict 24-hour text. 24:00 is only an end boundary.
	 *
	 * @param string $time Entered time.
	 * @param bool   $end Whether this is an end boundary.
	 * @return bool Whether the time is valid.
	 */
	private static function valid_time( string $time, bool $end ): bool {
		return (bool) preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $time ) || ( $end && '24:00' === $time );
	}

	/**
	 * Parse keyboard-friendly exception lines from the settings form.
	 *
	 * @param mixed $text Submitted textarea value.
	 * @return array Canonical exceptions.
	 * @throws InvalidArgumentException On invalid input.
	 */
	private static function parse_exception_lines( $text ): array {
		if ( ! is_string( $text ) ) {
			throw new InvalidArgumentException( esc_html__( 'Exceptions must be plain text lines.', 'digicyfr-opening-hours-pl' ) );
		}
		if ( strlen( $text ) > 100000 ) {
			throw new InvalidArgumentException( esc_html__( 'Exceptions text is too long.', 'digicyfr-opening-hours-pl' ) );
		}
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$rows  = array();
		foreach ( $lines as $index => $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line, 3 ) );
			if ( count( $parts ) < 2 ) {
				/* translators: %d is a weekday, exception row or line number. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Exception line %d needs a date, hours or closed, and an optional label.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
			}
			$date  = $parts[0];
			$spec  = $parts[1];
			$label = $parts[2] ?? '';
			if ( 'closed' === strtolower( $spec ) ) {
				$rows[] = array(
					'date'      => $date,
					'type'      => 'closed',
					'intervals' => array(),
					'label'     => $label,
				);
				continue;
			}
			$intervals = array();
			foreach ( explode( ',', $spec ) as $part ) {
				$part = trim( $part );
				if ( ! preg_match( '/^([0-9]{2}:[0-9]{2})-([0-9]{2}:[0-9]{2})$/D', $part, $matches ) ) {
					/* translators: %d is a weekday, exception row or line number. */
					throw new InvalidArgumentException( sprintf( esc_html__( 'Invalid hours on exception line %d.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
				}
				$intervals[] = array( $matches[1], $matches[2] );
			}
			$rows[] = array(
				'date'      => $date,
				'type'      => 'hours',
				'intervals' => $intervals,
				'label'     => $label,
			);
		}
		return self::parse_exception_rows( $rows );
	}

	/**
	 * Validate canonical exception rows, also making first-save sanitization idempotent.
	 *
	 * @param mixed $rows Submitted rows.
	 * @return array Canonical exceptions sorted by date.
	 * @throws InvalidArgumentException On invalid input.
	 */
	private static function parse_exception_rows( $rows ): array {
		if ( ! is_array( $rows ) || count( $rows ) > 366 ) {
			throw new InvalidArgumentException( esc_html__( 'Use no more than 366 dated exceptions.', 'digicyfr-opening-hours-pl' ) );
		}
		$exceptions = array();
		$seen       = array();
		foreach ( $rows as $index => $row ) {
			if ( ! is_array( $row ) ) {
				/* translators: %d is a weekday, exception row or line number. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Invalid exception row %d.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
			}
			$date  = $row['date'] ?? '';
			$type  = $row['type'] ?? '';
			$label = $row['label'] ?? '';
			if ( ! is_string( $date ) || ! self::valid_date( $date ) ) {
				/* translators: %d is a weekday, exception row or line number. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Invalid date on exception row %d. Use YYYY-MM-DD.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
			}
			if ( isset( $seen[ $date ] ) ) {
				/* translators: %d is a weekday, exception row or line number. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Duplicate exception date on row %d.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
			}
			$seen[ $date ] = true;
			if ( ! is_string( $label ) ) {
				/* translators: %d is a weekday, exception row or line number. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Invalid label on exception row %d.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
			}
			$label = sanitize_text_field( $label );
			if ( self::text_length( $label ) > 120 ) {
				/* translators: %d is a weekday, exception row or line number. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Exception label on row %d exceeds 120 characters.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
			}
			if ( 'closed' === $type ) {
				if ( ! empty( $row['intervals'] ) ) {
					/* translators: %d is a weekday, exception row or line number. */
					throw new InvalidArgumentException( sprintf( esc_html__( 'Closed exception on row %d cannot have hours.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
				}
				$intervals = array();
			} elseif ( 'hours' === $type ) {
				/* translators: %d is a weekday, exception row or line number. */
				$intervals = self::parse_intervals( $row['intervals'] ?? array(), sprintf( __( 'exception row %d', 'digicyfr-opening-hours-pl' ), $index + 1 ) );
				if ( array() === $intervals ) {
					/* translators: %d is a weekday, exception row or line number. */
					throw new InvalidArgumentException( sprintf( esc_html__( 'Hours exception on row %d needs at least one interval.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
				}
			} else {
				/* translators: %d is a weekday, exception row or line number. */
				throw new InvalidArgumentException( sprintf( esc_html__( 'Invalid type on exception row %d.', 'digicyfr-opening-hours-pl' ), absint( $index + 1 ) ) );
			}
			$exceptions[] = array(
				'date'      => $date,
				'type'      => $type,
				'intervals' => $intervals,
				'label'     => $label,
			);
		}
		usort( $exceptions, static fn( array $a, array $b ): int => strcmp( $a['date'], $b['date'] ) );
		return $exceptions;
	}

	/**
	 * Validate an inclusive site-local temporary notice.
	 *
	 * @param mixed $input Submitted notice.
	 * @return array Canonical notice.
	 * @throws InvalidArgumentException On invalid input.
	 */
	private static function parse_notice( $input ): array {
		if ( ! is_array( $input ) ) {
			throw new InvalidArgumentException( esc_html__( 'Invalid temporary notice.', 'digicyfr-opening-hours-pl' ) );
		}
		$enabled = ! empty( $input['enabled'] );
		$text    = isset( $input['text'] ) && is_string( $input['text'] ) ? sanitize_textarea_field( $input['text'] ) : '';
		$start   = isset( $input['start'] ) && is_string( $input['start'] ) ? trim( $input['start'] ) : '';
		$end     = isset( $input['end'] ) && is_string( $input['end'] ) ? trim( $input['end'] ) : '';
		if ( self::text_length( $text ) > 250 ) {
			throw new InvalidArgumentException( esc_html__( 'The temporary notice exceeds 250 characters.', 'digicyfr-opening-hours-pl' ) );
		}
		if ( ( '' !== $start && ! self::valid_date( $start ) ) || ( '' !== $end && ! self::valid_date( $end ) ) ) {
			throw new InvalidArgumentException( esc_html__( 'Notice dates must use valid YYYY-MM-DD dates.', 'digicyfr-opening-hours-pl' ) );
		}
		if ( $enabled && ( '' === $text || '' === $start || '' === $end || $start > $end ) ) {
			throw new InvalidArgumentException( esc_html__( 'An enabled notice needs text and a start date on or before its end date.', 'digicyfr-opening-hours-pl' ) );
		}
		return array(
			'enabled' => $enabled,
			'text'    => $text,
			'start'   => $start,
			'end'     => $end,
		);
	}

	/**
	 * Validate a strict calendar date independently of daylight-saving offsets.
	 *
	 * @param string $date ISO date.
	 * @return bool Whether the date exists.
	 */
	private static function valid_date( string $date ): bool {
		if ( ! preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $date, $parts ) ) {
			return false;
		}
		return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
	}

	/**
	 * Count display characters without assuming mbstring is installed.
	 *
	 * @param string $text Sanitized text.
	 * @return int Length in characters or bytes if mbstring is absent.
	 */
	private static function text_length( string $text ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
	}

	/** Register an administrator-only settings page. */
	public static function register_admin_page(): void {
		add_options_page(
			__( 'Opening Hours PL', 'digicyfr-opening-hours-pl' ),
			__( 'Opening Hours PL', 'digicyfr-opening-hours-pl' ),
			'manage_options',
			'digicyfr-opening-hours-pl',
			array( __CLASS__, 'admin_page' )
		);
	}

	/**
	 * Load styles only for this settings page.
	 *
	 * @param string $hook Admin page hook.
	 */
	public static function enqueue_admin_styles( string $hook ): void {
		if ( 'settings_page_digicyfr-opening-hours-pl' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'digicyfr-opening-hours-pl-style' );
		wp_enqueue_style( 'digicyfr-opening-hours-pl-admin', DIGICYFR_OPENING_HOURS_PL_URL . 'assets/admin.css', array(), DIGICYFR_OPENING_HOURS_PL_VERSION );
	}

	/** Use the same small stylesheet for the block and shortcode. */
	public static function enqueue_frontend_style(): void {
		wp_enqueue_style( 'digicyfr-opening-hours-pl-style' );
	}

	/** Localized weekday names in ISO order. */
	private static function weekday_names(): array {
		return array(
			1 => __( 'Monday', 'digicyfr-opening-hours-pl' ),
			2 => __( 'Tuesday', 'digicyfr-opening-hours-pl' ),
			3 => __( 'Wednesday', 'digicyfr-opening-hours-pl' ),
			4 => __( 'Thursday', 'digicyfr-opening-hours-pl' ),
			5 => __( 'Friday', 'digicyfr-opening-hours-pl' ),
			6 => __( 'Saturday', 'digicyfr-opening-hours-pl' ),
			7 => __( 'Sunday', 'digicyfr-opening-hours-pl' ),
		);
	}

	/** Render the keyboard-usable settings form and saved-data preview. */
	public static function admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage opening hours.', 'digicyfr-opening-hours-pl' ) );
		}
		$saved      = self::saved_schedule();
		$weekly     = $saved['weekly'] ?? array();
		$notice     = $saved['notice'] ?? array();
		$exceptions = $saved['exceptions'] ?? array();
		$timezone   = wp_timezone_string();
		?>
		<div class="wrap digicyfr-opening-hours-pl-admin">
			<h1><?php echo esc_html__( 'Opening Hours PL', 'digicyfr-opening-hours-pl' ); ?></h1>
			<p><?php echo esc_html__( 'Times and dates use the site timezone:', 'digicyfr-opening-hours-pl' ); ?> <strong><?php echo esc_html( $timezone ); ?></strong>.</p>
			<?php if ( preg_match( '/^[+-][0-9]{2}:[0-9]{2}$/D', $timezone ) ) : ?>
				<p class="notice notice-warning inline"><?php echo esc_html__( 'This site uses a fixed UTC offset. It will not follow daylight-saving changes. Choose a city timezone such as Europe/Warsaw in WordPress Settings if that is what your business uses.', 'digicyfr-opening-hours-pl' ); ?></p>
			<?php endif; ?>
			<form action="options.php" method="post">
				<?php settings_fields( 'digicyfr_opening_hours_pl' ); ?>
				<h2><?php echo esc_html__( 'Regular weekly hours', 'digicyfr-opening-hours-pl' ); ?></h2>
				<p><?php echo esc_html__( 'Use up to four intervals per day. Leave every row blank to mark a day closed. Enter 24:00 only as an end time; split overnight hours across two dates.', 'digicyfr-opening-hours-pl' ); ?></p>
				<?php foreach ( self::weekday_names() as $day => $label ) : ?>
					<fieldset class="digicyfr-opening-hours-pl-day">
						<legend><?php echo esc_html( $label ); ?></legend>
						<?php for ( $row = 0; $row < 4; $row++ ) : ?>
							<?php $interval = $weekly[ (string) $day ][ $row ] ?? array( '', '' ); ?>
							<div class="digicyfr-opening-hours-pl-pair">
								<label for="digicyfr-opening-hours-pl-<?php echo esc_attr( $day . '-' . $row . '-start' ); ?>">
									<?php /* translators: %d is the interval number. */ ?>
									<?php echo esc_html( sprintf( __( 'Interval %d start', 'digicyfr-opening-hours-pl' ), $row + 1 ) ); ?>
									<input id="digicyfr-opening-hours-pl-<?php echo esc_attr( $day . '-' . $row . '-start' ); ?>" name="<?php echo esc_attr( self::OPTION . '[weekly][' . $day . '][' . $row . '][start]' ); ?>" type="text" inputmode="numeric" maxlength="5" placeholder="09:00" value="<?php echo esc_attr( $interval[0] ?? '' ); ?>" />
								</label>
								<label for="digicyfr-opening-hours-pl-<?php echo esc_attr( $day . '-' . $row . '-end' ); ?>">
									<?php /* translators: %d is the interval number. */ ?>
									<?php echo esc_html( sprintf( __( 'Interval %d end', 'digicyfr-opening-hours-pl' ), $row + 1 ) ); ?>
									<input id="digicyfr-opening-hours-pl-<?php echo esc_attr( $day . '-' . $row . '-end' ); ?>" name="<?php echo esc_attr( self::OPTION . '[weekly][' . $day . '][' . $row . '][end]' ); ?>" type="text" inputmode="numeric" maxlength="5" placeholder="17:00" value="<?php echo esc_attr( $interval[1] ?? '' ); ?>" />
								</label>
							</div>
						<?php endfor; ?>
					</fieldset>
				<?php endforeach; ?>
				<h2><?php echo esc_html__( 'Dated exceptions', 'digicyfr-opening-hours-pl' ); ?></h2>
				<p id="digicyfr-opening-hours-pl-exception-help"><?php echo esc_html__( 'One line per date: YYYY-MM-DD | closed | Optional label, or YYYY-MM-DD | 09:00-12:00,13:00-17:00 | Optional label. An exception replaces that date’s regular hours.', 'digicyfr-opening-hours-pl' ); ?></p>
				<label for="digicyfr-opening-hours-pl-exceptions"><strong><?php echo esc_html__( 'Exceptions', 'digicyfr-opening-hours-pl' ); ?></strong></label><br />
				<textarea id="digicyfr-opening-hours-pl-exceptions" name="<?php echo esc_attr( self::OPTION . '[exceptions_text]' ); ?>" aria-describedby="digicyfr-opening-hours-pl-exception-help" rows="8" cols="80" class="large-text code"><?php echo esc_textarea( self::format_exceptions( $exceptions ) ); ?></textarea>
				<h2><?php echo esc_html__( 'Temporary notice', 'digicyfr-opening-hours-pl' ); ?></h2>
				<p><label><input name="<?php echo esc_attr( self::OPTION . '[notice][enabled]' ); ?>" type="checkbox" value="1" <?php checked( ! empty( $notice['enabled'] ) ); ?> /> <?php echo esc_html__( 'Show notice during the inclusive date range', 'digicyfr-opening-hours-pl' ); ?></label></p>
				<p><label for="digicyfr-opening-hours-pl-notice-text"><?php echo esc_html__( 'Notice text', 'digicyfr-opening-hours-pl' ); ?></label><br />
				<textarea id="digicyfr-opening-hours-pl-notice-text" name="<?php echo esc_attr( self::OPTION . '[notice][text]' ); ?>" rows="3" cols="70" maxlength="250"><?php echo esc_textarea( $notice['text'] ?? '' ); ?></textarea></p>
				<p><label for="digicyfr-opening-hours-pl-notice-start"><?php echo esc_html__( 'Start date', 'digicyfr-opening-hours-pl' ); ?></label>
				<input id="digicyfr-opening-hours-pl-notice-start" name="<?php echo esc_attr( self::OPTION . '[notice][start]' ); ?>" type="date" value="<?php echo esc_attr( $notice['start'] ?? '' ); ?>" />
				<label for="digicyfr-opening-hours-pl-notice-end"><?php echo esc_html__( 'End date', 'digicyfr-opening-hours-pl' ); ?></label>
				<input id="digicyfr-opening-hours-pl-notice-end" name="<?php echo esc_attr( self::OPTION . '[notice][end]' ); ?>" type="date" value="<?php echo esc_attr( $notice['end'] ?? '' ); ?>" /></p>
				<?php submit_button( __( 'Save opening hours', 'digicyfr-opening-hours-pl' ) ); ?>
			</form>
			<div class="digicyfr-opening-hours-pl-preview">
				<h2><?php echo esc_html__( 'Saved seven-day preview', 'digicyfr-opening-hours-pl' ); ?></h2>
				<?php echo self::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes each element. ?>
				<p><?php echo esc_html__( 'Shortcode:', 'digicyfr-opening-hours-pl' ); ?> <code>[digicyfr_opening_hours]</code></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Format saved exceptions back into the documented textarea syntax.
	 *
	 * @param array $exceptions Canonical rows.
	 * @return string Editable text.
	 */
	private static function format_exceptions( array $exceptions ): string {
		$lines = array();
		foreach ( $exceptions as $exception ) {
			if ( ! is_array( $exception ) ) {
				continue;
			}
			$spec    = 'closed' === ( $exception['type'] ?? '' ) ? 'closed' : implode( ',', array_map( static fn( array $pair ): string => $pair[0] . '-' . $pair[1], $exception['intervals'] ?? array() ) );
			$lines[] = ( $exception['date'] ?? '' ) . ' | ' . $spec . ' | ' . ( $exception['label'] ?? '' );
		}
		return implode( "\n", $lines );
	}

	/** Read a configured version-one option, retaining a distinct absent state. */
	private static function saved_schedule(): ?array {
		$value = get_option( self::OPTION, false );
		return is_array( $value ) && 1 === ( $value['schema_version'] ?? null ) ? $value : null;
	}

	/**
	 * Seven successive site-local calendar dates, even across DST transitions.
	 *
	 * @param DateTimeImmutable $now Current instant.
	 * @param DateTimeZone      $timezone Site timezone.
	 * @return array Seven DateTimeImmutable local midnights.
	 * @throws RuntimeException If a site-local date cannot be constructed.
	 */
	public static function local_dates( DateTimeImmutable $now, DateTimeZone $timezone ): array {
		$local = $now->setTimezone( $timezone );
		$start = DateTimeImmutable::createFromFormat( '!Y-m-d', $local->format( 'Y-m-d' ), $timezone );
		if ( false === $start ) {
			throw new RuntimeException( 'Could not construct local calendar date.' );
		}
		$dates = array();
		for ( $offset = 0; $offset < 7; $offset++ ) {
			$dates[] = $start->modify( '+' . $offset . ' day' );
		}
		return $dates;
	}

	/**
	 * Shared public renderer for the block, shortcode and admin preview.
	 *
	 * @param DateTimeImmutable|null $now Optional test instant.
	 * @return string Escaped semantic HTML.
	 */
	public static function render( ?DateTimeImmutable $now = null ): string {
		$schedule = self::saved_schedule();
		if ( null === $schedule ) {
			return '<p class="digicyfr-opening-hours-pl__unconfigured">' . esc_html__( 'Opening hours have not been configured yet.', 'digicyfr-opening-hours-pl' ) . '</p>';
		}
		$timezone   = wp_timezone();
		$dates      = self::local_dates( $now ?? current_datetime(), $timezone );
		$today      = $dates[0]->format( 'Y-m-d' );
		$exceptions = array();
		foreach ( $schedule['exceptions'] ?? array() as $exception ) {
			if ( is_array( $exception ) && isset( $exception['date'] ) && is_string( $exception['date'] ) ) {
				$exceptions[ $exception['date'] ] = $exception;
			}
		}
		$html   = '<div class="digicyfr-opening-hours-pl">';
		$notice = $schedule['notice'] ?? array();
		if ( is_array( $notice ) && ! empty( $notice['enabled'] ) && isset( $notice['text'], $notice['start'], $notice['end'] ) && is_string( $notice['text'] ) && $today >= $notice['start'] && $today <= $notice['end'] ) {
			$html .= '<p class="digicyfr-opening-hours-pl__notice">' . nl2br( esc_html( $notice['text'] ) ) . '</p>';
		}
		$html .= '<table><caption>' . esc_html__( 'Opening hours for the next seven days', 'digicyfr-opening-hours-pl' ) . '</caption>';
		$html .= '<thead><tr><th scope="col">' . esc_html__( 'Date', 'digicyfr-opening-hours-pl' ) . '</th><th scope="col">' . esc_html__( 'Hours', 'digicyfr-opening-hours-pl' ) . '</th></tr></thead><tbody>';
		foreach ( $dates as $date ) {
			$key       = $date->format( 'Y-m-d' );
			$weekday   = $date->format( 'N' );
			$exception = $exceptions[ $key ] ?? null;
			$intervals = is_array( $exception ) ? ( $exception['intervals'] ?? array() ) : ( $schedule['weekly'][ $weekday ] ?? array() );
			$html     .= '<tr><th scope="row"><time datetime="' . esc_attr( $key ) . '">' . esc_html( wp_date( 'l, j F Y', $date->getTimestamp(), $timezone ) ) . '</time>';
			if ( is_array( $exception ) && ! empty( $exception['label'] ) && is_string( $exception['label'] ) ) {
				$html .= '<span class="digicyfr-opening-hours-pl__exception">' . esc_html( $exception['label'] ) . '</span>';
			}
			$html .= '</th><td>';
			if ( ! is_array( $intervals ) || array() === $intervals ) {
				$html .= esc_html__( 'Closed', 'digicyfr-opening-hours-pl' );
			} else {
				$display = array();
				foreach ( $intervals as $pair ) {
					if ( is_array( $pair ) && isset( $pair[0], $pair[1] ) && is_string( $pair[0] ) && is_string( $pair[1] ) ) {
						$display[] = esc_html( $pair[0] . '–' . $pair[1] );
					}
				}
				$html .= $display ? implode( ', ', $display ) : esc_html__( 'Closed', 'digicyfr-opening-hours-pl' );
			}
			$html .= '</td></tr>';
		}
		return $html . '</tbody></table></div>';
	}

	/** Server-rendered block callback. */
	public static function render_block(): string {
		return self::render();
	}

	/** Shortcode callback with no public attributes. */
	public static function shortcode(): string {
		return self::render();
	}
}
