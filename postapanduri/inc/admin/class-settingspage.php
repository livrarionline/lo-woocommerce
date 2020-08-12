<?php

namespace Postapanduri\Inc\Admin;

use PostaPanduri\Inc\Libraries\LO as LO;
use PostaPanduri\Inc\Core\WC_PostaPanduri as WC_PostaPanduri;

class SettingsPage
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;
	private $message = null;
	private $type = null;

	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_plugin_page'));
		add_action('admin_init', array($this, 'page_init'));
	}


	public function importa_servicii()
	{
		$lo = new LO();
		$setari_generale = get_option('postapanduri_setari_generale');
		$lo->f_login = (int)$setari_generale['f_login'];
		$lo->setRSAKey($setari_generale['rsa_key']);
		return $lo->GetServicii()->f_servicii;

	}

	/**
	 * Add options page
	 */
	public function add_plugin_page()
	{
		// This page will be under "Settings"
		add_menu_page(
			__('Posta Panduri', 'postapanduri'), __('Posta Panduri', 'postapanduri'), 'manage_woocommerce', 'postapanduri-general', array($this, 'postapanduri_toplevel_descriere'), plugin_dir_url(__FILE__) . '../../img/pachetomat-icon.png'
		);

		add_submenu_page(
			'postapanduri-general', __('Setari generale', 'postapanduri'), __('Setari generale', 'postapanduri'), 'manage_woocommerce', 'postapanduri-setari-generale', array($this, 'postapanduri_sublevel_setari_generale')
		);
		add_submenu_page(
			'postapanduri-general', __('Setari puncte de ridicare', 'postapanduri'), __('Setari puncte de ridicare', 'postapanduri'), 'manage_woocommerce', 'postapanduri-setari-puncte-ridicare', array($this, 'postapanduri_sublevel_setari_puncte_ridicare')
		);
		add_submenu_page(
			'postapanduri-general', __('Setari curierat', 'postapanduri'), __('Setari curierat', 'postapanduri'), 'manage_woocommerce', 'postapanduri-setari-curierat', array($this, 'postapanduri_sublevel_setari_curierat')
		);
		add_submenu_page(
			'postapanduri-general', __('Setari Pachetomat', 'postapanduri'), __('Setari Pachetomat', 'postapanduri'), 'manage_woocommerce', 'postapanduri-setari-pachetomat', array($this, 'postapanduri_sublevel_setari_pachetomat')
		);
		add_submenu_page(
			'postapanduri-general', __('Lista Pachetomatelor', 'postapanduri'), __('Lista Pachetomatelor', 'postapanduri'), 'manage_woocommerce', 'postapanduri-setari-lista-pachetomate', array($this, 'postapanduri_sublevel_setari_lista_pachetomate')
		);
	}

	/**
	 * Options page callback
	 */
	public function postapanduri_toplevel_descriere()
	{
		echo '<div class="wrap">';
		echo '<h1>' . __('LivrariOnline.ro', 'postapanduri') . '</h1>';
		echo '<h2>' . __('Descriere', 'postapanduri') . '</h2>
                    <p>
                    ' . __('<a href="https://livrarionline.ro" target="_blank"><b>LivrariOnline.ro</b></a> este un sistem software as a service (SAAS) ce inglobeaza cele mai noi concepte si tehnologii pentru un management eficient al activitatii de curierat modern. Platforma web de management a serviciilor de curierat si a platilor de tip ramburs care conecteaza magazinele online din Romania cu cei mai competitivi prestatori de servicii de curierat inrolati in sistem. Dedicata proprietarilor de magazine online care doresc sa isi gestioneze si sa isi monitorizeze intr-un mod automatizat, eficient si rapid comenzile si operatiunile logistice de curierat precum si incasarile ramburs pentru comenzile de pe site.', 'postapanduri') . '
                    </p>
                    <h2>' . __('Beneficii', 'postapanduri') . '</h2>
                    <ol>
                        <li>' . __('Gestioneaza si monitorizeaza  automatizat, eficient si rapid livrarile si incasarile ramburs pentru comenzile de pe site;', 'postapanduri') . '</li>
                        <li>' . __('Preturi super competitive de la curierii parteneri LivrariOnline;', 'postapanduri') . '</li>
                        <li>' . __('Integrare rapida si gratuita pentru cele mai populare platforme de e-commerce;', 'postapanduri') . '</li>
                        <li>' . __('Click & Collect pentru SmartLockers & Statii Postale – serviciu de gestiune a livrarilor alternative la punct fix pentru comenzi online.', 'postapanduri') . '</li>
                    </ol>
                    <p>' . __('Sistemul LivrariOnline.ro are 3 componente:', 'postapanduri') . '<p>
                    <ol>
                        <li>' . __('Livrare la usa prin curier;', 'postapanduri') . '</li>
                        <li>' . __('Click&Collect Pachetomat - permite ridicarea comenzilor online de la cel mai apropiat Pachetomat;', 'postapanduri') . '</li>
                        <li>' . __('Click&Collect Ghiseu - permite ridicarea comenzilor online de la cel mai apropiat punct de ridicare pre-stabilit de la ghiseu.', 'postapanduri') . '</li>
                    </ol>';
		echo '</div>';
	}

	public function postapanduri_sublevel_setari_generale()
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'postapanduri'));
		}
		// Set class property
		$this->options = get_option('postapanduri_setari_generale');
		if (isset(get_option('postapanduri_setari_generale')['issn'])) {
			$this->options['issn'] = get_option('postapanduri_setari_generale')['issn'];
		}
		?>
        <div class="wrap">
            <h1>Setari generale</h1>
			<?php settings_errors('postapanduri_setari_generale_error'); ?>
            <form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields('postapanduri_setari_generale_group');
				do_settings_sections('postapanduri-setari-generale-admin');
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	public function postapanduri_sublevel_setari_puncte_ridicare()
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'postapanduri'));
		}
		// Set class property
		// $this->options = get_option( 'postapanduri_setari_curierat' ); -- de reactivat

		?>
        <div class="wrap">
            <h1>Setari Puncte de ridicare</h1>
            <form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields('postapanduri_setari_puncte_ridicare_group');
				do_settings_sections('postapanduri-setari-puncte_ridicare-admin');
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	public function postapanduri_sublevel_setari_curierat()
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'postapanduri'));
		}
		// Set class property
		// $this->options = get_option( 'postapanduri_setari_curierat' ); -- de reactivat

		?>
        <div class="wrap">
            <h1>Setari Curierat</h1>
            <form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields('postapanduri_setari_curierat_group');
				do_settings_sections('postapanduri-setari-curierat-admin');
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	public function postapanduri_sublevel_setari_pachetomat()
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'postapanduri'));
		}
		// Set class property
		$this->options = get_option('postapanduri_setari_pachetomat');

		?>
        <div class="wrap">
            <h1>Setari pachetomat</h1>
            <form method="post" action="options.php">
				<?php
				// This prints out all hidden setting fields
				settings_fields('postapanduri_setari_pachetomat_group');
				do_settings_sections('postapanduri-setari-pachetomat-admin');
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	public function postapanduri_sublevel_setari_lista_pachetomate()
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(__('You do not have sufficient permissions to access this page.', 'postapanduri'));
		}
		?>
        <div class="wrap">
            <h1>Lista pachetomatelor active in sistemul Dvs</h1>
            <table class="wp-list-table widefat fixed striped comments">
                <thead>
                <tr>
                    <th><b><?php echo __('ID Pachetomat', 'postapanduri'); ?></b></th>
                    <th><b><?php echo __('Denumire', 'postapanduri'); ?></b></th>
                    <th><b><?php echo __('Judet', 'postapanduri'); ?></b></th>
                    <th><b><?php echo __('Localitate', 'postapanduri'); ?></b></th>
                    <th><b><?php echo __('Orar', 'postapanduri'); ?></b></th>
                    <th><b><?php echo __('Temperatura', 'postapanduri'); ?></b></th>
                </tr>
                </thead>
                <tbody>
				<?php
				$lo = new LO();
				$pachetomate = $lo->get_all_delivery_points_location_by_judet();
				if (!empty($pachetomate)) {
					foreach ($pachetomate as $pachetomat) {
						echo '<tr>';
						echo '<td><b>' . $pachetomat->dp_id . '</b></td>';
						echo '<td><b>' . $pachetomat->dp_denumire . '</b></td>';
						echo '<td>' . $pachetomat->dp_judet . '</td>';
						echo '<td>' . $pachetomat->dp_oras . '</td>';
						echo '<td>' . $pachetomat->orar . '</td>';
						echo '<td ' . ($pachetomat->termosensibil ? 'style="color:red;font-weight:bold"' : '') . '>' . ($pachetomat->dp_temperatura ?: '-') . '&deg; C</td>';
						echo '</tr>';
					}
				} else {
					echo '<tr>';
					echo '<td colspan="6">' . __('Momentan nu exista nici un Pachetomat in sistemul Dvs. Va rugam sa verificati daca ati introdus <b>URL-ul ISSN</b> in interfata de comerciant la adresa <a href="https://comercianti.livrarionline.ro" target="_blank"><b>https://comercianti.livrarionline.ro</b></a>, sectiunea <b>Setari</b>, meniul <b>Info API</b> pentru a putea primi lista Pachetomatelor. Mai multe informatii gasiti in meniul <b>Setari generale</b> al plugin-ului PostaPanduri.', 'postapanduri') . '</td>';
					echo '</tr>';
				}
				?>
                </tbody>
            </table>
        </div>
		<?php
	}


	/**
	 * Register and add settings
	 */
	public function page_init()
	{
		register_setting(
			'postapanduri_setari_generale_group', // Option group
			'postapanduri_setari_generale', // Option name
			array($this, 'sanitize_general_settings') // Sanitize
		);

		register_setting(
			'postapanduri_setari_puncte_ridicare_group', // Option group
			'postapanduri_setari_puncte_ridicare', // Option name
			array($this, 'sanitize_puncte_ridicare_settings') // Sanitize
		);

		register_setting(
			'postapanduri_setari_curierat_group', // Option group
			'postapanduri_setari_curierat', // Option name
			array($this, 'sanitize_curierat_settings') // Sanitize
		);

		register_setting(
			'postapanduri_setari_pachetomat_group', // Option group
			'postapanduri_setari_pachetomat', // Option name
			array($this, 'sanitize_pachetomat_settings') // Sanitize
		);

		// SETARI GENERALE
		add_settings_section('postapanduri_setari_generale_section_id', __('Setarea contului de comerciant PostaPanduri', 'postapanduri'), array($this, 'print_general_section_info'), 'postapanduri-setari-generale-admin');
		add_settings_field('is_active', __('Activeaza plugin-ul PostaPanduri', 'postapanduri'), array($this, 'is_active_callback'), 'postapanduri-setari-generale-admin', 'postapanduri_setari_generale_section_id');
		add_settings_field('f_login', __('Merchant Login ID (f_login)', 'postapanduri'), array($this, 'f_login_callback'), 'postapanduri-setari-generale-admin', 'postapanduri_setari_generale_section_id');
		add_settings_field('rsa_key', __('Merchant RSA Key (rsakey)', 'postapanduri'), array($this, 'rsa_key_callback'), 'postapanduri-setari-generale-admin', 'postapanduri_setari_generale_section_id');
		add_settings_field('plateste_ramburs', __('Plateste Ramburs la comerciant', 'postapanduri'), array($this, 'plateste_ramburs_callback'), 'postapanduri-setari-generale-admin', 'postapanduri_setari_generale_section_id');
		add_settings_field('gmaps_api_key', __('Google Maps API key (necesar generarii hartii cu locatiile Pachetomatelor in procesul de comanda)', 'postapanduri'), array($this, 'gmaps_api_key_callback'), 'postapanduri-setari-generale-admin', 'postapanduri_setari_generale_section_id');
		add_settings_section('postapanduri_setari_generale_section_id2', __('ISSN - Instant Shipping Status Notification', 'postapanduri'), array($this, 'print_general_section_info2'), 'postapanduri-setari-generale-admin');
		add_settings_field('use_thermo', __('Livrez produse perisabile', 'postapanduri'), array($this, 'use_thermo_callback'), 'postapanduri-setari-generale-admin', 'postapanduri_setari_generale_section_id');

		foreach (WC_PostaPanduri::$pp_order_statuses as $key => $value) {
			add_settings_field($key, $value['cod'] . ' : ' . $value['denumire'], array($this, 'pp_order_statuses_callback'), 'postapanduri-setari-generale-admin', 'postapanduri_setari_generale_section_id2', array($key, $value));
		}

		// END SETARI GENERALE

		// SETARI PUNCTE DE RIDICARE
		add_settings_section('postapanduri_setari_puncte_ridicare_section_id', __('Setarea punctelor de ridicare', 'postapanduri'), array($this, 'print_puncte_ridicare_section_info'), 'postapanduri-setari-puncte_ridicare-admin');
		// END SETARI PUNCTE DE RIDICARE

		// SETARI CURIERAT
		add_settings_section('postapanduri_setari_curierat_section_id', __('Setarea serviciilor de curierat livrare la usa PostaPanduri', 'postapanduri'), array($this, 'print_curierat_section_info'), 'postapanduri-setari-curierat-admin');
		// END SETARI CURIERAT

		// SETARI PACHETOMAT
		add_settings_section('postapanduri_setari_pachetomat_section_id', __('Setarea serviciilor Pachetomat PostaPanduri', 'postapanduri'), array($this, 'print_pachetomat_section_info'), 'postapanduri-setari-pachetomat-admin');
		// END SETARI PACHETOMAT
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize_general_settings($input)
	{
		$this->type = 'updated';
		$this->message = 'Setarile generale au fost salvate cu succes';
		$new_input = array();

		foreach ($input as $key => $value) {
			if ($key != 'issn' && isset($value) && $value) {
				$new_input[$key] = sanitize_text_field($value);
			} elseif ($key != 'issn') {
				$this->type = 'error';
				$this->message = sprintf(__('Campul %s este obligatoriu', 'postapanduri'), $key);

			}
		}

		foreach ($input['issn'] as $key => $value) {
			if (isset($value) && $value) {
				$new_input['issn'][$key] = sanitize_text_field($value);
			}
		}

		add_settings_error(
			'postapanduri_setari_generale_error',
			esc_attr('settings_updated'),
			$this->message,
			$this->type
		);
		return $new_input;
	}

	public function sanitize_puncte_ridicare_settings($input)
	{
		$this->type = 'updated';
		$this->message = __('Setarile generale au fost salvate cu succes', 'postapanduri');
		$new_input = array();

		foreach ($input as $k => $i) {
			if (!is_array($i)) {
				$new_input[$k] = sanitize_text_field($i);
			}
			foreach ($i as $key => $value) {

				if (isset($value) && $value) {
					$new_input[$k][$key] = sanitize_text_field($value);
				} else {
					$new_input[$k][$key] = false;
				}
			}
		}

		return $new_input;
	}

	public function sanitize_curierat_settings($input)
	{
		$this->type = 'updated';
		$this->message = __('Setarile de curierat au fost salvate cu succes', 'postapanduri');
		$new_input = array();

		foreach ($input as $k => $i) {
			if (!is_array($i)) {
				$new_input[$k] = sanitize_text_field($i);
			}
			foreach ($i as $key => $value) {

				if (isset($value) && $value) {
					$new_input[$k][$key] = sanitize_text_field($value);
				} else {
					$new_input[$k][$key] = false;
				}
			}
		}

		return $new_input;
	}

	public function sanitize_pachetomat_settings($input)
	{
		$this->type = 'updated';
		$this->message = __('Setarile de pachetomat au fost salvate cu succes', 'postapanduri');
		$new_input = array();

		foreach ($input as $k => $i) {
			if (!is_array($i)) {
				$new_input[$k] = sanitize_text_field($i);
			}
			foreach ($i as $key => $value) {

				if (isset($value) && $value) {
					$new_input[$k][$key] = sanitize_text_field($value);
				} else {
					$new_input[$k][$key] = false;
				}
			}
		}

		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_general_section_info()
	{
		echo __('Va rugam sa obtineti datele din <a href="https://comercianti.livrarionline.ro/comercianti/ListAPI" target="_blank"><b>contul de comerciant</b></a>', 'postapanduri');
	}

	public function print_general_section_info2()
	{
		echo '<p>' . __('Serviciul ISSN are urmatoarele functionalitati:', 'postapanduri') . '</p>';
		echo '<ol>
                    <li>' . __('Notifica in timp real magazinul Dvs cu privire la modificarea starii livrarilor si actualizeaza starea comenzilor astfel incat acesta sa reflecte starea curenta a livrarii', 'postapanduri') . '</li>
                    <li>' . __('Notifica in timp real magazinul Dvs cu privire la noile Pachetomate adaugate in sistemul PostaPanduri si actualizeaza starea Pachetomatelor existente (orar, temperatura etc.)', 'postapanduri') . '</li>
              </ol>';
		echo '<div class="error inline">
                    ' . sprintf(__('<p>
                        <b>INFO:</b> Pentru a seta URL-ul ISSN accesati <a href="https://comercianti.livrarionline.ro" target="_blank"><b>https://comercianti.livrarionline.ro</b></a>, sectiunea <b>Info API</b>, campul <b>Url ISSN comerciant</b>. Completati adresa <b>%s/wc-api/wc_postapanduri_issn</b> si selectati Metoda ISSN comerciant <b>POST</b>. Bifati starile pentru care doriti sa fie notificat site-ul Dvs (<b>Stari livrare ISSN</b>) in contul de comerciant. Selectati din starile de mai jos cele pentru care doriti sa se actualizeze starea comenzii.
                    </p>', 'postapanduri'), get_bloginfo('url')) . '
                </div>';

		echo '<h3>' . __('Setati starea livrarii pentru care doriti sa se actualizeze starea comenzii in magazinul Dvs.', 'postapanduri') . '</h3>';
	}

	public function print_puncte_ridicare_section_info()
	{
		echo '<div>' . __('Va rugam sa definiti punctul/punctele de ridicare', 'postapanduri') . '</div>';
		echo '<div>' . __('Campurile marcate cu <b>*</b> sunt obligatorii. Dupa adaugarea unui punct de ridicare nou veti putea sa il activati si sa il setati ca fiind implicit.', 'postapanduri') . '</div><hr />';
		echo '<div id="servicii">';
		$ppsc = get_option('postapanduri_setari_puncte_ridicare');
		$dpr = isset($ppsc['default_punct_de_ridicare']) ? $ppsc['default_punct_de_ridicare'] : null;
		$judete = WC()->countries->get_shipping_country_states();
		$judete = $judete['RO'];

		if (is_array($ppsc) && !empty($ppsc)) {
			$i = 0;
			foreach ($ppsc as $data) {
				if (!is_array($data)) {
					continue;
				}
				$data = (object)$data;
				$toate_judetele_select = "<select name='postapanduri_setari_puncte_ridicare[" . $i . "][judet_punct_de_ridicare]'>";
				foreach ($judete as $key => $value) {
					$toate_judetele_select .= "<option value='" . $key . "' " . ($data->judet_punct_de_ridicare == $key ? 'selected' : '') . ">" . $value . "</option>";
				}
				$toate_judetele_select .= "</select>";

				echo "<table class='form-table adauga_serviciu_table clone_table'>
                    <tr>
                        <th scope=\"row\">" . __('Activeaza acest punct de ridicare', 'postapanduri') . "</th>
                        <td><input type='checkbox' class='activ_serviciu' name='postapanduri_setari_puncte_ridicare[" . $i . "][activ_punct_ridicare]' value='1' " . checked(1, isset($data->activ_punct_ridicare) ? $data->activ_punct_ridicare : '', false) . " /></td>
                    </tr>
                		<tr>
                			<th scope=\"row\">" . __('Denumire punct de ridicare', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_puncte_ridicare[" . $i . "][nume_punct_de_ridicare]' value='" . $data->nume_punct_de_ridicare . "' /></td>
                		</tr>
                		<tr>
                			<th scope=\"row\">" . __('Nume persoana de contact', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_puncte_ridicare[" . $i . "][nume_persoana_de_contact]' value='" . $data->nume_persoana_de_contact . "' /></td>
                		</tr>
                		<tr>
                			<th scope=\"row\">" . __('Prenume persoana de contact', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_puncte_ridicare[" . $i . "][prenume_persoana_de_contact]' value='" . $data->prenume_persoana_de_contact . "' /></td>
                		</tr>
                		<tr>
                			<th scope=\"row\">" . __('E-Mail punct de ridicare', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_puncte_ridicare[" . $i . "][email_punct_de_ridicare]' value='" . $data->email_punct_de_ridicare . "' /></td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Telefon', 'postapanduri') . "</th>
                			<td><input type='text' name='postapanduri_setari_puncte_ridicare[" . $i . "][telefon_punct_de_ridicare]' value='" . $data->telefon_punct_de_ridicare . "' /></td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Telefon mobil', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_puncte_ridicare[" . $i . "][telefon_mobil_punct_de_ridicare]' value='" . $data->telefon_mobil_punct_de_ridicare . "' /></td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Adresa punct de ridicare', 'postapanduri') . " *</th>
                			<td><textarea type='text' name='postapanduri_setari_puncte_ridicare[" . $i . "][adresa_punct_ridicare]'/>" . $data->adresa_punct_ridicare . "</textarea></td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Judet', 'postapanduri') . " *</th>
                			<td>" . $toate_judetele_select . "</td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Oras', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_puncte_ridicare[" . $i . "][oras_punct_de_ridicare]' value='" . $data->oras_punct_de_ridicare . "' /></td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Cod postal', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_puncte_ridicare[" . $i . "][cod_postal_punct_de_ridicare]' value='" . $data->cod_postal_punct_de_ridicare . "' /></td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Punct de ridicare implicit', 'postapanduri') . "</th>
                			<td><input type='radio' name='postapanduri_setari_puncte_ridicare[default_punct_de_ridicare]' value='" . $data->nume_punct_de_ridicare . "' " . (isset($dpr) && $dpr == $data->nume_punct_de_ridicare ? 'checked' : '') . " /></td>
                		</tr>
                		<tr>
                			<td colspan='2' style='text-align:left;'><span class='sterge_serviciu button-secondary'>" . __('Sterge punct de ridicare', 'postapanduri') . "</span></td>
                		</tr>
                		<tr>
                			<td colspan='2'><hr/></td>
                		</tr>
                	</table>";
				$i++;
			}
		} else {
			$toate_judetele_select = "<select name='postapanduri_setari_puncte_ridicare[0][judet_punct_de_ridicare]'>";
			foreach ($judete as $key => $value) {
				$toate_judetele_select .= "<option value='" . $key . "'>" . $value . "</option>";
			}
			$toate_judetele_select .= "</select>";
			echo "<table class='form-table adauga_serviciu_table clone_table'>
                    <tr>
                        <th scope=\"row\">" . __('Activeaza acest punct de ridicare', 'postapanduri') . "</th>
                        <td><input type='checkbox' class='activ_serviciu' name='postapanduri_setari_puncte_ridicare[0][activ_punct_ridicare]' value='1' /></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Denumire punct de ridicare', 'postapanduri') . " *</th>
                        <td><input type='text' name='postapanduri_setari_puncte_ridicare[0][nume_punct_de_ridicare]' value='' /></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Nume persoana de contact', 'postapanduri') . " *</th>
                        <td><input type='text' name='postapanduri_setari_puncte_ridicare[0][nume_persoana_de_contact]' value='' /></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Prenume persoana de contact', 'postapanduri') . " *</th>
                        <td><input type='text' name='postapanduri_setari_puncte_ridicare[0][prenume_persoana_de_contact]' value='' /></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('E-Mail punct de ridicare', 'postapanduri') . " *</th>
                        <td><input type='text' name='postapanduri_setari_puncte_ridicare[0][email_punct_de_ridicare]' value='' /></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Telefon', 'postapanduri') . "</th>
                        <td><input type='text' name='postapanduri_setari_puncte_ridicare[0][telefon_punct_de_ridicare]' value='' /></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Telefon mobil', 'postapanduri') . " *</th>
                        <td><input type='text' name='postapanduri_setari_puncte_ridicare[0][telefon_mobil_punct_de_ridicare]' value='' /></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Adresa punct de ridicare', 'postapanduri') . " *</th>
                        <td><textarea type='text' name='postapanduri_setari_puncte_ridicare[0][adresa_punct_ridicare]'/></textarea></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Judet', 'postapanduri') . " *</th>
                        <td>" . $toate_judetele_select . "</td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Oras', 'postapanduri') . " *</th>
                        <td><input type='text' name='postapanduri_setari_puncte_ridicare[0][oras_punct_de_ridicare]' value='' /></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Cod postal', 'postapanduri') . " *</th>
                        <td><input type='text' name='postapanduri_setari_puncte_ridicare[0][cod_postal_punct_de_ridicare]' value='' /></td>
                    </tr>
                    <tr>
                        <th scope=\"row\">" . __('Punct de ridicare implicit', 'postapanduri') . "</th>
                        <td><input type='radio' name='postapanduri_setari_puncte_ridicare[default_punct_de_ridicare]' value='' checked/></td>
                    </tr>

                    <tr>
                        <td colspan='2' style='text-align:left;'><span class='sterge_serviciu button-secondary'>" . __('Sterge punct de ridicare', 'postapanduri') . "</span></td>
                    </tr>
                    <tr>
                        <td colspan='2'><hr/></td>
                    </tr>
                </table>";
		}
		echo '</div>';
		echo "<span class='add_serviciu button-primary'>" . __('Adauga punct de ridicare', 'postapanduri') . "</span>";
	}

	public function print_curierat_section_info()
	{
		echo '<div>' . __('Va rugam sa obtineti serviciile disponibile din <a href="https://comercianti.livrarionline.ro/comercianti/ListAPI" target="_blank"><b>contul de comerciant</b></a>', 'postapanduri') . '</div>';
		echo '<div>' . __('Campurile marcate cu <b>*</b> sunt obligatorii. Dupa adaugarea unui serviciu nou veti putea sa il activati.', 'postapanduri') . '</div><hr />';
		echo '<div id="servicii">';
		$ppsc = get_option('postapanduri_setari_curierat');

		if (is_array($ppsc) && !empty($ppsc)) {
			$i = 0;
			foreach ($ppsc as $data) {
				$data = (object)$data;

				echo "<table class='form-table adauga_serviciu_table clone_table'>
                        <tr>
                            <th scope=\"row\">" . __('Activeaza acest serviciu de curierat', 'postapanduri') . "</th>
                            <td><input type='checkbox' class='activ_serviciu' name='postapanduri_setari_curierat[" . $i . "][activ_serviciu]' value='1' " . checked(1, isset($data->activ_serviciu) ? $data->activ_serviciu : '', false) . " /></td>
                        </tr>
                		<tr>
                			<th scope=\"row\">" . __('Denumire serviciu', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_curierat[" . $i . "][nume_serviciu]' value='" . $data->nume_serviciu . "' /></td>
                		</tr>
                		<tr>
                			<th scope=\"row\">" . __('ID Serviciu', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_curierat[" . $i . "][id_serviciu]' value='" . $data->id_serviciu . "' /></td>
                		</tr>
                		<tr>
                			<th scope=\"row\">" . __('ID Companie Shipping', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_curierat[" . $i . "][id_shipping_company]' value='" . $data->id_shipping_company . "' /></td>
                		</tr>
                		<tr>
                			<th scope=\"row\">" . __('Pret fix (nu se va estima pretul livrarii)', 'postapanduri') . "</th>
                			<td><input type='text' name='postapanduri_setari_curierat[" . $i . "][pret_fix]' value='" . $data->pret_fix . "' /></td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Modificare cost transport (+ sau -, valoric sau procentual relativ la costul fix sau estimat)', 'postapanduri') . "</th>
                			<td>
                                <select name='postapanduri_setari_curierat[" . $i . "][semn_reducere]'>
                                    <option value='P' " . (isset($data->semn_reducere) && $data->semn_reducere == 'P' ? 'selected' : '') . ">+</option>
                                    <option value='M' " . (isset($data->semn_reducere) && $data->semn_reducere == 'M' ? 'selected' : '') . ">-</option>
                                </select>
                                <input type='text' name='postapanduri_setari_curierat[" . $i . "][reducere]' value='" . $data->reducere . "' />
                                <select name='postapanduri_setari_curierat[" . $i . "][tip_reducere]'>
                                    <option value='V' " . (isset($data->tip_reducere) && $data->tip_reducere == 'V' ? 'selected' : '') . ">" . __('Valoric (RON)', 'postapanduri') . "</option>
                                    <option value='P' " . (isset($data->tip_reducere) && $data->tip_reducere == 'P' ? 'selected' : '') . ">" . __('Procentual (%)', 'postapanduri') . "</option>
                                </select>
                            </td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Cost livrare gratuit pentru cos peste', 'postapanduri') . "</th>
                			<td><input type='text' name='postapanduri_setari_curierat[" . $i . "][gratuit_peste]' value='" . $data->gratuit_peste . "' /> RON</td>
                		</tr>
                		<tr>
                			<td colspan='2' style='text-align:left;'><span class='sterge_serviciu button-secondary'>" . __('Sterge serviciu', 'postapanduri') . "</span></td>
                		</tr>
                		<tr>
                			<td colspan='2'><hr/></td>
                		</tr>
                	</table>";
				$i++;
			}
		} else {
			echo "<table class='form-table adauga_serviciu_table clone_table'>
                    <tr>
                        <th scope=\"row\">" . __('Activeaza acest serviciu de curierat', 'postapanduri') . "</th>
                        <td><input type='checkbox' class='activ_serviciu' name='postapanduri_setari_curierat[0][activ_serviciu]' value='1' /></td>
                    </tr>
            		<tr>
            			<th scope=\"row\">" . __('Denumire serviciu', 'postapanduri') . " *</th>
            			<td><input type='text' name='postapanduri_setari_curierat[0][nume_serviciu]' value='' /></td>
            		</tr>
            		<tr>
            			<th scope=\"row\">" . __('ID Serviciu', 'postapanduri') . " *</th>
            			<td><input type='text' name='postapanduri_setari_curierat[0][id_serviciu]' value='' /></td>
            		</tr>
            		<tr>
            			<th scope=\"row\">" . __('ID Companie Shipping', 'postapanduri') . " *</th>
            			<td><input type='text' name='postapanduri_setari_curierat[0][id_shipping_company]' value='' /></td>
            		</tr>
            		<tr>
            			<th scope=\"row\">" . __('Pret fix (nu se va estima pretul livrarii)', 'postapanduri') . "</th>
            			<td><input type='text' name='postapanduri_setari_curierat[0][pret_fix]' value='' /></td>
            		</tr>
                    <tr>
            			<th scope=\"row\">" . __('Modificare cost transport (+ sau -, valoric sau procentual relativ la costul fix sau estimat)', 'postapanduri') . "</th>
            			<td>
                            <select name='postapanduri_setari_curierat[0][semn_reducere]'>
                                <option value='P'>+</option>
                                <option value='M'>-</option>
                            </select>
                            <input type='text' name='postapanduri_setari_curierat[0][reducere]' value='' />
                            <select name='postapanduri_setari_curierat[0][tip_reducere]'>
                                <option value='V'>" . __('Valoric (RON)', 'postapanduri') . "</option>
                                <option value='P'>" . __('Procentual (%)', 'postapanduri') . "</option>
                            </select>
                        </td>
            		</tr>
                    <tr>
            			<th scope=\"row\">" . __('Cost livrare gratuit pentru cos peste', 'postapanduri') . "</th>
            			<td><input type='text' name='postapanduri_setari_curierat[0][gratuit_peste]' value='' /> RON</td>
            		</tr>
            		<tr>
            			<td colspan='2' style='text-align:left;'><span class='sterge_serviciu button-secondary'>" . __('Sterge serviciu', 'postapanduri') . "</span></td>
            		</tr>
            		<tr>
            			<td colspan='2'><hr/></td>
            		</tr>
            	</table>";
		}
		echo '</div>';
		echo "<span class='add_serviciu button-primary'>" . __('Adauga serviciu', 'postapanduri') . "</span>";
	}

	public function print_pachetomat_section_info()
	{
		echo '<div>' . __('Va rugam sa obtineti serviciile disponibile din <a href="https://comercianti.livrarionline.ro/comercianti/ListAPI" target="_blank"><b>contul de comerciant</b></a>', 'postapanduri') . '</div>';
		echo '<div class="error inline"><p>' . __('Serviciul de livrare in Pachetomat presupune plata in avans a contravalorii comenzii. Din acest motiv, la selectarea metodei de livrare in Pachetomat de catre client in procesul de comanda <b>va fi dezactivata metoda de plata Ramburs!</b>.', 'postapanduri') . '</p></div>';
		echo '<div>' . __('Campurile marcate cu <b>*</b> sunt obligatorii. Dupa adaugarea unui serviciu nou veti putea sa il activati.', 'postapanduri') . '</div><hr />';
		echo '<div id="servicii">';
		$ppsc = get_option('postapanduri_setari_pachetomat');

		if (is_array($ppsc) && !empty($ppsc)) {
			$i = 0;
			foreach ($ppsc as $data) {
				$data = (object)$data;
				echo "<table class='form-table adauga_serviciu_table clone_table'>
                        <tr>
                            <th scope=\"row\">" . __('Activeaza acest serviciu Pachetomat', 'postapanduri') . "</th>
                            <td><input type='checkbox' class='activ_serviciu' name='postapanduri_setari_pachetomat[" . $i . "][activ_serviciu]' value='1' " . checked(1, isset($data->activ_serviciu) ? $data->activ_serviciu : '', false) . " /></td>
                        </tr>
                		<tr>
                			<th scope=\"row\">" . __('Denumire serviciu', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_pachetomat[" . $i . "][nume_serviciu]' value='" . $data->nume_serviciu . "' /></td>
                		</tr>
                		<tr>
                			<th scope=\"row\">" . __('ID Serviciu', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_pachetomat[" . $i . "][id_serviciu]' value='" . $data->id_serviciu . "' /></td>
                		</tr>
                		<tr>
                			<th scope=\"row\">" . __('ID Companie Shipping', 'postapanduri') . " *</th>
                			<td><input type='text' name='postapanduri_setari_pachetomat[" . $i . "][id_shipping_company]' value='" . $data->id_shipping_company . "' /></td>
                		</tr>
                		<tr>
                			<th scope=\"row\">" . __('Pret fix (nu se va estima pretul livrarii)', 'postapanduri') . "</th>
                			<td><input type='text' name='postapanduri_setari_pachetomat[" . $i . "][pret_fix]' value='" . $data->pret_fix . "' /></td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Modificare cost transport (+ sau -, valoric sau procentual relativ la costul fix sau estimat)', 'postapanduri') . "</th>
                			<td>
                                <select name='postapanduri_setari_pachetomat[" . $i . "][semn_reducere]'>
                                    <option value='P' " . (isset($data->semn_reducere) && $data->semn_reducere == 'P' ? 'selected' : '') . ">+</option>
                                    <option value='M' " . (isset($data->semn_reducere) && $data->semn_reducere == 'M' ? 'selected' : '') . ">-</option>
                                </select>
                                <input type='text' name='postapanduri_setari_pachetomat[" . $i . "][reducere]' value='" . $data->reducere . "' />
                                <select name='postapanduri_setari_pachetomat[" . $i . "][tip_reducere]'>
                                    <option value='V' " . (isset($data->tip_reducere) && $data->tip_reducere == 'V' ? 'selected' : '') . ">" . __('Valoric (RON)', 'postapanduri') . "</option>
                                    <option value='P' " . (isset($data->tip_reducere) && $data->tip_reducere == 'P' ? 'selected' : '') . ">" . __('Procentual (%)', 'postapanduri') . "</option>
                                </select>
                            </td>
                		</tr>
                        <tr>
                			<th scope=\"row\">" . __('Cost livrare gratuit pentru cos peste', 'postapanduri') . "</th>
                			<td><input type='text' name='postapanduri_setari_pachetomat[" . $i . "][gratuit_peste]' value='" . $data->gratuit_peste . "' /> RON</td>
                		</tr>
                		<tr>
                			<td colspan='2' style='text-align:left;'><span class='sterge_serviciu button-secondary'>" . __('Sterge serviciu', 'postapanduri') . "</span></td>
                		</tr>
                		<tr>
                			<td colspan='2'><hr/></td>
                		</tr>
                	</table>";
				$i++;
			}
		} else {
			echo "<table class='form-table adauga_serviciu_table clone_table'>
                    <tr>
                        <th scope=\"row\">" . __('Activeaza acest serviciu Pachetomat', 'postapanduri') . "</th>
                        <td><input type='checkbox' class='activ_serviciu' name='postapanduri_setari_pachetomat[0][activ_serviciu]' value='1' /></td>
                    </tr>
            		<tr>
            			<th scope=\"row\">" . __('Denumire serviciu', 'postapanduri') . " *</th>
            			<td><input type='text' name='postapanduri_setari_pachetomat[0][nume_serviciu]' value='' /></td>
            		</tr>
            		<tr>
            			<th scope=\"row\">" . __('ID Serviciu', 'postapanduri') . " *</th>
            			<td><input type='text' name='postapanduri_setari_pachetomat[0][id_serviciu]' value='' /></td>
            		</tr>
            		<tr>
            			<th scope=\"row\">" . __('ID Companie Shipping', 'postapanduri') . " *</th>
            			<td><input type='text' name='postapanduri_setari_pachetomat[0][id_shipping_company]' value='' /></td>
            		</tr>
            		<tr>
            			<th scope=\"row\">" . __('Pret fix (nu se va estima pretul livrarii)', 'postapanduri') . "</th>
            			<td><input type='text' name='postapanduri_setari_pachetomat[0][pret_fix]' value='' /></td>
            		</tr>
                    <tr>
            			<th scope=\"row\">" . __('Modificare cost transport (+ sau -, valoric sau procentual relativ la costul fix sau estimat)', 'postapanduri') . "</th>
            			<td>
                            <select name='postapanduri_setari_pachetomat[0][semn_reducere]'>
                                <option value='P'>+</option>
                                <option value='M'>-</option>
                            </select>
                            <input type='text' name='postapanduri_setari_pachetomat[0][reducere]' value='' />
                            <select name='postapanduri_setari_pachetomat[0][tip_reducere]'>
                                <option value='V'>" . __('Valoric (RON)', 'postapanduri') . "</option>
                                <option value='P'>" . __('Procentual (%)', 'postapanduri') . "</option>
                            </select>
                        </td>
            		</tr>
                    <tr>
            			<th scope=\"row\">" . __('Cost livrare gratuit pentru cos peste', 'postapanduri') . "</th>
            			<td><input type='text' name='postapanduri_setari_pachetomat[0][gratuit_peste]' value='' /> RON</td>
            		</tr>
            		<tr>
            			<td colspan='2' style='text-align:left;'><span class='sterge_serviciu button-secondary'>" . __('Sterge serviciu', 'postapanduri') . "</span></td>
            		</tr>
            		<tr>
            			<td colspan='2'><hr/></td>
            		</tr>
            	</table>";
		}
		echo '</div>';
		echo "<span class='add_serviciu button-primary'>" . __('Adauga serviciu', 'postapanduri') . "</span>";
	}

	/**
	 * Get the settings option array and print one of its values
	 */

	public function is_active_callback()
	{
		printf('<input name="postapanduri_setari_generale[is_active]" id="is_active" type="checkbox" value="1" %s />',
			isset($this->options['is_active']) ? checked(1, esc_attr($this->options['is_active']), false) : ''
		);
	}

	public function use_thermo_callback()
	{
		printf('<input name="postapanduri_setari_generale[use_thermo]" id="use_thermo" type="checkbox" value="1" %s />',
			isset($this->options['use_thermo']) ? checked(1, esc_attr($this->options['use_thermo']), false) : ''
		);
		echo '<p class="description">' . __('Bifand aceasta optiune, clientul nu va putea selecta in procesul de comanda Pachetomatele a caror celule au temperatura mai mare de 30&deg; C. Temperatura pachetomatelor este monitorizata constant, iar in momentul in care temperatura din interiorul celulelor scade sub 30&deg; C, acestea se vor reactiva automat', 'postapanduri') . '</p>';
	}

	public function pp_order_statuses_callback($args)
	{
		printf('<input name="postapanduri_setari_generale[issn][' . $args[0] . ']" id="' . $args[0] . '" type="checkbox" value="1" %s />',
			isset($this->options['issn'][$args[0]]) ? checked(1, esc_attr($this->options['issn'][$args[0]]), false) : ''
		);
	}

	public function f_login_callback()
	{
		printf(
			'<input type="text" id="f_login" name="postapanduri_setari_generale[f_login]" value="%s" size="47"/>',
			isset($this->options['f_login']) ? esc_attr($this->options['f_login']) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function rsa_key_callback()
	{
		printf(
			'<textarea type="textarea" id="rsa_key" name="postapanduri_setari_generale[rsa_key]" rows="8" cols="50"/>%s</textarea>',
			isset($this->options['rsa_key']) ? esc_attr($this->options['rsa_key']) : ''
		);
	}

	public function plateste_ramburs_callback()
	{
		$items = array(__('Cash', 'postapanduri') => 1, __('Banca', 'postapanduri') => 2);
		//$this->options = get_option( 'postapanduri_setari_generale' );
		echo "<select id='plateste_ramburs' name='postapanduri_setari_generale[plateste_ramburs]'>";
		foreach ($items as $key => $value) {
			$selected = ($this->options['plateste_ramburs'] == $value) ? 'selected="selected"' : '';
			echo "<option value='$value' $selected>$key</option>";
		}
		echo "</select>";
	}

	public function gmaps_api_key_callback()
	{
		printf(
			'<input type="text" id="f_login" name="postapanduri_setari_generale[gmaps_api_key]" value="%s" size="47"/>',
			isset($this->options['gmaps_api_key']) ? esc_attr($this->options['gmaps_api_key']) : ''
		);
		echo '<p class="description">' . __('Pentru a obtine o cheie API pentru Google Maps trebuie sa accesati <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key"><b>aceasta adresa</b></a> si apasati pe butonul <b>GET A KEY</b>.<br />
        Activati apoi <b>Google Static Maps API</b> <a target="_blank" href="https://console.developers.google.com/apis/api/static_maps_backend"><b>la aceasta adresa.</b>', 'postapanduri') . '</a></p>';
	}
}
