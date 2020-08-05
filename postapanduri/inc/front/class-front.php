<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://postapanduri.ro
 * @since      1.0.0
 *
 * @package    PostaPanduri
 * @subpackage PostaPanduri/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    PostaPanduri
 * @subpackage PostaPanduri/public
 * @author     Adrian Lado <adrian@plationline.eu>
 */

namespace Postapanduri\Inc\Front;

use PostaPanduri\Inc\Core\WC_PostaPanduri;
use PostaPanduri\Inc\Libraries\LO as LO;

class Front
{
	private $plugin_name;
	private $version;
	private $plugin_text_domain;

	public function __construct($plugin_name, $version, $plugin_text_domain)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_text_domain = $plugin_text_domain;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in PostaPanduri_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The PostaPanduri_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/postapanduri-public.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script('postapanduri_script_public', plugin_dir_url(__FILE__) . 'js/postapanduri-public.js', array(), null, true);
		$gmaps_api_key = !empty(get_option('postapanduri_setari_generale')['gmaps_api_key']) ? get_option('postapanduri_setari_generale')['gmaps_api_key'] : '';
		$this->rcp_get_template_part('plugin-postapanduri-gmaps', array('gmaps_api_key' => $gmaps_api_key));
		wp_register_script("postapanduri_ajax_script", plugin_dir_url(__FILE__) . 'js/postapanduri-public-ajax.js', array('jquery', 'wp-i18n'), $this->version, true);
		wp_set_script_translations('postapanduri_ajax_script', 'postapanduri');
		wp_localize_script('postapanduri_ajax_script', 'ppa', array('ajaxurl' => admin_url('admin-ajax.php')));
		wp_enqueue_script('postapanduri_ajax_script');
		wp_enqueue_script('postapanduri_script');
		wp_enqueue_script('postapanduri_script_public');
		wp_set_script_translations('postapanduri_script_public', 'postapanduri');
	}

	public function rcp_locate_template($template_names, $load = false, $require_once = true)
	{
		$located = false;
		foreach ((array)$template_names as $template_name) {
			if (empty($template_name)) {
				continue;
			}
			$template_name = ltrim($template_name, '/');
			if (file_exists(plugin_dir_path(__FILE__) . 'partials/') . $template_name) {
				$located = plugin_dir_path(__FILE__) . 'partials/' . $template_name;
				break;
			}
		}
		if ((true == $load) && !empty($located)) {
			load_template($located, $require_once);
		}
		return $located;
	}

	public function rcp_get_template_part($slug, $data, $name = null, $load = true)
	{
		foreach ($data as $key => $value) {
			set_query_var($key, $value);
		}
		do_action('get_template_part_' . $slug, $slug, $name);
		// Setup possible parts
		$templates = array();
		if (isset($name)) {
			$templates[] = $slug . '-' . $name . '.php';
		}

		$templates[] = $slug . '.php';

		// Return the part that is found
		return $this->rcp_locate_template($templates, $load, false);
	}

	public function ajax_get_localitati()
	{
		$lo = new LO();
		$localitati = $lo->get_all_delivery_points_location_by_state(trim($_POST['judet']));
		$pachetomate = $lo->get_all_delivery_points_location_by_judet(trim($_POST['judet']));
		echo json_encode(array('count' => count($localitati), 'orase' => $localitati, 'pachetomate' => $pachetomate, 'jselected' => WC()->session->get('judet'), 'selected' => WC()->session->get('oras'), 'pselected' => WC()->session->get('dp_id'), 'selected_name' => WC()->session->get('dp_name')));
		wp_die();
	}

	public function ajax_get_pachetomate()
	{
		$lo = new LO();
		$pachetomate = $lo->get_all_delivery_points_location_by_localitate(trim($_POST['oras']));
		echo json_encode(array('count' => count($pachetomate), 'pachetomate' => $pachetomate, 'oselected' => WC()->session->get('oras'), 'selected' => WC()->session->get('dp_id'), 'selected_name' => WC()->session->get('dp_name')));
		wp_die();
	}

	public function ajax_get_pachetomat()
	{
		$lo = new LO();
		$pachetomat = $lo->get_delivery_point_by_id(trim($_POST['pachetomat']));
		if (!empty($pachetomat)) {
			WC()->session->set('judet', $pachetomat->dp_judet);
			WC()->session->set('oras', $pachetomat->dp_oras);
			WC()->session->set('dp_id', $pachetomat->dp_id);
			WC()->session->set('dp_name', $pachetomat->dp_denumire);
			$chosen_method = WC()->session->get('chosen_shipping_methods')[0];
			echo json_encode(array('pachetomat' => $pachetomat, 'selected' => WC()->session->get('dp_id'), 'selected_name' => WC()->session->get('dp_name')));
		}
		wp_die();
	}

	public function pp_add_delivery_point_id($order_id)
	{
		$chosen_methods = WC()->session->get('chosen_shipping_methods');
		$chosen_method = $chosen_methods[0];
		$chosen_method = explode('_', $chosen_method)[0];
		$dp_id = WC()->session->get('dp_id');

		if ($chosen_method == 'pachetomat' && $dp_id) {
			update_post_meta($order_id, 'id_pachetomat', sanitize_text_field(WC()->session->get('dp_id')));
		}
	}

	public function pp_action_before_woocommerce_pay()
	{
		WC()->session->__unset('chosen_shipping_methods');
		WC()->session->__unset('judet');
		WC()->session->__unset('oras');
		WC()->session->__unset('dp_id');
		WC()->session->__unset('dp_name');
	}

	public function pp_filter_woocommerce_thankyou_order_received_text($message, $order)
	{
		WC()->session->__unset('chosen_shipping_methods');
		WC()->session->__unset('judet');
		WC()->session->__unset('oras');
		WC()->session->__unset('dp_id');
		WC()->session->__unset('dp_name');
		foreach ($order->get_meta_data() as $meta) {
			if ($meta->key == 'id_pachetomat') {
				$lo = new LO();
				$pachetomat = $lo->get_delivery_point_by_id($meta->value);
				$message = sprintf(__('Dupa procesare, coletul Dvs va fi livrat in pachetomatul <b>%s - %s</b>. Adresa: %s, Judet %s, Localitate: %s', 'postapanduri'), $pachetomat->dp_id, $pachetomat->dp_denumire, $pachetomat->dp_adresa, $pachetomat->dp_judet, $pachetomat->dp_oras);
				$gmaps_api_key = get_option('postapanduri_setari_generale')['gmaps_api_key'];
				if ($gmaps_api_key) {
					$icon = '';
					$harta = '<img style="-webkit-user-select: none;width:100%;" src="https://maps.googleapis.com/maps/api/staticmap?center=' . $pachetomat->dp_gps_lat . ',' . $pachetomat->dp_gps_long . '&zoom=15&size=1800x1600&markers=icon:' . $icon . '%7C' . $pachetomat->dp_gps_lat . ',' . $pachetomat->dp_gps_long . '&key=' . $gmaps_api_key . '" />';
				} else {
					$harta = '';
				}

				return '<div class="woocommerce-message"><div>' . $message . '</div><div><hr />' . $harta . '</div></div>';
			}
		}
	}

	public function postapanduri_validate_order()
	{
		$chosen_methods = WC()->session->get('chosen_shipping_methods');
		$chosen_method = $chosen_methods[0];
		$chosen_method = explode('_', $chosen_method)[0];
		$dp_id = WC()->session->get('dp_id');

		if ($chosen_method == 'pachetomat' && !$dp_id) {
			$message = __('Ati selectat livrarea in pachetomat, insa nu ati selectat un pachetomat', 'postapanduri');
			$messageType = "error";
			if (!wc_has_notice($message, $messageType)) {
				wc_add_notice($message, $messageType);
			}
		}

		if ($chosen_method == 'pachetomat' && $dp_id && WC()->cart->shipping_total == -1) {
			$message = __('Din pacate nu s-a putut estima pretul livrarii pentru pachetomatul selectat, va rugam sa selectati alt pachetomat', 'postapanduri');
			$messageType = "error";
			if (!wc_has_notice($message, $messageType)) {
				wc_add_notice($message, $messageType);
			}
		}
	}

	public function pp_action_woocommerce_after_shipping_rate($method, $index)
	{
		$meta = $method->get_meta_data();
		$chosen_method = WC()->session->get('chosen_shipping_methods')[0];
		$chosen_method = explode('_', $chosen_method)[0];
		if ($method->method_id == 'postapanduri' && $meta['tip'] == 'pachetomat' && $chosen_method == 'pachetomat') {
			$lo = new LO();
			$gmaps_api_key = get_option('postapanduri_setari_generale')['gmaps_api_key'];
			$delivery_points = $lo->get_all_delivery_points_location_by_localitate();
			$delivery_points_states = $lo->get_all_delivery_points_states();
			$this->rcp_get_template_part('plugin-postapanduri-display', array('delivery_points_states' => $delivery_points_states, 'gmaps_api_key' => $gmaps_api_key, 'delivery_points' => json_encode($delivery_points)));
		}
	}

	function pp_action_woocommerce_checkout_update_order_review()
	{
		WC()->cart->calculate_shipping();
		return;
	}

	public function pp_clear_wc_shipping_rates_cache()
	{
		$packages = WC()->cart->get_shipping_packages();
		foreach ($packages as $key => $value) {
			$shipping_session = "shipping_for_package_$key";
			unset(WC()->session->$shipping_session);
		}
	}

	public function postapanduri_tracking_awb($order_id)
	{
		global $wpdb;
		$order = new \WC_Order($order_id);
		$table_name = $wpdb->prefix . "lo_awb";
		$awb_db = $wpdb->get_row("SELECT * from {$table_name} WHERE id_comanda = " . $order->get_id() . " and deleted = 0 order by generat desc limit 1");

		if ($awb_db && !empty($awb_db->awb)) {
			echo '<a id="postapanduri-public-tracking" target="_blank" href="https://static.livrarionline.ro/?awb=' . $awb_db->awb . '"><img src="' . plugin_dir_url(__FILE__) . '../../img/logo.png">' . sprintf(__('AWB Tracking (%s)', 'postapanduri'), $awb_db->awb) . '</a>';
		}
	}

	public function pp_disable_shipping_calc_on_cart($show_shipping)
	{
		if (is_cart()) {
			return false;
		}
		return $show_shipping;
	}

	public function process_issn()
	{
		$posted = $_POST;
		$lo = new LO();
		$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');
		$lo->setRSAKey(WC_PostaPanduri::get_setari_generale('rsa_key'));

		$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

		switch ($user_agent) {
			case "mozilla/5.0 (livrarionline.ro locker update service aes)":
				$this->run_lockers_update($posted);
				break;
			case "mozilla/5.0 (livrarionline.ro issn service)":
				$this->run_issn($posted);
				break;
			default:
				$this->run_issn($posted);
				break;
		}
	}

	private function run_issn($posted)
	{
		if (!isset($posted) || !isset($posted['F_CRYPT_MESSAGE_ISSN']) || !$posted['F_CRYPT_MESSAGE_ISSN']) {
			wp_die('F_CRYPT_MESSAGE_ISSN nu a fost trimis');
		}
		$F_CRYPT_MESSAGE_ISSN = $posted['F_CRYPT_MESSAGE_ISSN'];

		$lo = new LO();
		$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');
		$lo->setRSAKey(WC_PostaPanduri::get_setari_generale('rsa_key'));

		$issn = $lo->decrypt_ISSN($F_CRYPT_MESSAGE_ISSN); //obiect decodat din JSON in clasa LO
		if (empty($issn)) {
			wp_die('Nu am putut decripta mesajul!');
		}
		if (!isset($issn->f_order_id)) {
			wp_die('Parametrul f_order_id lipseste.');
		}
		if (!isset($issn->f_statusid)) {
			wp_die('Parametrul f_statusid lipseste.');
		}
		if (!isset($issn->f_stamp)) {
			wp_die('Parametrul f_stamp lipseste.');
		}
		if (!isset($issn->f_awb_collection)) {
			wp_die('Parametrul f_awb lipseste.');
		}

		$order = new \WC_Order($issn->f_order_id);

		$issn_order_statuses = get_option('postapanduri_setari_generale')['issn'];
		$issn_order_statuses = array_keys($issn_order_statuses);
		$matches = WC_PostaPanduri::multidimensional_search(WC_PostaPanduri::$pp_order_statuses, array('cod' => $issn->f_statusid));

		if (!empty($matches)) {
			$match = $matches[0];
			if (in_array($match, $issn_order_statuses)) {
				$status = WC_PostaPanduri::$pp_order_statuses[$match];
				$order->update_status(ltrim($match, 'wc-'));
				$raspuns_xml = '<?xml version="1.0" encoding="UTF-8" ?>';
				$raspuns_xml .= '<issn>';
				$raspuns_xml .= '<x_order_number>' . $issn->f_order_id . '</x_order_number>';
				$raspuns_xml .= '<merchServerStamp>' . date("Y-m-dTH:m:s") . '</merchServerStamp>';
				$raspuns_xml .= '<f_response_code>1</f_response_code>';
				$raspuns_xml .= '</issn>';
				header('Content-type: text/xml');
				echo $raspuns_xml;
			}
		}
		die();
	}

	// SMARTLOCKER UPDATE
	public function run_lockers_update($posted)
	{
		global $wpdb;
		$posted = file_get_contents('php://input');
		$lo = new LO();

		$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');
		$lo->setRSAKey(WC_PostaPanduri::get_setari_generale('rsa_key'));
		$lockers_data = $lo->decrypt_ISSN($posted); //obiect decodat din JSON in clasa LO
		if (is_null($lockers_data)) {
			wp_die('Nu am putut decripta payload-ul');
		}
		$login_id = $lockers_data->merchid;
		$lo_delivery_points = $lockers_data->dulap;
		$lo_dp_program = $lockers_data->zile2dulap;
		$lo_dp_exceptii = $lockers_data->exceptii_zile;

		foreach ($lo_delivery_points as $delivery_point) {
			$check_sql = "SELECT count(dp_id) AS `exists` FROM {$wpdb->prefix}lo_delivery_points WHERE dp_id = " . (int)$delivery_point->dulapid;
			$check = $wpdb->get_row($check_sql);

			if ((int)$check->exists < 1) {
				$wpdb->insert(
					"{$wpdb->prefix}lo_delivery_points",
					array(
						'dp_id'          => (int)$delivery_point->dulapid,
						'dp_denumire'    => $delivery_point->denumire,
						'dp_adresa'      => $delivery_point->adresa,
						'dp_judet'       => $delivery_point->judet,
						'dp_oras'        => $delivery_point->oras,
						'dp_tara'        => $delivery_point->tara,
						'dp_gps_lat'     => $delivery_point->latitudine,
						'dp_gps_long'    => $delivery_point->longitudine,
						'dp_tip'         => $delivery_point->tip_dulap,
						'dp_active'      => $delivery_point->active,
						'version_id'     => $delivery_point->versionid,
						'dp_temperatura' => $delivery_point->dp_temperatura,
						'dp_indicatii'   => $delivery_point->dp_indicatii,
						'termosensibil'  => (int)$delivery_point->termosensibil,
					)
				);
			} else {
				$wpdb->update(
					"{$wpdb->prefix}lo_delivery_points",
					array(
						'dp_id'          => (int)$delivery_point->dulapid,
						'dp_denumire'    => $delivery_point->denumire,
						'dp_adresa'      => $delivery_point->adresa,
						'dp_judet'       => $delivery_point->judet,
						'dp_oras'        => $delivery_point->oras,
						'dp_tara'        => $delivery_point->tara,
						'dp_gps_lat'     => $delivery_point->latitudine,
						'dp_gps_long'    => $delivery_point->longitudine,
						'dp_tip'         => $delivery_point->tip_dulap,
						'dp_active'      => $delivery_point->active,
						'version_id'     => $delivery_point->versionid,
						'dp_temperatura' => $delivery_point->dp_temperatura,
						'dp_indicatii'   => $delivery_point->dp_indicatii,
						'termosensibil'  => (int)$delivery_point->termosensibil,
					),
					array('dp_id' => (int)$delivery_point->dulapid)
				);
			}
		}

		foreach ($lo_dp_program as $program) {
			$check_sql = "SELECT count(leg_id) AS `exists` FROM {$wpdb->prefix}lo_dp_program WHERE dp_id = " . (int)$program->dulapid . " AND day_number = " . (int)$program->day_number;
			$check = $wpdb->get_row($check_sql);
			if ((int)$check->exists < 1) {
				$wpdb->insert(
					"{$wpdb->prefix}lo_dp_program",
					array(
						'dp_start_program' => $program->start_program,
						'dp_end_program'   => $program->end_program,
						'dp_id'            => (int)$program->dulapid,
						'day_active'       => (int)$program->active,
						'version_id'       => (int)$program->versionid,
						'day_number'       => (int)$program->day_number,
						'day'              => $program->day_name,
					)
				);
			} else {
				$wpdb->update(
					"{$wpdb->prefix}lo_dp_program",
					array(
						'dp_start_program' => $program->start_program,
						'dp_end_program'   => $program->end_program,
						'dp_id'            => (int)$program->dulapid,
						'day_active'       => (int)$program->active,
						'version_id'       => (int)$program->versionid,
						'day_number'       => (int)$program->day_number,
						'day'              => $program->day_name,
					),
					array(
						'dp_id'      => (int)$program->dulapid,
						'day_number' => (int)$program->day_number,
					)
				);
			}
		}

		foreach ($lo_dp_exceptii as $exceptie) {
			$check_sql = "SELECT count(leg_id) AS `exists` FROM {$wpdb->prefix}lo_dp_day_exceptions WHERE dp_id = " . (int)$exceptie->dulapid . " AND date(exception_day) = '" . $exceptie->ziua . "'";
			$check = $wpdb->get_row($check_sql);
			if ((int)$check->exists < 1) {
				$wpdb->insert(
					"{$wpdb->prefix}lo_dp_day_exceptions",
					array(
						'dp_start_program' => $exceptie->start_program,
						'dp_end_program'   => $exceptie->end_program,
						'dp_id'            => (int)$exceptie->dulapid,
						'active'           => (int)$exceptie->active,
						'version_id'       => (int)$exceptie->versionid,
						'exception_day'    => date($exceptie->ziua),
					)
				);
			} else {
				$wpdb->update(
					"{$wpdb->prefix}lo_dp_day_exceptions",
					array(
						'dp_start_program' => $exceptie->start_program,
						'dp_end_program'   => $exceptie->end_program,
						'dp_id'            => (int)$exceptie->dulapid,
						'active'           => (int)$exceptie->active,
						'version_id'       => (int)$exceptie->versionid,
						'exception_day'    => date($exceptie->ziua),
					),
					array(
						'dp_id'         => (int)$exceptie->dulapid,
						'exception_day' => date($exceptie->ziua),
					)
				);
			}
		}

		$sql = "SELECT
                        COALESCE(MAX(dp.version_id), 0) AS max_dulap_id,
                        COALESCE(MAX(dpp.version_id), 0) AS max_zile2dp,
                        COALESCE(MAX(dpe.version_id), 0) AS max_exceptii_zile
                    FROM
                        {$wpdb->prefix}lo_delivery_points dp
                        LEFT join {$wpdb->prefix}lo_dp_program dpp ON dpp.dp_id = dp.dp_id
                        LEFT join {$wpdb->prefix}lo_dp_day_exceptions dpe ON dpe.dp_id = dp.dp_id";

		$row = $wpdb->get_row($sql);

		$response['merch_id'] = (int)$login_id;
		$response['max_dulap_id'] = (int)$row->max_dulap_id;
		$response['max_zile2dp'] = (int)$row->max_zile2dp;
		$response['max_exceptii_zile'] = (int)$row->max_exceptii_zile;

		echo json_encode($response);
		die();
	}

}
