<?php
/**
 * Remove this plugin's one settings option on uninstall.
 *
 * @package Digicyfr_Opening_Hours_PL
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$digicyfr_opening_hours_pl_option = 'digicyfr_opening_hours_pl_schedule';

if ( is_multisite() ) {
	$digicyfr_opening_hours_pl_offset = 0;
	do {
		$digicyfr_opening_hours_pl_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => $digicyfr_opening_hours_pl_offset,
			)
		);
		foreach ( $digicyfr_opening_hours_pl_ids as $digicyfr_opening_hours_pl_site_id ) {
			switch_to_blog( $digicyfr_opening_hours_pl_site_id );
			delete_option( $digicyfr_opening_hours_pl_option );
			restore_current_blog();
		}
		$digicyfr_opening_hours_pl_site_count = count( $digicyfr_opening_hours_pl_ids );
		$digicyfr_opening_hours_pl_offset    += $digicyfr_opening_hours_pl_site_count;
	} while ( 100 === $digicyfr_opening_hours_pl_site_count );
} else {
	delete_option( $digicyfr_opening_hours_pl_option );
}
