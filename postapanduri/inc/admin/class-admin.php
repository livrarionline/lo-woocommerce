<?php

namespace Postapanduri\Inc\Admin;

use PostaPanduri\Inc\Libraries\LO as LO;
use PostaPanduri\Inc\Core\WC_PostaPanduri as WC_PostaPanduri;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://postapanduri.ro
 * @since      1.0.0
 *
 * @package    PostaPanduri
 * @subpackage PostaPanduri/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    PostaPanduri
 * @subpackage PostaPanduri/admin
 * @author     Adrian Lado <adrian@plationline.eu>
 */
class Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The text domain of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_text_domain The text domain of this plugin.
	 */
	private $plugin_text_domain;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 * @param string $plugin_text_domain The text domain of this plugin.
	 *
	 * @since       1.0.0
	 *
	 */
	public function __construct($plugin_name, $version, $plugin_text_domain)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_text_domain = $plugin_text_domain;

		add_action('add_meta_boxes', function () {
			// Add extra box in order page
			add_meta_box('form_generare_awb', 'PostaPanduri - AWB', array($this, 'form_generare_awb'), 'shop_order');
			wp_register_script("postapanduri_admin_ajax_script", plugin_dir_url(__FILE__) . 'js/postapanduri-admin-ajax.js', array('jquery', 'wp-i18n'), $this->version, true);
			wp_set_script_translations('postapanduri_admin_ajax_script', 'postapanduri');
			wp_localize_script('postapanduri_admin_ajax_script', 'ppaadmin', array('ajaxurl' => admin_url('admin-ajax.php')));
			wp_enqueue_script('postapanduri_admin_ajax_script');
		});
	}

	/**
	 * Register the stylesheets for the admin area.
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
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/postapanduri-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
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

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/postapanduri-admin.js', array('jquery'), $this->version, true);

	}

	public function order_status_changed($order_id, $old_status, $new_status)
	{
		if ($new_status == 'processing' || $new_status == 'cancelled') {
			$lo = new LO();
			$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');
			$lo->setRSAKey(WC_PostaPanduri::get_setari_generale('rsa_key'));
			$order = new \WC_Order($order_id);
			$pachetomat = $lo->get_delivery_point_by_id(WC_PostaPanduri::get_selected_dp($order));
			if ($new_status == 'processing') {
				$lo->plus_expectedin((int)$pachetomat->dp_id, $order_id);
			} elseif ($new_status == 'cancelled') {
				$lo->minus_expectedin((int)$pachetomat->dp_id, $order_id);
			}
		}
	}

	public function genereaza_awb()
	{
		$posted = $_POST['data'];
		parse_str($posted, $posted);

		global $wpdb;

		$lo = new LO();
		$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');
		$lo->setRSAKey(WC_PostaPanduri::get_setari_generale('rsa_key'));

		$order_id = $posted['orders_id'];
		$serviciu_id = $posted['serviciuid'];
		$punct_ridicare_id = $posted['shops'];

		$order = new \WC_Order($order_id);

		$user = get_user_by('id', $order->customer_user);

		$serviciu = WC_PostaPanduri::get_detalii_serviciu($serviciu_id, 'all');

		$f_request_awb = array();
		$colete = array();

		$f_request_awb['f_shipping_company_id'] = (int)$serviciu->id_shipping_company;
		$f_request_awb['descriere_livrare'] = $posted['descriere_livrare'];
		$f_request_awb['referinta_expeditor'] = $posted['referinta_expeditor'];
		$f_request_awb['valoare_declarata'] = (float)$posted['valoare_declarata'];
		$f_request_awb['ramburs'] = (float)$posted['ramburs'];
		$f_request_awb['asigurare_la_valoarea_declarata'] = $lo->checkboxSelected(isset($posted['asigurare_la_valoarea_declarata']) ? $posted['asigurare_la_valoarea_declarata'] : 0);
		$f_request_awb['retur_documente'] = $lo->checkboxSelected(isset($posted['retur_documente']) ? $posted['retur_documente'] : 0);
		$f_request_awb['retur_documente_bancare'] = $lo->checkboxSelected(isset($posted['retur_documente_bancare']) ? $posted['retur_documente_bancare'] : 0);
		$f_request_awb['confirmare_livrare'] = $lo->checkboxSelected(isset($posted['confirmare_livrare']) ? $posted['confirmare_livrare'] : 0);
		$f_request_awb['livrare_sambata'] = $lo->checkboxSelected(isset($posted['livrare_sambata']) ? $posted['livrare_sambata'] : 0);
		$f_request_awb['currency'] = $posted['currency'];
		$f_request_awb['currency_ramburs'] = $posted['currency_ramburs'];
		$f_request_awb['notificare_email'] = $lo->checkboxSelected(isset($posted['notificare_email']) ? $posted['notificare_email'] : 0);
		$f_request_awb['notificare_sms'] = $lo->checkboxSelected(isset($posted['notificare_sms']) ? $posted['notificare_sms'] : 0);
		$f_request_awb['cine_plateste'] = $posted['cine_plateste'];
		$f_request_awb['serviciuid'] = (int)$serviciu->id_serviciu;
		$f_request_awb['request_mpod'] = $lo->checkboxSelected(isset($posted['request_mpod']) ? $posted['request_mpod'] : 0);
		$f_request_awb['verificare_colet'] = $lo->checkboxSelected(isset($posted['verificare_colet']) ? $posted['verificare_colet'] : 0);
		$f_request_awb['orderid'] = strval($order_id);

		for ($i = 0; $i < sizeof($posted['tipcolet']); $i++) {
			$colete[] = array(
				'greutate' => (float)$posted['greutate'][$i],
				'lungime'  => (float)$posted['lungime'][$i],
				'latime'   => (float)$posted['latime'][$i],
				'inaltime' => (float)$posted['inaltime'][$i],
				'continut' => (int)$posted['continut'][$i],
				'tipcolet' => (int)$posted['tipcolet'][$i],
			);
		}

		// am setat coletele
		$f_request_awb['colete'] = $colete;

		$f_request_awb['destinatar'] = array(
			'first_name'   => $order->shipping_first_name,
			'last_name'    => $order->shipping_last_name,
			'email'        => $order->shipping_email ?: $order->billing_email,
			'phone'        => '',
			'mobile'       => $order->billing_phone,
			'lang'         => 'ro',
			'company_name' => $order->shipping_company,
			'j'            => '',
			'bank_account' => '',
			'bank_name'    => '',
			'cui'          => '',
		);


		if ($posted['tip'] != 'pachetomat') {
			$f_request_awb['shipTOaddress'] = array(
				'address1'   => $order->shipping_address_1,
				'address2'   => $order->shipping_address_2,
				'city'       => $order->shipping_city,
				'state'      => WC_PostaPanduri::get_state_name_by_code($order->shipping_state),
				'zip'        => $order->shipping_postcode,
				'country'    => ($order->shipping_country == 'RO' ? 'Romania' : ''),
				'phone'      => $order->billing_phone,
				'observatii' => $order->customer_note,
			);
		}

		$punct_ridicare = WC_PostaPanduri::get_punct_ridicare($punct_ridicare_id);

		$f_request_awb['shipFROMaddress'] = array(
			'email'        => $punct_ridicare->email_punct_de_ridicare,
			'first_name'   => $punct_ridicare->prenume_persoana_de_contact,
			'last_name'    => $punct_ridicare->nume_persoana_de_contact,
			'mobile'       => $punct_ridicare->telefon_mobil_punct_de_ridicare ?: '',
			'main_address' => $punct_ridicare->adresa_punct_ridicare,
			'city'         => $punct_ridicare->oras_punct_de_ridicare,
			'state'        => WC_PostaPanduri::get_state_name_by_code($punct_ridicare->judet_punct_de_ridicare),
			'zip'          => $punct_ridicare->cod_postal_punct_de_ridicare,
			'country'      => 'Romania',
			'phone'        => $punct_ridicare->telefon_punct_de_ridicare ?: '',
			'instructiuni' => '',
		);

		$f_request_awb['plateste_rambursul_la_comerciant'] = (int)WC_PostaPanduri::get_setari_generale('plateste_ramburs');

		if ($posted['tip'] == 'pachetomat') {
			$response_awb = $lo->GenerateAwbSmartloker($f_request_awb, WC_PostaPanduri::get_selected_dp($order), (isset($posted['marime_celula']) ? $posted['marime_celula'] : 3), $order_id);
		} else {
			$response_awb = $lo->GenerateAwb($f_request_awb);
		}

		$table_name = $wpdb->prefix . "lo_awb";

		//raspuns generare AWB
		if (isset($response_awb->status) && ($response_awb->status == 'error' || !isset($response_awb->f_awb_collection[0]))) {
			echo json_encode($response_awb);
		} else {
			$raspuns = '<p class="raspuns-colet">' . sprintf(__('Coletul trimis prin serviciul <b>%s</b> a primit AWB nr. <b>%s</b>', 'postapanduri'), $serviciu->nume_serviciu, $response_awb->f_awb_collection[0]) . '</p>';

			$wpdb->insert($table_name, array('awb' => trim($response_awb->f_awb_collection[0]), 'f_token' => $response_awb->f_token, 'id_comanda' => $order_id, 'id_serviciu' => $serviciu->id_serviciu, 'generated_awb_price' => $response_awb->f_price, 'payload' => json_encode($f_request_awb)));

			$raspuns .= '<span id="form-tracking-awb">
							<input type="hidden" name="tawb" id="tawb" value="' . $response_awb->f_awb_collection[0] . '"/>
							<button class="button" id="tracking-awb">Tracking AWB</button>
						</span>';

			$raspuns .= '<span id="form-cancel-awb">
							<input type="hidden" name="cawb" id="cawb" value="' . $response_awb->f_awb_collection[0] . '"/>
							<button class="button" id="cancel-awb">Anuleaza AWB</button>
						</span>';
			$f_request_print = array('awb' => $response_awb->f_awb_collection[0], 'f_token' => $response_awb->f_token);
			$raspuns .= $lo->PrintAwb($f_request_print, 'button', 'border-color:#00f;');

			echo json_encode(array('status' => 'success', 'message' => $raspuns));
		}
		wp_die();
	}

	public function cancel_awb()
	{
		$posted = $_POST['data'];
		parse_str($posted, $posted);
		global $wpdb;

		$lo = new LO();
		$table_name = $wpdb->prefix . "lo_awb";

		$f_request_cancel = array();

		$f_request_cancel['awb'] = trim($posted['cawb']);

		$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');
		$lo->setRSAKey(WC_PostaPanduri::get_setari_generale('rsa_key'));

		$response_cancel = $lo->CancelLivrare($f_request_cancel);

		//raspuns CANCEL LIVRARE
		if (($response_cancel->status) && $response_cancel->status == 'error') {
			echo json_encode($response_cancel);
		} else {
			if ($response_cancel->status == "success") {
				$wpdb->query('UPDATE ' . $table_name . ' set deleted=1 where awb="' . $f_request_cancel['awb'] . '"');
			}
			echo json_encode(array('status' => $response_cancel->status));
		}
		wp_die();
	}

	public function tracking_awb()
	{
		$posted = $_POST['data'];
		parse_str($posted, $posted);

		$lo = new LO();

		$f_request_tracking = array();
		$f_request_tracking['awb'] = trim($posted['tawb']);

		$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');
		$lo->setRSAKey(WC_PostaPanduri::get_setari_generale('rsa_key'));

		//tracking
		$response_tracking = $lo->Tracking($f_request_tracking);

		//raspuns TRACKING
		if (isset($response_tracking->status) && $response_tracking->status == 'error') {
			echo json_encode($response_tracking);
		} else {
			$stare_curenta = $response_tracking->f_stare_curenta;
			$istoric = $response_tracking->f_istoric;
			$raspuns = '<h3>' . __('Tracking AWB', 'postapanduri') . '</h3>';
			$raspuns .= '<div><span>' . date('d-m-Y H:i:s', strtotime($stare_curenta->stamp)) . '</span> - <span>' . $stare_curenta->stare . '</span></div>';
			foreach ($istoric as $is) {
				$raspuns .= '<div><span>' . date('d-m-Y H:i:s', strtotime($is->stamp)) . '</span> - <span>' . $is->stare . '</span></div>';
			}
			// echo $raspuns;
			echo json_encode(array('status' => 'success', 'message' => $raspuns));
		}
		wp_die();
	}

	public static function form_generare_awb($post)
	{
		global $wpdb;
		$order = new \WC_Order($post->ID);

		$lo = new LO();
		$lo->f_login = (int)WC_PostaPanduri::get_setari_generale('f_login');

		$items = $order->get_items();
		$package_weight = (float)0;
		foreach ($items as $item) {
			$product_quantity = (int)$item->get_quantity();
			$product = new \WC_Product($item->get_product_id());
			$package_weight += ((float)$product->get_weight() * $product_quantity);
		}

		$shipping_method = $order->get_shipping_methods();
		$shipping_method = array_values($shipping_method);

		if (empty($shipping_method[0])) {
			return;
		}

		$tip = $shipping_method[0]->get_meta('tip');
		$serviciu_id = $shipping_method[0]->get_meta('id_serviciu');
		$tip2 = $tip;
		if ($tip == 'pachetomat') {
			$tip2 = 'all';
		}
		$servicii = WC_PostaPanduri::get_servicii($tip2);

		$select_servicii = "<select id='serviciuid' name='serviciuid'>";

		foreach ($servicii as $value) {
			$value = (object)$value;
			$selected = false;
			$id_serviciu = $value->id_serviciu;
			if (!isset($value->activ_serviciu) || $value->activ_serviciu == 0) {
				continue;
			}
			if ($serviciu_id == $id_serviciu) {
				$selected = true;
			}
			$select_servicii .= "<option value='" . $id_serviciu . "' " . ($selected ? 'selected' : '') . " >" . $value->nume_serviciu . "</option>";
		}
		$select_servicii .= "</select>";

		$select_shops = "<select name='shops'>";
		$puncte_ridicare = WC_PostaPanduri::get_puncte_ridicare();
		$dpr = $puncte_ridicare['default_punct_de_ridicare'];
		unset($puncte_ridicare['default_punct_de_ridicare']);

		foreach ($puncte_ridicare as $value) {
			$value = (object)$value;

			$select_shops .= "<option value='" . $dpr . "' " . ($value->nume_punct_de_ridicare == $dpr ? 'selected' : '') . " >" . $value->nume_punct_de_ridicare . "</option>";
		}
		$select_shops .= "</select>";

		$table_name = $wpdb->prefix . "lo_awb";
		$awb_db = $wpdb->get_row("SELECT * from {$table_name} WHERE id_comanda = " . $order->get_id() . " and deleted = 0 order by generat desc limit 1"); // caut daca exista awb care nu este anulat

		if ($awb_db) {
			$display_form_awb = 'none';
			$serviciu = WC_PostaPanduri::get_detalii_serviciu($awb_db->id_serviciu, 'all');

			$raspuns = '<p class="raspuns-colet">Coletul trimis prin serviciul <b>' . $serviciu->nume_serviciu . '</b> a primit AWB nr. <b>' . $awb_db->awb . '</b></p>';
			$raspuns .= '<span id="form-tracking-awb">
							<input type="hidden" name="tawb" id="tawb" value="' . $awb_db->awb . '"/>
							<input type="hidden" name="f_login" value="' . (int)WC_PostaPanduri::get_setari_generale('f_login') . '" />
							<button class="button" id="tracking-awb">Tracking AWB</button>
						</span>';
			$raspuns .= '<span id="form-cancel-awb">
							<input type="hidden" name="cawb" id="cawb" value="' . $awb_db->awb . '"/>
							<input type="hidden" name="f_login" value="' . (int)WC_PostaPanduri::get_setari_generale('f_login') . '"/>
							<button class="button" id="cancel-awb">Anuleaza AWB</button>
						</span>';
			$f_request_print = array('awb' => $awb_db->awb, 'f_token' => $awb_db->f_token);
			$raspuns .= $lo->PrintAwb($f_request_print);
			echo $raspuns;
		} else {
			$display_form_awb = 'block';
		}


		echo "
			<div id='awb' style='display: " . $display_form_awb . "'>
			<table cellspacing='2' cellpadding='2' width='100%'>
				<tr>
					<td width='70%'>
						<input type='hidden' id='orders_id' name='orders_id' value='" . $order->get_id() . "'>
						<input type='hidden' id='tip' name='tip' value='" . $tip . "'>
						<table>";
		if ($tip == 'pachetomat') {
			$pachetomat = $lo->get_delivery_point_by_id(WC_PostaPanduri::get_selected_dp($order));
			$info_dp = "<p><b> " . $pachetomat->dp_id . ' - ' . $pachetomat->dp_denumire . "</b>. Adresa: " . $pachetomat->dp_adresa . ', <b>' . $pachetomat->dp_oras . ', ' . $pachetomat->dp_judet . '</b></p>';

			echo "<tr>
										<td style='text-align:right;'><label for='info_dp'>Informatii punct de ridicare</label></td>
										<td>" . $info_dp . "</td>
								</tr>";
		}
		echo "<tr>
								<td style='text-align:right;'><label for='serviciuid'>Alegeti un serviciu</label></td>
								<td>
									" . $select_servicii . "
								</td>
							</tr>
							<tr>
								<td style='text-align:right;'><label for='descriere_livrare'>Descriere livrare</label></td>
								<td><input type='text' id='descriere_livrare' name='descriere_livrare' value='" . $order->get_customer_note() . "'></td>
							</tr>
							<tr>
								<td style='text-align:right;'><label for='referinta_expeditor'>Referinta expeditor</label></td>
								<td><input type='text' id='referinta_expeditor' name='referinta_expeditor' value='" . get_bloginfo('name') . " - Comanda nr. #" . $order->get_id() . "'></td>
							</tr>
							<tr>
								<td style='text-align:right;'><label for='valoare_declarata'>Valoare declarata</label></td>
								<td><input type='text' id='valoare_declarata' name='valoare_declarata' value='" . round($order->get_total() - $order->get_shipping_total(), 2) . "'> " . $order->get_currency() . "</td>
							</tr>";

		if ($tip == 'pachetomat') {
			echo "<input type='hidden' id='ramburs' name='ramburs' value='0'><input type='hidden' id='currency' name='currency' value='" . $order->get_currency() . "'>";
		} else {
			$ramburs = $order->get_total();
			switch ($order->get_payment_method()) {
				case 'plationline':
				case 'bacs':
				case 'cheque':
				case 'paypal':
					$ramburs = 0;
					break;
			}
			echo "<tr>
									<td style='text-align:right;'><label for='ramburs'>Ramburs</label></td>
									<td>
										<input type='hidden' id='currency' name='currency' value='" . $order->get_currency() . "'>
										<input type='text' id='ramburs' name='ramburs' value='" . (float)$ramburs . "'> " . $order->get_currency() . "
									</td>
								</tr>";
		}

		echo "
							<tr>
								<td style='text-align:right;display:none'><label for='currency_ramburs'>Moneda ramburs</label></td>
								<td><input type='hidden' id='currency_ramburs' name='currency_ramburs' value='" . $order->get_currency() . "'></td>
							</tr>";
		if ($tip == 'pachetomat') {
			echo "<input type='hidden' id='cine_plateste' name='cine_plateste' value='0'>";
			echo "<tr>
									<td style='text-align:right;'><label for='marime_celula'>Marime celula</label></td>
									<td>
										<select name='marime_celula' id='marime_celula'>
											<option value='0' selected='selected' disabled='disabled'>Alege marimea celulei (lungime / latime / inaltime) (mm)</option>
											<option value='3' selected>S (498mm / 600mm / 300mm)</option>
											<option value='2'>M (498mm / 600mm / 382mm)</option>
											<option value='1'>L (440mm / 600mm / 611mm)</option>
											<option value='4'>XL (600mm / 600mm / 600mm)</option>
											</select>
									</td>
									</tr>";
		} else {
			echo "<tr>
									<td style='text-align:right;'><label for='cine_plateste'>Cine plateste</label></td>
									<td>
										<select name='cine_plateste' id='cine_plateste'>
											<option selected value='0'>Comerciant</option>
											<option value='1'>Expeditor</option>
											<option value='2'>Destinatar</option>
										</select>
									</td>
								</tr>";
		}
		echo "
							<tr>
								<td style='text-align:right;'><label for='shops'>Ridicare din</label></td>
								<td>" . $select_shops . "</td>
							</tr>
						</table>
					</td>
					<td width='50%'>
						<input type='checkbox' name='asigurare_la_valoarea_declarata' id='asigurare_la_valoarea_declarata' value='1'>Asigurare la valoarea declarata<br>
						<input type='checkbox' name='retur_documente' id='retur_documente' value='1'>Retur documente<br>
						<input type='checkbox' name='retur_documente_bancare' id='retur_documente_bancare' value='1'>Retur documente bancare<br>
						<input type='checkbox' name='confirmare_livrare' id='confirmare_livrare' value='1' >Confirmare livrare<br>
						<input type='checkbox' name='livrare_sambata' id='livrare_sambata' value='1'>Livrare sambata<br>
						<input type='checkbox' name='notificare_email' id='notificare_email' value='1' checked>Notificare email<br>
						<input type='checkbox' name='notificare_sms' id='notificare_sms' value='1' checked>Notificare sms<br>
						<input type='checkbox' name='request_mpod' id='request_mpod' value='1'>Confirmare livrare comerciant<br>
						<input type='checkbox' name='verificare_colet' id='verificare_colet' value='1'>Deschidere colet la livrare<br>
					</td>
				</tr>
			</table>
			<div id='detalii-pachete' style='border-top: 1px dashed #999; border-bottom: 1px dashed #999; margin: 5px 0 10px; padding-bottom: 10px;'>
				<h3>Detalii pachete</h3>
				<p>Numar pachete <input type='text' readonly='readonly' value='1' id='nrcolete' size='2' style='border: 1px dashed #ddd; font-size: 14px; font-weight: bold; text-align:center;'></p>
				<table id='colete' cellspacing='2' cellpadding='2' style='width:100%; text-align:center; border-collapse: collapse; margin-bottom: 5px;'>
					<tr>
						<th style='background: #eee; border-right: 2px solid #fff'>Tip pachet</th>
						<th style='background: #eee; border-right: 2px solid #fff'>Continutul</th>
						<th style='background: #eee; border-right: 2px solid #fff'>Greutatea (kg)</th>
						<th style='background: #eee; border-right: 2px solid #fff'>Lungime pachet (cm)</th>
						<th style='background: #eee; border-right: 2px solid #fff'>Latime pachet (cm)</th>
						<th style='background: #eee; border-right: 2px solid #fff'>Inaltime pachet (cm)</th>
					</tr>
					<tr>
						<td>
							<select name='tipcolet[]' style='padding: 4px 5px;'>
								<option value='1'>Plic</option>
								<option value='2' selected='selected'>Colet</option>
								<option value='3'>Palet</option>
							</select>
						</td>
						<td>
							<select name='continut[]' style='padding: 4px 5px;'>
								<option value='1'>Acte</option>
								<option value='2'>Tipizate</option>
								<option value='3'>Fragile</option>
								<option value='4' selected='selected'>Generale</option>
							</select>
						</td>
						<td>
							<input style='text-align:right; width: 50px; padding: 4px 5px;' type='text' name='greutate[]' value='" . ($package_weight ?: 1) . "'>
						</td>
						<td>
							<input style='text-align:right; width: 50px; padding: 4px 5px;' type='text' name='lungime[]' value='1'>
						</td>
						<td>
							<input style='text-align:right; width: 50px; padding: 4px 5px;' type='text' name='latime[]' value='1'>
						</td>
						<td>
							<input style='text-align:right; width: 50px; padding: 4px 5px;' type='text' name='inaltime[]' value='1'>
						</td>
					</tr>
				</table>
				<div><button type='button' class='btn btn-update button' id='adauga-pachet'>+ Adauga pachet</button></div>
			</div>
			<button type='submit' class='button-primary' id='get-awb'>Genereaza AWB »</button>
		</div>
		<div id='holder-awb-generat'></div>
		<div id='rezultat-awb'></div>
		<div id='rezultat'></div>
		";

	}

}
