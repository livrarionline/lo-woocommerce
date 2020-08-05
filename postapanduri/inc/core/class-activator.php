<?php

/**
 * Fired during plugin activation
 *
 * @link       https://postapanduri.ro
 * @since      1.0.0
 *
 * @package    PostaPanduri
 * @subpackage PostaPanduri/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    PostaPanduri
 * @subpackage PostaPanduri/includes
 * @author     Adrian Lado <adrian@plationline.eu>
 */

namespace Postapanduri\Inc\Core;

class Activator
{
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */

	public static function activate()
	{
		$php_min_version = '5.5';
		$curl_min_version = '7.29.0';
		$openssl_min_version = 0x1000100f; //1.0.1

		// Check PHP Version and deactivate & die if it doesn't meet minimum requirements.
		if (\version_compare(PHP_VERSION, $php_min_version, '<')) {
			deactivate_plugins(NS\PLUGIN_BASENAME);
			wp_die('This plugin requires a minmum PHP Version of ' . $php_min_version);
		}

		if (\version_compare(WOOCOMMERCE_VERSION, '3.0.4', '<')) {
			deactivate_plugins(NS\PLUGIN_BASENAME);
			wp_die('This plugin requires Woocommerce minimum version 3.0.4 or later');
		}

		if (!\extension_loaded('curl')) {
			deactivate_plugins(NS\PLUGIN_BASENAME);
			wp_die('This plugin requires PHP CURL extension to be installed and active');
		}

		if (\version_compare(\curl_version()['version'], $curl_min_version, '<')) {
			deactivate_plugins(NS\PLUGIN_BASENAME);
			wp_die('This plugin requires a minmum cURL Version of ' . $curl_min_version);
		}

		if (!\extension_loaded('openssl')) {
			deactivate_plugins(NS\PLUGIN_BASENAME);
			wp_die('This plugin requires a minmum OpenSSL extension');
		}

		if (OPENSSL_VERSION_NUMBER < $openssl_min_version) {
			deactivate_plugins(NS\PLUGIN_BASENAME);
			wp_die('This plugin requires a minmum OpenSSL Version of 1.0.1' . $openssl_min_version);
		}

		if (!\class_exists('WC_Payment_Gateway')) {
			deactivate_plugins(NS\PLUGIN_BASENAME);
			wp_die('This plugin requires Woocommerce');
		}

		global $jal_db_version;
		global $wpdb;

		$installed_ver = get_option("jal_db_version");

		if ($installed_ver != $jal_db_version) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$charset_collate = $wpdb->get_charset_collate();
			$table_name = $wpdb->prefix . 'lo_awb';
			$sql = "CREATE TABLE $table_name (
			  `id` int(9) NOT NULL AUTO_INCREMENT,
			  `awb` varchar(50) NOT NULL,
			  `f_token` varchar(512) NOT NULL,
			  `id_comanda` int(11) unsigned NOT NULL,
			  `id_serviciu` int(11) NOT NULL,
			  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `generat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `generated_awb_price` decimal(10, 2) NULL,
			  `payload` mediumtext null,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `unique` (`awb`,`id_comanda`),
			  KEY `id_comanda` (`id_comanda`,`deleted`)
		  	) $charset_collate;";
			dbDelta($sql);

			$table_name = $wpdb->prefix . 'lo_delivery_points';
			$sql = "CREATE TABLE $table_name (
			  `dp_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `dp_denumire` varchar(255) NOT NULL,
			  `dp_adresa` varchar(255) NOT NULL,
			  `dp_judet` varchar(50) NOT NULL,
			  `dp_oras` varchar(50) NOT NULL,
			  `dp_tara` varchar(255) NOT NULL,
			  `dp_cod_postal` varchar(255) NOT NULL,
			  `dp_gps_lat` double NOT NULL,
			  `dp_gps_long` double NOT NULL,
			  `dp_tip` int(11) DEFAULT '1',
			  `dp_active` tinyint(1) NOT NULL DEFAULT '0',
			  `version_id` int(11) NOT NULL,
			  `stamp_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `dp_temperatura` decimal(10,2) DEFAULT NULL,
			  `dp_indicatii` text,
			  `termosensibil` tinyint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`dp_id`)
		  	) $charset_collate;";
			dbDelta($sql);

			$table_name = $wpdb->prefix . 'lo_dp_day_exceptions';
			$sql = "CREATE TABLE $table_name (
			  `leg_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `dp_id` int(10) unsigned NOT NULL,
			  `exception_day` date NOT NULL,
			  `dp_start_program` time NOT NULL DEFAULT '00:00:00',
			  `dp_end_program` time NOT NULL DEFAULT '00:00:00',
			  `active` tinyint(1) NOT NULL,
			  `version_id` int(10) NOT NULL,
			  `stamp_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`leg_id`),
			  KEY `delivery_point` (`dp_id`,`exception_day`)
		  	) $charset_collate;";
			dbDelta($sql);

			$table_name = $wpdb->prefix . 'lo_dp_program';
			$sql = "CREATE TABLE $table_name (
			  `leg_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `dp_start_program` time NOT NULL DEFAULT '00:00:00',
			  `dp_end_program` time NOT NULL DEFAULT '00:00:00',
			  `dp_id` int(10) unsigned NOT NULL,
			  `day_active` tinyint(1) NOT NULL,
			  `version_id` int(10) NOT NULL,
			  `day_number` int(11) NOT NULL,
			  `day` varchar(50) NOT NULL,
			  `day_sort_order` int(1) NOT NULL,
			  `stamp_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`leg_id`),
			  KEY `delivery_point` (`dp_id`,`day`(1))
			) $charset_collate;";
			dbDelta($sql);

			$wpdb->query("DROP TRIGGER IF EXISTS {$wpdb->prefix}lo_dp_program_BEFORE_INSERT;");
			$wpdb->query("DROP TRIGGER IF EXISTS {$wpdb->prefix}lo_dp_program_BEFORE_UPDATE;");

			$sql_trigger = "CREATE TRIGGER {$wpdb->prefix}lo_dp_program_BEFORE_INSERT BEFORE INSERT ON {$wpdb->prefix}lo_dp_program FOR EACH ROW
								SET new.`day_sort_order` =
								CASE
									WHEN (new.`day_number` = 1) THEN 1
									WHEN (new.`day_number` = 2) THEN 2
									WHEN (new.`day_number` = 3) THEN 3
									WHEN (new.`day_number` = 4) THEN 4
									WHEN (new.`day_number` = 5) THEN 5
									WHEN (new.`day_number` = 6) THEN 6
									WHEN (new.`day_number` = 0) THEN 7
							END;";

			$wpdb->query($sql_trigger);

			$sql_trigger = "CREATE TRIGGER {$wpdb->prefix}lo_dp_program_BEFORE_UPDATE BEFORE UPDATE ON {$wpdb->prefix}lo_dp_program FOR EACH ROW
								SET new.`day_sort_order` =
								CASE
									WHEN (new.`day_number` = 1) THEN 1
									WHEN (new.`day_number` = 2) THEN 2
									WHEN (new.`day_number` = 3) THEN 3
									WHEN (new.`day_number` = 4) THEN 4
									WHEN (new.`day_number` = 5) THEN 5
									WHEN (new.`day_number` = 6) THEN 6
									WHEN (new.`day_number` = 0) THEN 7
							END;";

			$wpdb->query($sql_trigger);
			update_option("jal_db_version", $jal_db_version);
		}
	}
}
