<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://postapanduri.ro
 * @since      1.0.0
 *
 * @package    PostaPanduri
 * @subpackage PostaPanduri/public/wc
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

namespace PostaPanduri\Inc\Core;

use PostaPanduri\Inc\Libraries\LO as LO;


class WC_PostaPanduri extends \WC_Shipping_Method
{
	public static $pp_order_statuses;

	public function __construct($instance_id = 0)
	{
		self::$pp_order_statuses = array(
			'wc-pp-alocata'         => array('denumire' => __('PostaPanduri - Alocata curier', 'postapanduri'), 'cod' => 50),
			'wc-pp-preluata'        => array('denumire' => __('PostaPanduri - Preluata de curier de la comerciant', 'postapanduri'), 'cod' => 100),
			'wc-pp-negasit'         => array('denumire' => __('PostaPanduri - Destinatarul nu a fost gasit', 'postapanduri'), 'cod' => 250),
			'wc-pp-predata-sl'      => array('denumire' => __('PostaPanduri - Predata in Smart Locker', 'postapanduri'), 'cod' => 290),
			'wc-pp-livrata'         => array('denumire' => __('PostaPanduri - Livrata la destinatar', 'postapanduri'), 'cod' => 300),
			'wc-pp-amanata'         => array('denumire' => __('PostaPanduri - Livrare amanata', 'postapanduri'), 'cod' => 350),
			'wc-pp-gresita'         => array('denumire' => __('PostaPanduri - Destinatie gresita', 'postapanduri'), 'cod' => 500),
			'wc-pp-in-anulare'      => array('denumire' => __('PostaPanduri - In curs de anulare', 'postapanduri'), 'cod' => 550),
			'wc-pp-anulata'         => array('denumire' => __('PostaPanduri - Anulata', 'postapanduri'), 'cod' => 600),
			'wc-pp-retur'           => array('denumire' => __('PostaPanduri - Retur', 'postapanduri'), 'cod' => 650),
			'wc-pp-ramburs-achitat' => array('denumire' => __('PostaPanduri - Ramburs achitat', 'postapanduri'), 'cod' => 850),
			'wc-pp-facturata'       => array('denumire' => __('PostaPanduri - Facturata', 'postapanduri'), 'cod' => 900),
			'wc-pp-finalizata'      => array('denumire' => __('PostaPanduri - Finalizata', 'postapanduri'), 'cod' => 1000),
		);

		$this->id = 'postapanduri'; // Id for your shipping method. Should be unique.
		$this->method_title = __('PostaPanduri');  // Title shown in admin
		$this->method_description = __('PostaPanduri shipping module'); // Description shown in admin

		$this->enabled = WC_PostaPanduri::get_setari_generale('is_active') ? 'yes' : 'no'; // This can be added as an setting but for this example its forced enabled
		$this->title = __('PostaPanduri'); // This can be added as an setting but for this example its forced.

		$this->icon = plugin_dir_url(__FILE__) . 'img/logo.png';

		$this->instance_id = absint($instance_id);
		$this->supports = array(
			'shipping-zones',
		);

		$this->pp_denied_payment_methods = array('cod');
		$this->init();

		add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));

		add_filter('woocommerce_available_payment_gateways', array($this, 'pp_filter_woocommerce_available_payment_gateways'), 10, 2);
		add_filter('woocommerce_states', array($this, 'pp_RO_woocommerce_states'));
		add_filter('wc_order_statuses', array($this, 'pp_add_order_statuses'));
	}

	public function add_postapanduri($methods)
	{
		$methods['postapanduri'] = 'PostaPanduri\Inc\Core\WC_PostaPanduri';
		return $methods;
	}

	public static function get_selected_dp($order)
	{
		foreach ($order->get_meta_data() as $meta) {
			if ($meta->key == 'id_pachetomat') {
				return $meta->value;
			}
		}
	}

	public static function multidimensional_search($parents, $searched)
	{
		if (empty($searched) || empty($parents)) {
			return false;
		}

		$keys = array();

		foreach ($parents as $key => $value) {
			$exists = true;
			foreach ($searched as $skey => $svalue) {
				$exists = ($exists && isset($parents[$key][$skey]) && $parents[$key][$skey] == $svalue);
			}
			if ($exists) {
				$keys[] = $key;
			}
		}

		return $keys;
	}

	public static function get_setari_generale($index = 'all')
	{
		switch ($index) {
			case 'all':
				return get_option('postapanduri_setari_generale');
				break;
			default:
				return isset(get_option('postapanduri_setari_generale')[$index]) ? get_option('postapanduri_setari_generale')[$index] : '';
				break;
		}
	}

	public static function get_state_name_by_code($code)
	{
		if (empty($code)) {
			return false;
		}
		$judete = WC()->countries->get_shipping_country_states();
		$judete = $judete['RO'];
		return !empty($judete[$code]) ? $judete[$code] : $code;
	}

	public static function get_servicii($type = 'all')
	{
		switch ($type) {
			case 'all':
				return array_merge(get_option('postapanduri_setari_curierat') ?: array(), get_option('postapanduri_setari_pachetomat') ?: array());
				break;
			case 'curierat':
				return get_option('postapanduri_setari_curierat') ?: array();
				break;
			case 'pachetomat':
				return get_option('postapanduri_setari_pachetomat') ?: array();
				break;
			default:
				return array_merge(get_option('postapanduri_setari_curierat') ?: array(), get_option('postapanduri_setari_pachetomat') ?: array());
				break;
		}
	}

	public static function get_detalii_serviciu($id_serviciu, $type = 'curierat')
	{
		if (in_array($type, array('l', 'n'))) {
			$type = 'curierat';
		}
		if (in_array($type, array('p'))) {
			$type = 'pachetomat';
		}
		if ($type == 'curierat') {
			$servicii = get_option('postapanduri_setari_curierat')?:array();
		} elseif ($type == 'pachetomat') {
			$servicii = get_option('postapanduri_setari_pachetomat')?:array();
		} elseif ($type == 'all') {
			$servicii = array_merge(get_option('postapanduri_setari_curierat')?:array(), get_option('postapanduri_setari_pachetomat')?:array());
		}

		$index = self::multidimensional_search($servicii, array('id_serviciu' => $id_serviciu));
		if (!empty($index)) {
			return (object)$servicii[$index[0]];
		} else {
			return false;
		}
	}

	public static function get_puncte_ridicare($type = 'all')
	{
		switch ($type) {
			case 'all':
				$pr = get_option('postapanduri_setari_puncte_ridicare');
				return $pr;
				break;
			case 'default':
				$pr = get_option('postapanduri_setari_puncte_ridicare');
				$dpr = $pr['default_punct_de_ridicare'];
				$index = self::multidimensional_search($pr, array('nume_punct_de_ridicare' => $dpr, 'activ_punct_ridicare' => 1));
				if (empty($index)) {
					$index = 0;
				} else {
					$index = $index[0];
				}
				return (object)$pr[$index];
				break;
		}
	}

	public static function get_punct_ridicare($nume)
	{
		$pr = get_option('postapanduri_setari_puncte_ridicare');
		$index = self::multidimensional_search($pr, array('nume_punct_de_ridicare' => $nume, 'activ_punct_ridicare' => 1));
		if (empty($index)) {
			$index = 0;
		} else {
			$index = $index[0];
		}
		return (object)$pr[$index];
	}

	public static function recalculate_shipping_cost($serviciuid, $pret, $tip)
	{
		$serviciu = self::get_detalii_serviciu($serviciuid, $tip);
		if (is_null($pret) || $pret === false) {
			return -1;
		}
		$pret = (float)$pret;
		// GRATUIT PESTE X RON CART
		if (isset($serviciu->gratuit_peste) && $serviciu->gratuit_peste) {
			if (WC()->cart->subtotal >= $serviciu->gratuit_peste) {
				return 0;
			}
		}

		// DACA AM PRET FIX MODIFIC PRETUL ESTIMAT
		if (isset($serviciu->pret_fix) && $serviciu->pret_fix) {
			$pret = (float)$serviciu->pret_fix;
		}
		if (isset($serviciu->reducere) && isset($serviciu->semn_reducere) && isset($serviciu->tip_reducere) && $serviciu->reducere && $serviciu->semn_reducere && $serviciu->tip_reducere) {
			switch ($serviciu->tip_reducere) {
				case 'V':
					if ($serviciu->semn_reducere == 'P') {
						$pret += (float)$serviciu->reducere;
					} elseif ($serviciu->semn_reducere == 'M') {
						$pret -= (float)$serviciu->reducere;
					}
					break;
				case 'P':
					if ($serviciu->semn_reducere == 'P') {
						$pret *= (100 + (float)$serviciu->reducere) / 100;
					} elseif ($serviciu->semn_reducere == 'M') {
						$pret *= (100 - (float)$serviciu->reducere) / 100;
					}
					break;
			}
		}
		return $pret ? round($pret, 2) : 0;
	}

	public function pp_filter_woocommerce_available_payment_gateways($gateways)
	{
		if (is_checkout()) {
			$chosen_methods = WC()->session->get('chosen_shipping_methods');
			if (!empty($chosen_methods)) {
				$chosen_method = $chosen_methods[0];
				$chosen_method = explode('_', $chosen_method)[0];
				if ($chosen_method == 'pachetomat') {
					foreach ($gateways as $key => $value) {
						if (in_array($key, $this->pp_denied_payment_methods)) {
							unset($gateways[$key]);
						}
					}
				}
			}
			return $gateways;
		}
	}

	public function rcp_locate_template($template_names, $load = false, $require_once = true, $location = '../front')
	{
		$located = false;

		foreach ((array)$template_names as $template_name) {
			// Continue if template is empty
			if (empty($template_name)) {
				continue;
			}
			$template_name = ltrim($template_name, '/');

			if (file_exists(plugin_dir_path(__FILE__) . $location . '/' . 'partials/') . $template_name) {
				$located = plugin_dir_path(__FILE__) . $location . '/' . 'partials/' . $template_name;
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
		return $this->rcp_locate_template($templates, $load, false, '../front');
	}

	public function init()
	{
		// Load the settings API
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.
	}

	public function calculate_shipping($package = array())
	{
		if (empty(WC()->session->get('chosen_shipping_methods'))) {
			return false;
		}
		$dp_id = WC()->session->get('dp_id');
		$chosen_method = WC()->session->get('chosen_shipping_methods')[0];
		$method = explode('_', $chosen_method)[0];

		$this->rates = array();

		$servicii = WC_PostaPanduri::get_servicii('all');

		if (empty($servicii)) {
			return false;
		}
		$preturi = $this->estimeazaPretServicii((int)$servicii[0]['id_shipping_company'], $package);
		$preturi = json_encode($preturi);
		$preturi = json_decode($preturi, true);
		if (empty($preturi)) {
			return false;
		}

		$curierat = WC_PostaPanduri::get_servicii('curierat');
		$local = false;
		if (!empty($curierat)) {
			// daca am servicii de tip local le afisez pe acestea
			$matches = WC_PostaPanduri::multidimensional_search($preturi, array('f_tip' => 'l'));
			if (!empty($matches)) {
				foreach ($matches as $key => $value) {
					$m = WC_PostaPanduri::multidimensional_search($curierat, array('id_serviciu' => $preturi[$value]['f_serviciuid'], 'activ_serviciu' => 1));
					if (!empty($m)) {
						foreach ($m as $v) {
							$serviciu = WC_PostaPanduri::get_detalii_serviciu($curierat[$v]['id_serviciu']);
							$rate = array(
								'id'        => 'curierat_' . $this->id . '_' . $serviciu->id_serviciu . '_' . $serviciu->id_shipping_company,
								'label'     => $serviciu->nume_serviciu,
								'cost'      => WC_PostaPanduri::recalculate_shipping_cost($serviciu->id_serviciu, $preturi[$value]['f_pret'], $preturi[$value]['f_tip']),
								'taxes'     => false,
								'package'   => $package,
								'meta_data' => array('tip' => 'curierat', 'id_serviciu' => $serviciu->id_serviciu),
							);
							if ($rate['cost'] >= 0) {
								$this->add_rate($rate);
							}
							$local = true;
						}
					}
				}
			}

			$matches = WC_PostaPanduri::multidimensional_search($preturi, array('f_tip' => 'n'));
			if ($local == false && !empty($matches)) {
				foreach ($matches as $key => $value) {
					$m = WC_PostaPanduri::multidimensional_search($curierat, array('id_serviciu' => $preturi[$value]['f_serviciuid'], 'activ_serviciu' => 1));
					if (!empty($m)) {
						foreach ($m as $v) {
							$serviciu = WC_PostaPanduri::get_detalii_serviciu($curierat[$v]['id_serviciu']);
							$rate = array(
								'id'        => 'curierat_' . $this->id . '_' . $serviciu->id_serviciu . '_' . $serviciu->id_shipping_company,
								'label'     => $serviciu->nume_serviciu,
								'cost'      => WC_PostaPanduri::recalculate_shipping_cost($serviciu->id_serviciu, $preturi[$value]['f_pret'], $preturi[$value]['f_tip']),
								'taxes'     => false,
								'package'   => $package,
								'meta_data' => array('tip' => 'curierat', 'id_serviciu' => $serviciu->id_serviciu),
							);
							if ($rate['cost'] >= 0) {
								$this->add_rate($rate);
							}
						}
					}
				}
			}
		}

		$pachetomat = WC_PostaPanduri::get_servicii('pachetomat');
		if (!empty($pachetomat)) {
			// daca am servicii de tip local le afisez pe acestea
			$matches = WC_PostaPanduri::multidimensional_search($preturi, array('f_tip' => 'p'));

			if (!empty($matches)) {
				foreach ($matches as $key => $value) {

					$m = WC_PostaPanduri::multidimensional_search($pachetomat, array('id_serviciu' => $preturi[$value]['f_serviciuid'], 'activ_serviciu' => 1));

					if (!empty($m)) {
						foreach ($m as $v) {
							$serviciu = WC_PostaPanduri::get_detalii_serviciu($pachetomat[$v]['id_serviciu'], 'pachetomat');

							if (isset($dp_id) && $dp_id && $method == 'pachetomat') {
								// am selectat un pachetomat, trebuie sa fac estimare pe el
								$pret_pachetomat = $this->estimeazaPretPachetomat($dp_id, $serviciu->id_serviciu, $serviciu->id_shipping_company, $package);

								if (!is_null($pret_pachetomat)) {
									$rate = array(
										'id'        => 'pachetomat_' . $this->id . '_' . $serviciu->id_serviciu . '_' . $serviciu->id_shipping_company,
										'label'     => $serviciu->nume_serviciu,
										'cost'      => WC_PostaPanduri::recalculate_shipping_cost($serviciu->id_serviciu, $pret_pachetomat, $preturi[$value]['f_tip']),
										'taxes'     => false,
										'package'   => $package,
										'meta_data' => array('tip' => 'pachetomat', 'id_pachetomat' => WC()->session->get('dp_id'), 'id_serviciu' => $serviciu->id_serviciu),
									);
									if ($rate['cost'] >= 0) {
										$this->add_rate($rate);
									}
								}
							} else {
								$rate = array(
									'id'        => 'pachetomat_' . $this->id . '_' . $serviciu->id_serviciu . '_' . $serviciu->id_shipping_company,
									'label'     => $serviciu->nume_serviciu,
									'cost'      => WC_PostaPanduri::recalculate_shipping_cost($serviciu->id_serviciu, $preturi[$value]['f_pret'], $preturi[$value]['f_tip']),
									'taxes'     => false,
									'package'   => $package,
									'meta_data' => array('tip' => 'pachetomat', 'id_pachetomat' => WC()->session->get('dp_id'), 'id_serviciu' => $serviciu->id_serviciu),
								);
								if ($rate['cost'] >= 0) {
									$this->add_rate($rate);
								}
							}
						}
					}
				}
			}
		}
	}

	private function estimeazaPretServicii($id_shipping_company, $package)
	{
		$lo = new LO();
		$destination = $package['destination'];
		$order_total = WC()->cart->subtotal;
		$currency = get_woocommerce_currency();
		$f_request_awb = array();
		$colete = array();
		$f_request_awb['f_shipping_company_id'] = (int)$id_shipping_company;
		$f_request_awb['descriere_livrare'] = 'estimare pret ' . get_bloginfo('name');
		$f_request_awb['referinta_expeditor'] = '';
		$f_request_awb['valoare_declarata'] = (float)$order_total;
		$f_request_awb['ramburs'] = (float)0.00;
		$f_request_awb['asigurare_la_valoarea_declarata'] = false;
		$f_request_awb['retur_documente'] = false;
		$f_request_awb['retur_documente_bancare'] = false;
		$f_request_awb['confirmare_livrare'] = false;
		$f_request_awb['livrare_sambata'] = false;
		$f_request_awb['currency'] = $currency;
		$f_request_awb['currency_ramburs'] = $currency;
		$f_request_awb['notificare_email'] = false;
		$f_request_awb['notificare_sms'] = false;
		$f_request_awb['cine_plateste'] = 0;
		$f_request_awb['request_mpod'] = false;
		$f_request_awb['serviciuid'] = 0;
		$f_request_awb['verificare_colet'] = false;

		$greutate = 0;
		foreach ($package['contents'] as $item_id => $values) {
			$_product = $values['data'];
			$_product_quantity = $values['quantity'];
			$_product_weight = ((float)$_product->get_weight() ?: 1);
			$greutate += $_product_weight * $_product_quantity;
		}

		$greutate = round($greutate, 2);
		$colete[] = array(
			'greutate' => $greutate ? (float)$greutate : 1.00,
			'lungime'  => 1,
			'latime'   => 1,
			'inaltime' => 1,
			'continut' => 4,
			'tipcolet' => 2,
		);

		// Setare colete
		$f_request_awb['colete'] = $colete;

		$f_request_awb['destinatar'] = array(
			'first_name'   => '',
			'last_name'    => '',
			'email'        => '',
			'phone'        => '',
			'mobile'       => '',
			'lang'         => 'ro',
			'company_name' => '',
			'j'            => '',
			'bank_account' => '',
			'bank_name'    => '',
			'cui'          => '',
		);

		$f_request_awb['shipTOaddress'] = array(
			'address1'   => $destination['address'],
			'address2'   => $destination['address_2'],
			'city'       => $destination['city'],
			'state'      => WC_PostaPanduri::get_state_name_by_code($destination['state']),
			'zip'        => $destination['postcode'],
			'country'    => ($destination['country'] == 'RO' ? 'Romania' : ''),
			'phone'      => '',
			'observatii' => '',
		);

		$punct_ridicare = WC_PostaPanduri::get_puncte_ridicare('default');

		$f_request_awb['shipFROMaddress'] = array(
			'email'        => $punct_ridicare->email_punct_de_ridicare,
			'first_name'   => $punct_ridicare->prenume_persoana_de_contact,
			'last_name'    => $punct_ridicare->nume_persoana_de_contact,
			'phone'        => $punct_ridicare->telefon_punct_de_ridicare ?: '',
			'mobile'       => $punct_ridicare->telefon_mobil_punct_de_ridicare ?: '',
			'main_address' => $punct_ridicare->adresa_punct_ridicare,
			'city'         => $punct_ridicare->oras_punct_de_ridicare,
			'state'        => WC_PostaPanduri::get_state_name_by_code($punct_ridicare->judet_punct_de_ridicare),
			'zip'          => $punct_ridicare->cod_postal_punct_de_ridicare,
			'country'      => 'Romania',
			'instructiuni' => '',
		);

		$f_request_awb['plateste_rambursul_la_comerciant'] = (int)WC_PostaPanduri::get_setari_generale('plateste_ramburs');
		$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');
		$lo->setRSAKey(WC_PostaPanduri::get_setari_generale('rsa_key'));
		$raspuns = $lo->EstimeazaPretServicii($f_request_awb);

		if (isset($raspuns->status) && ($raspuns->status == 'error' || !isset($raspuns->f_pret[0]))) {
			return false;
		} else {
			return $raspuns;
		}
	}

	private function estimeazaPretPachetomat($dp_id, $serviciuid, $id_shipping_company, $package)
	{
		$lo = new LO();
		$order_total = WC()->cart->subtotal;
		$currency = get_woocommerce_currency();
		$f_request_awb = array();
		$colete = array();
		$f_request_awb['f_shipping_company_id'] = (int)$id_shipping_company;
		$f_request_awb['descriere_livrare'] = 'estimare pret pachetomat ' . get_bloginfo('name');
		$f_request_awb['referinta_expeditor'] = '';
		$f_request_awb['valoare_declarata'] = (float)$order_total;
		$f_request_awb['ramburs'] = (float)0.00;
		$f_request_awb['asigurare_la_valoarea_declarata'] = false;
		$f_request_awb['retur_documente'] = false;
		$f_request_awb['retur_documente_bancare'] = false;
		$f_request_awb['confirmare_livrare'] = false;
		$f_request_awb['livrare_sambata'] = false;
		$f_request_awb['currency'] = $currency;
		$f_request_awb['currency_ramburs'] = $currency;
		$f_request_awb['notificare_email'] = false;
		$f_request_awb['notificare_sms'] = false;
		$f_request_awb['cine_plateste'] = 0;
		$f_request_awb['request_mpod'] = false;
		$f_request_awb['serviciuid'] = (int)$serviciuid;
		$f_request_awb['verificare_colet'] = false;

		$greutate = 0;
		foreach ($package['contents'] as $item_id => $values) {
			$_product = $values['data'];
			$_product_quantity = $values['quantity'];
			if (!empty($_product->get_weight())) {
				$_product_weight = (float)$_product->get_weight() ?: 1;
				$greutate += $_product_weight * $_product_quantity;
			}
		}

		$greutate = round($greutate, 2);
		$colete[] = array(
			'greutate' => $greutate ? (float)$greutate : 1.00,
			'lungime'  => 1,
			'latime'   => 1,
			'inaltime' => 1,
			'continut' => 4,
			'tipcolet' => 2,
		);

		// Setare colete
		$f_request_awb['colete'] = $colete;

		$f_request_awb['destinatar'] = array(
			'first_name'   => '',
			'last_name'    => '',
			'email'        => '',
			'phone'        => '',
			'mobile'       => '',
			'lang'         => 'ro',
			'company_name' => '',
			'j'            => '',
			'bank_account' => '',
			'bank_name'    => '',
			'cui'          => '',
		);


		$punct_ridicare = WC_PostaPanduri::get_puncte_ridicare('default');

		$f_request_awb['shipFROMaddress'] = array(
			'email'        => $punct_ridicare->email_punct_de_ridicare,
			'first_name'   => $punct_ridicare->prenume_persoana_de_contact,
			'last_name'    => $punct_ridicare->nume_persoana_de_contact,
			'phone'        => $punct_ridicare->telefon_punct_de_ridicare ?: '',
			'mobile'       => $punct_ridicare->telefon_mobil_punct_de_ridicare ?: '',
			'main_address' => $punct_ridicare->adresa_punct_ridicare,
			'city'         => $punct_ridicare->oras_punct_de_ridicare,
			'state'        => WC_PostaPanduri::get_state_name_by_code($punct_ridicare->judet_punct_de_ridicare),
			'zip'          => $punct_ridicare->cod_postal_punct_de_ridicare,
			'country'      => 'Romania',
			'instructiuni' => '',
		);

		$f_request_awb['plateste_rambursul_la_comerciant'] = (int)WC_PostaPanduri::get_setari_generale('plateste_ramburs');

		$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');
		$lo->setRSAKey(WC_PostaPanduri::get_setari_generale('rsa_key'));
		$raspuns = $lo->EstimeazaPretSmartlocker($f_request_awb, $dp_id, '');  // 42 - dulapid, '10002222' - orderid

		if (isset($raspuns->status) && ($raspuns->status == 'error' || !isset($raspuns->f_pret))) {
			return false;
		} else {
			return $raspuns->f_pret;
		}
	}

	// Add to list of WC Order statuses
	public function pp_add_order_statuses($order_statuses)
	{
		$new_order_statuses = array();
		// add new order status after processing
		foreach (self::$pp_order_statuses as $key => $status) {
			$new_order_statuses[$key] = $status['denumire'];
		}
		return array_merge($new_order_statuses, $order_statuses);
	}

	public static function pp_register_shipment_status()
	{
		foreach (self::$pp_order_statuses as $key => $value) {
			register_post_status($key, array(
				'label'                     => $value['denumire'],
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop($value['denumire'] . ' <span class="count">(%s)</span>', $value['denumire'] . ' <span class="count">(%s)</span>'),
			));
		}
	}

	public function pp_RO_woocommerce_states($states)
	{
		$states['RO'] = array(
			'AB' => 'Alba',
			'AR' => 'Arad',
			'AG' => 'Arges',
			'BC' => 'Bacau',
			'BH' => 'Bihor',
			'BN' => 'Bistrita-Nasaud',
			'BT' => 'Botosani',
			'BV' => 'Brasov',
			'BR' => 'Braila',
			'B'  => 'Bucuresti',
			'BZ' => 'Buzau',
			'CS' => 'Caras-Severin',
			'CL' => 'Calarasi',
			'CJ' => 'Cluj',
			'CT' => 'Constanta',
			'CV' => 'Covasna',
			'DB' => 'Dambovita',
			'DJ' => 'Dolj',
			'GL' => 'Galati',
			'GR' => 'Giurgiu',
			'GJ' => 'Gorj',
			'HR' => 'Harghita',
			'HD' => 'Hunedoara',
			'IL' => 'Ialomita',
			'IS' => 'Iasi',
			'IF' => 'Ilfov',
			'MM' => 'Maramures',
			'MH' => 'Mehedinti',
			'MS' => 'Mures',
			'NT' => 'Neamt',
			'OT' => 'Olt',
			'PH' => 'Prahova',
			'SM' => 'Satu Mare',
			'SJ' => 'Salaj',
			'SB' => 'Sibiu',
			'SV' => 'Suceava',
			'TR' => 'Teleorman',
			'TM' => 'Timis',
			'TL' => 'Tulcea',
			'VS' => 'Vaslui',
			'VL' => 'Valcea',
			'VN' => 'Vrancea',
		);
		return $states;
	}
}
