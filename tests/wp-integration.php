<?php
/** Exercise the exact installed ZIP in a disposable WordPress site. */

declare( strict_types=1 );

$port = getenv( 'DIGICYFR_TEST_PORT' );
$_SERVER['HTTP_HOST']   = '127.0.0.1:' . $port;
$_SERVER['SERVER_NAME'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_PORT'] = $port;
$_SERVER['HTTPS']      = 'off';

require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

function check( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $message\n" );
		exit( 1 );
	}
}

function blank_week(): array {
	return array_fill_keys( array_map( 'strval', range( 1, 7 ) ), array() );
}

function form_data( array $weekly, string $exceptions = '', array $notice = array() ): array {
	return array(
		'weekly'          => $weekly,
		'exceptions_text' => $exceptions,
		'notice'          => $notice,
	);
}

$option = 'digicyfr_opening_hours_pl_schedule';
$plugin_file = 'digicyfr-opening-hours-pl/digicyfr-opening-hours-pl.php';
check( is_plugin_active( $plugin_file ), 'installed ZIP is active' );
check( shortcode_exists( 'digicyfr_opening_hours' ), 'shortcode registered' );
$block = WP_Block_Type_Registry::get_instance()->get_registered( 'digicyfr/opening-hours' );
check( $block instanceof WP_Block_Type, 'server block registered' );
check( in_array( 'digicyfr-opening-hours-pl-editor', $block->editor_script_handles, true ), 'editor script attached' );
check( in_array( 'digicyfr-opening-hours-pl-style', $block->style_handles, true ), 'style attached' );
do_action( 'admin_init' );
check( isset( get_registered_settings()[ $option ] ), 'Settings API option registered' );
check( empty( get_registered_settings()[ $option ]['show_in_rest'] ), 'settings private to administrator' );
check( false === get_option( $option, false ), 'option absent before valid save' );
check( str_contains( Digicyfr_Opening_Hours_PL::render(), 'not been configured' ), 'unconfigured public state' );

$invalid = form_data( blank_week(), 'not-a-date | closed' );
update_option( $option, $invalid );
check( false === get_option( $option, false ), 'invalid initial save leaves option absent' );
check( count( get_settings_errors( $option ) ) > 0, 'invalid save produces Settings API error' );

update_option( $option, form_data( blank_week() ) );
$closed = get_option( $option );
check( is_array( $closed ) && 1 === $closed['schema_version'], 'valid all-closed week persists' );
check( 7 === count( $closed['weekly'] ) && array() === $closed['weekly']['7'], 'all seven closed weekdays saved' );
check( ! str_contains( Digicyfr_Opening_Hours_PL::render(), 'not been configured' ), 'closed differs from unconfigured' );
check( 7 === substr_count( Digicyfr_Opening_Hours_PL::render(), 'Closed' ), 'closed week shows seven Closed rows' );

$week = blank_week();
$week['1'] = array( array( 'start' => '09:00', 'end' => '12:00' ), array( 'start' => '13:00', 'end' => '17:00' ) );
$week['4'] = array( array( 'start' => '09:00', 'end' => '17:00' ) );
$week['5'] = array( array( 'start' => '09:00', 'end' => '17:00' ) );
$week['7'] = array();
update_option( $option, form_data( $week ) );
$stored = get_option( $option );
check( array( array( '09:00', '12:00' ), array( '13:00', '17:00' ) ) === $stored['weekly']['1'], 'Monday intervals normalized' );
$shortcode = do_shortcode( '[digicyfr_opening_hours]' );
$block_html = do_blocks( '<!-- wp:digicyfr/opening-hours /-->' );
check( $shortcode === $block_html, 'shortcode and block output identical' );
$next_monday = new DateTimeImmutable( 'next monday 12:00', wp_timezone() );
$view = Digicyfr_Opening_Hours_PL::render( $next_monday );
check( str_contains( $view, '09:00–12:00, 13:00–17:00' ), 'two intervals rendered on Monday' );
check( str_contains( $view, 'Closed' ), 'saved Sunday closed' );

$adjacent = $week;
$adjacent['1'] = array( array( 'start' => '12:00', 'end' => '13:00' ), array( 'start' => '13:00', 'end' => '17:00' ) );
update_option( $option, form_data( $adjacent ) );
check( '12:00' === get_option( $option )['weekly']['1'][0][0], 'adjacent intervals accepted' );
$before = get_option( $option );
foreach ( array( array( '12:00', '12:00' ), array( '12:00', '11:00' ), array( '25:00', '26:00' ), array( '24:00', '24:00' ), array( '22:00', '02:00' ) ) as $pair ) {
	$bad = $adjacent;
	$bad['1'] = array( array( 'start' => $pair[0], 'end' => $pair[1] ) );
	update_option( $option, form_data( $bad ) );
	check( $before === get_option( $option ), 'invalid interval leaves prior schedule unchanged: ' . implode( '-', $pair ) );
}
$overlap = $adjacent;
$overlap['1'] = array( array( 'start' => '09:00', 'end' => '13:00' ), array( 'start' => '12:00', 'end' => '17:00' ) );
update_option( $option, form_data( $overlap ) );
check( $before === get_option( $option ), 'overlap rejected' );
$overnight = $week;
$overnight['5'] = array( array( 'start' => '22:00', 'end' => '24:00' ) );
$overnight['6'] = array( array( 'start' => '00:00', 'end' => '02:00' ) );
update_option( $option, form_data( $overnight ) );
check( '24:00' === get_option( $option )['weekly']['5'][0][1], 'split overnight accepted' );

$exceptions = "2026-12-24 | 09:00-12:00 | Wigilia\n2026-12-25 | closed | Święto";
$notice = array( 'enabled' => '1', 'text' => 'Zmiana godzin', 'start' => '2026-12-23', 'end' => '2026-12-31' );
update_option( $option, form_data( $week, $exceptions, $notice ) );
$stored = get_option( $option );
check( 2 === count( $stored['exceptions'] ), 'dated exceptions stored' );
$christmas = Digicyfr_Opening_Hours_PL::render( new DateTimeImmutable( '2026-12-24 12:00', wp_timezone() ) );
check( str_contains( $christmas, 'Wigilia' ) && str_contains( $christmas, '09:00–12:00' ), 'hours exception replaces Thursday' );
check( ! str_contains( $christmas, '09:00–17:00' ), 'Thursday regular hours hidden on exception date' );
check( str_contains( $christmas, 'Święto' ), 'closed exception label rendered' );
check( str_contains( $christmas, 'Zmiana godzin' ), 'notice shown within inclusive range' );
$notice_start = Digicyfr_Opening_Hours_PL::render( new DateTimeImmutable( '2026-12-23 12:00', wp_timezone() ) );
$notice_end = Digicyfr_Opening_Hours_PL::render( new DateTimeImmutable( '2026-12-31 12:00', wp_timezone() ) );
$after_notice = Digicyfr_Opening_Hours_PL::render( new DateTimeImmutable( '2027-01-01 12:00', wp_timezone() ) );
check( str_contains( $notice_start, 'Zmiana godzin' ) && str_contains( $notice_end, 'Zmiana godzin' ), 'notice inclusive endpoints' );
check( ! str_contains( $after_notice, 'Zmiana godzin' ), 'notice hidden after end' );
$before = get_option( $option );
update_option( $option, form_data( $week, "2026-12-24 | closed\n2026-12-24 | closed", $notice ) );
check( $before === get_option( $option ), 'duplicate exception rejected atomically' );
$bad_notice = $notice;
$bad_notice['start'] = '2027-01-01';
update_option( $option, form_data( $week, $exceptions, $bad_notice ) );
check( $before === get_option( $option ), 'inverted enabled notice rejected atomically' );

$hostile = form_data( $week, '2026-12-24 | closed | <img src=x onerror=alert(1)>', array( 'enabled' => '1', 'text' => '<script>alert(1)</script>', 'start' => '2026-12-24', 'end' => '2026-12-24' ) );
update_option( $option, $hostile );
$html = Digicyfr_Opening_Hours_PL::render( new DateTimeImmutable( '2026-12-24 12:00', wp_timezone() ) );
check( ! str_contains( $html, '<img' ) && ! str_contains( $html, '<script' ), 'hostile label and notice HTML excluded' );

update_option( 'timezone_string', 'Europe/Warsaw' );
$warsaw = wp_timezone();
foreach ( array( '2026-03-27', '2026-10-23' ) as $start ) {
	$dates = Digicyfr_Opening_Hours_PL::local_dates( new DateTimeImmutable( "$start 12:00", $warsaw ), $warsaw );
	check( 7 === count( $dates ), 'seven local dates' );
	foreach ( $dates as $i => $date ) {
		check( $date->format( 'Y-m-d' ) === ( new DateTimeImmutable( $start, $warsaw ) )->modify( '+' . $i . ' day' )->format( 'Y-m-d' ), 'successive calendar date across DST' );
	}
	$hours = array_map( static fn( DateTimeImmutable $date ): int => $date->getTimestamp(), $dates );
	$durations = array();
	for ( $i = 1; $i < count( $hours ); $i++ ) {
		$durations[] = $hours[ $i ] - $hours[ $i - 1 ];
	}
	check( in_array( 23 * 3600, $durations, true ) || in_array( 25 * 3600, $durations, true ), 'DST transition occurs within seven calendar dates' );
	$view = Digicyfr_Opening_Hours_PL::render( new DateTimeImmutable( "$start 12:00", $warsaw ) );
	check( 7 === substr_count( $view, '<time datetime=' ), 'seven output dates across DST' );
}
$week = blank_week();
$week['7'] = array( array( 'start' => '02:00', 'end' => '03:00' ) );
update_option( $option, form_data( $week ) );
$view = Digicyfr_Opening_Hours_PL::render( new DateTimeImmutable( '2026-10-23 12:00', $warsaw ) );
check( str_contains( $view, '02:00–03:00' ), 'wall-clock interval unchanged across repeated hour' );
update_option( 'timezone_string', '' );
update_option( 'gmt_offset', 1 );
check( '+01:00' === wp_timezone_string(), 'fixed offset remains a fixed offset' );

$admin = get_user_by( 'login', 'digicyfr_test_admin' );
check( $admin instanceof WP_User, 'test admin exists' );
wp_set_current_user( $admin->ID );
ob_start();
Digicyfr_Opening_Hours_PL::admin_page();
$admin_html = ob_get_clean();
check( str_contains( $admin_html, 'fixed UTC offset' ), 'settings page warns on fixed offset' );
check( str_contains( $admin_html, 'digicyfr-opening-hours-pl-preview' ), 'settings preview present' );
check( str_contains( $admin_html, 'option_page' ), 'Settings API form fields present' );
$contributor_id = wp_create_user( 'digicyfr_hours_contributor', 'test-only-password', 'hours-contributor@example.invalid' );
check( ! is_wp_error( $contributor_id ), 'contributor created' );
( new WP_User( $contributor_id ) )->set_role( 'contributor' );
wp_set_current_user( $contributor_id );
check( ! current_user_can( 'manage_options' ), 'contributor lacks capability' );
add_filter( 'wp_die_handler', static fn(): callable => static function (): void { throw new RuntimeException( 'denied' ); } );
$denied = false;
try { Digicyfr_Opening_Hours_PL::admin_page(); } catch ( RuntimeException $error ) { $denied = true; }
check( $denied, 'contributor denied settings page' );

check( load_textdomain( 'digicyfr-opening-hours-pl', WP_PLUGIN_DIR . '/digicyfr-opening-hours-pl/languages/digicyfr-opening-hours-pl-pl_PL.mo' ), 'Polish MO loads' );
check( 'Zamknięte' === __( 'Closed', 'digicyfr-opening-hours-pl' ), 'Polish public translation works' );
check( 'Godziny otwarcia PL' === __( 'Opening Hours PL', 'digicyfr-opening-hours-pl' ), 'Polish admin translation works' );

wp_set_current_user( $admin->ID );
deactivate_plugins( $plugin_file );
uninstall_plugin( $plugin_file );
global $wpdb;
$remaining = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s", $option ) );
check( 0 === (int) $remaining, 'uninstall removes plugin option' );
echo "Opening Hours PL WordPress integration tests passed\n";
