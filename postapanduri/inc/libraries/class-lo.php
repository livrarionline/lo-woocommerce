<?php
namespace PostaPanduri\Inc\Libraries;

use PostaPanduri\Inc\Libraries\phpseclib\Crypt\AES as AES;
use PostaPanduri\Inc\Libraries\phpseclib\Crypt\RSA as RSA;
use PostaPanduri\Inc\Libraries\sylouuu\Curl\Method as Curl;

class LO
{
	//private
	private $f_request = null;
	private $f_secure = null;
	private $aes_key = null;
	private $iv = null;
	private $rsa_key = null;

	//definesc erorile standard: nu am putut comunica cu serverul, raspunsul de la server nu este de tip JSON. Restul de erori vin de la server
	private $error = array('server' => 'Nu am putut comunica cu serverul', 'notJSON' => 'Raspunsul primit de la server nu este formatat corect (JSON)');
	//public
	public $f_login = null;
	public $version = null;

	private $url_cancel_livrare = 'https://api.livrarionline.ro/Lobackend.asmx/CancelLivrare';
	private $url_returnare_livrare = 'https://api.livrarionline.ro/Lobackend.asmx/ReturnareLivrare';
	private $url_generare_awb = 'https://api.livrarionline.ro/Lobackend.asmx/GenerateAwb';
	private $url_register_awb = 'https://api.livrarionline.ro/Lobackend.asmx/RegisterAwb';
	private $url_tracking_awb = 'https://api.livrarionline.ro/Lobackend.asmx/Tracking';
	private $url_estimare_pret = 'https://estimare.livrarionline.ro/EstimarePret.asmx/EstimeazaPret';
	private $url_estimare_pret_servicii = 'https://estimare.livrarionline.ro/EstimarePret.asmx/EstimeazaPretServicii';
	private $url_locker_expectedin = 'https://smartlocker.livrarionline.ro/api/GetLockerExpectedInID';
	private $url_cancel_locker_expectedin = 'https://smartlocker.livrarionline.ro/api/CancelLockerExpectedInID';

	//////////////////////////////////////////////////////////////
	// 						METODE PUBLICE						//
	//////////////////////////////////////////////////////////////

	//setez versiunea de kit
	public function __construct()
	{
		$this->version = "LO1.3_R20180214";
		$this->iv = '285c02831e028bff';
		$this->use_thermo = isset(get_option('postapanduri_setari_generale')['use_thermo']) ? 1 : 0;
	}

	//setez cheia RSA
	public function setRSAKey($rsa_key)
	{
		$this->rsa_key = $rsa_key;
	}

	//helper pentru validarea bifarii unui checkbox si trimiterea de valori boolean catre server
	public function checkboxSelected($value)
	{
		if ($value) {
			return true;
		}
		return false;
	}

	public function encrypt_ISSN($input)
	{
		$issn_key = substr($this->rsa_key, 0, 16) . substr($this->rsa_key, -16);

		$aes = new AES();
		$aes->setIV($this->iv);
		$aes->setKey($issn_key);

		$local_rez = ($aes->encrypt($input));

		return base64_encode($local_rez);
	}

	public function decrypt_ISSN($input)
	{
		$issn_key = substr($this->rsa_key, 0, 16) . substr($this->rsa_key, -16);

		$aes = new AES();
		$aes->setIV($this->iv);
		$aes->setKey($issn_key);

		$issn = $aes->decrypt(base64_decode($input));

		return json_decode($issn);
	}

	//////////////////////////////////////////////////////////////
	// 				METODE COMUNICARE CU SERVER					//
	//////////////////////////////////////////////////////////////

	public function CancelLivrare($f_request)
	{
		return $this->LOCommunicate($f_request, $this->url_cancel_livrare);
	}

	public function ReturnareLivrare($f_request)
	{
		return $this->LOCommunicate($f_request, $this->url_returnare_livrare);
	}

	public function GenerateAwb($f_request)
	{
		return $this->LOCommunicate($f_request, $this->url_generare_awb);
	}

	public function GenerateAwbSmartloker($f_request, $delivery_point_id, $marime_celula, $order_id)
	{
		global $wpdb;
		$f_request['dulapid'] = (int)$delivery_point_id;
		$f_request['rezervationid'] = 0;
		$f_request['tipid_celula'] = (int)$marime_celula;

		$sql = "SELECT * FROM {$wpdb->prefix}lo_delivery_points where dp_id = " . $delivery_point_id;
		$row = $wpdb->get_row($sql);

		$f_request['shipTOaddress'] = array(
			'address1'   => $row->dp_adresa,
			'address2'   => '',
			'city'       => $row->dp_oras,
			'state'      => $row->dp_judet,
			'zip'        => $row->dp_cod_postal,
			'country'    => $row->dp_tara,
			'phone'      => '',
			'observatii' => '',
		);

		return $this->LOCommunicate($f_request, $this->url_generare_awb);
	}

	public function RegisterAwb($f_request)
	{
		return $this->LOCommunicate($f_request, $this->url_register_awb);
	}

	public function PrintAwb($f_request, $class = '')
	{
		return '<a class="' . $class . '" id="print-awb" href="https://api.livrarionline.ro/Lobackend_print/PrintAwb.aspx?f_login=' . $this->f_login . '&awb=' . $f_request['awb'] . '&f_token=' . $f_request['f_token'] . '" target="_blank">Click pentru print AWB</a>';
	}

	public function Tracking($f_request)
	{
		return $this->LOCommunicate($f_request, $this->url_tracking_awb);
	}

	public function EstimeazaPret($f_request)
	{
		return $this->LOCommunicate($f_request, $this->url_estimare_pret);
	}

	public function EstimeazaPretServicii($f_request)
	{
		return $this->LOCommunicate($f_request, $this->url_estimare_pret_servicii);
	}

	public function EstimeazaPretSmartlocker($f_request, $delivery_point_id, $order_id)
	{
		global $wpdb;
		$f_request['dulapid'] = (int)$delivery_point_id;
		$f_request['orderid'] = strval($order_id);

		$sql = "SELECT * FROM {$wpdb->prefix}lo_delivery_points where dp_id = " . $delivery_point_id;

		$row = $wpdb->get_row($sql);

		$f_request['shipTOaddress'] = array(
			'address1'   => $row->dp_adresa,
			'address2'   => '',
			'city'       => $row->dp_oras,
			'state'      => $row->dp_judet,
			'zip'        => $row->dp_cod_postal,
			'country'    => $row->dp_tara,
			'phone'      => '',
			'observatii' => '',
		);

		return $this->LOCommunicate($f_request, $this->url_estimare_pret);
	}

	public function getExpectedIn($f_request)
	{
		return $this->LOCommunicate($f_request, $this->url_locker_expectedin, true);
	}

	public function cancelExpectedIn($f_request)
	{
		return $this->LOCommunicate($f_request, $this->url_cancel_locker_expectedin, true);
	}

	//////////////////////////////////////////////////////////////
	// 				END METODE COMUNICARE CU SERVER				//
	//////////////////////////////////////////////////////////////

	// CAUTARE PACHETOMATE DUPA LOCALITATE, JUDET SI DENUMIRE

	public function get_all_delivery_points_states()
	{
		global $wpdb;

		$sql = "SELECT
				    distinct dp_judet as judet
				FROM
				    {$wpdb->prefix}lo_delivery_points dp
				WHERE
					dp_active > 0
				" . ($this->use_thermo ? ' and termosensibil = 0' : '') . "
				order by
				    dp.dp_judet asc
				";

		return $wpdb->get_results($sql);
	}

	public function get_all_delivery_points_location_by_state($judet)
	{
		global $wpdb;
		$sql = "SELECT
				    distinct dp_oras as oras
				FROM
				    {$wpdb->prefix}lo_delivery_points dp
				WHERE
					dp_active > 0 and dp_judet = '{$judet}'
					" . ($this->use_thermo ? ' and termosensibil = 0' : '') . "
				order by
				    dp.dp_oras asc
				";

		return $wpdb->get_results($sql);
	}

	public function get_all_delivery_points_location_by_judet($judet = '')
	{
		global $wpdb;
		$sql = "SELECT
					dp.*,
					COALESCE(group_concat(
						CASE
							WHEN p.day_active = 0 and day_sort_order > 5 THEN CONCAT('<div>', p.day, ': <b>Inchis</b>')
							WHEN p.day_active = 1 and day_sort_order > 5 THEN CONCAT('<div>', p.`day`, ': <b>', DATE_FORMAT(p.dp_start_program,'%H:%i'), '</b> - <b>', DATE_FORMAT(p.dp_end_program,'%H:%i'),'</b>')
							WHEN p.day_active = 2 and day_sort_order > 5 THEN CONCAT('<div>', p.day, ': <b>Non-Stop</b>')
							WHEN p.day_active = 0 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.day, ': <b>Inchis</b>')
							WHEN p.day_active = 1 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.`day`, ': <b>', DATE_FORMAT(p.dp_start_program,'%H:%i'), '</b> - <b>', DATE_FORMAT(p.dp_end_program,'%H:%i'),'</b>')
							WHEN p.day_active = 2 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.day, ': <b>Non-Stop</b>')
						END
						order by p.day_sort_order
						separator '</div>'
					),' - ') as orar
				FROM
					{$wpdb->prefix}lo_delivery_points dp
						LEFT JOIN
					{$wpdb->prefix}lo_dp_program p ON dp.dp_id = p.dp_id and day_sort_order > 4
				WHERE
					dp_active > 0
				" . ($judet ? ' and dp_judet = "' . $judet . '"' : '') . "
				" . ($this->use_thermo ? ' and termosensibil = 0' : '') . "
				group by
					dp.dp_id
				order by
				    dp_judet asc, dp_oras asc, dp.dp_denumire asc, dp.dp_active asc
				";

		return $wpdb->get_results($sql);
	}

	public function get_all_delivery_points_location_by_localitate($oras = '')
	{
		global $wpdb;
		$sql = "SELECT
					dp.*,
					COALESCE(group_concat(
						CASE
							WHEN p.day_active = 0 and day_sort_order > 5 THEN CONCAT('<div>', p.day, ': <b>Inchis</b>')
							WHEN p.day_active = 1 and day_sort_order > 5 THEN CONCAT('<div>', p.`day`, ': <b>', DATE_FORMAT(p.dp_start_program,'%H:%i'), '</b> - <b>', DATE_FORMAT(p.dp_end_program,'%H:%i'),'</b>')
							WHEN p.day_active = 2 and day_sort_order > 5 THEN CONCAT('<div>', p.day, ': <b>Non-Stop</b>')
							WHEN p.day_active = 0 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.day, ': <b>Inchis</b>')
							WHEN p.day_active = 1 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.`day`, ': <b>', DATE_FORMAT(p.dp_start_program,'%H:%i'), '</b> - <b>', DATE_FORMAT(p.dp_end_program,'%H:%i'),'</b>')
							WHEN p.day_active = 2 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.day, ': <b>Non-Stop</b>')
						END
						order by p.day_sort_order
						separator '</div>'
					),' - ') as orar
				FROM
					{$wpdb->prefix}lo_delivery_points dp
						LEFT JOIN
					{$wpdb->prefix}lo_dp_program p ON dp.dp_id = p.dp_id and day_sort_order > 4
				WHERE
					dp_active > 0
				" . ($oras ? ' and dp_oras = "' . $oras . '"' : '') . "
				" . ($this->use_thermo ? ' and termosensibil = 0' : '') . "
				group by
					dp.dp_id
				order by
				    dp.dp_active asc, dp.dp_denumire asc
				";

		return $wpdb->get_results($sql);
	}

	// AFISARE INFORMATII DESPRE SMARTLOCKER (adresa, orar) dupa selectarea smartlocker-ului din lista de pachetomate disponibile
	public function get_delivery_point_by_id($delivery_point_id)
	{
		global $wpdb;
		$sql = "SELECT
					dp.*,
					COALESCE(group_concat(
						CASE
							WHEN p.day_active = 0 and day_sort_order > 5 THEN CONCAT('<div>', p.day, ': <b>Inchis</b>')
							WHEN p.day_active = 1 and day_sort_order > 5 THEN CONCAT('<div>', p.`day`, ': <b>', DATE_FORMAT(p.dp_start_program,'%H:%i'), '</b> - <b>', DATE_FORMAT(p.dp_end_program,'%H:%i'),'</b>')
							WHEN p.day_active = 2 and day_sort_order > 5 THEN CONCAT('<div>', p.day, ': <b>Non-Stop</b>')
							WHEN p.day_active = 0 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.day, ': <b>Inchis</b>')
							WHEN p.day_active = 1 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.`day`, ': <b>', DATE_FORMAT(p.dp_start_program,'%H:%i'), '</b> - <b>', DATE_FORMAT(p.dp_end_program,'%H:%i'),'</b>')
							WHEN p.day_active = 2 and day_sort_order = 5 THEN CONCAT('<div>Luni - ', p.day, ': <b>Non-Stop</b>')
						END
						order by p.day_sort_order
						separator '</div>'
					),' - ') as orar
				FROM
				    {$wpdb->prefix}lo_delivery_points dp
				        LEFT JOIN
				    {$wpdb->prefix}lo_dp_program p ON dp.dp_id = p.dp_id
				WHERE
					dp.dp_id = {$delivery_point_id}
				group by
					dp.dp_id
				order by
				    dp.dp_active desc, dp.dp_id asc
				";

		return $wpdb->get_row($sql);
	}
	// END AFISARE INFORMATII DESPRE SMARTLOCKER (adresa, orar) dupa selectarea smartlocker-ului din lista de pachetomate disponibile

	// METODA INCREMENTARE EXPECTEDIN
	public function plus_expectedin($delivery_point_id, $orderid)
	{
		$f_request_expected_in = array();
		$f_request_expected_in['f_action'] = 3;
		$f_request_expected_in['f_orderid'] = strval($orderid);
		$f_request_expected_in['f_lockerid'] = $delivery_point_id;
		$this->getExpectedIn($f_request_expected_in);
	}
	// END METODA INCREMENTARE EXPECTEDIN

	// METODA SCADERE EXPECTEDIN
	public function minus_expectedin($delivery_point_id, $orderid)
	{
		$f_request_expected_in = array();
		$f_request_expected_in['f_action'] = 8;
		$f_request_expected_in['f_orderid'] = strval($orderid);
		$f_request_expected_in['f_lockerid'] = $delivery_point_id;
		$this->cancelExpectedIn($f_request_expected_in);
	}
	// END METODA SCADERE EXPECTEDIN

	//////////////////////////////////////////////////////////////
	// 					END METODE PUBLICE						//
	//////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////
	// 						METODE PRIVATE						//
	//////////////////////////////////////////////////////////////

	//criptez f_request cu AES
	private function AESEnc()
	{
		$this->aes_key = md5(uniqid());

		$aes = new AES();
		$aes->setIV($this->iv);
		$aes->setKey($this->aes_key);

		$this->f_request = bin2hex(base64_encode($aes->encrypt($this->f_request)));
	}

	//criptez cheia AES cu RSA
	private function RSAEnc()
	{
		$rsa = new RSA();
		$rsa->loadKey($this->rsa_key);
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

		$this->f_secure = base64_encode($rsa->encrypt($this->aes_key));
	}

	//setez f_request, criptez f_request cu AES si cheia AES cu RSA
	private function setFRequest($f_request)
	{
		$this->f_request = json_encode($f_request);

		$this->AESEnc();
		$this->RSAEnc();
	}

	//construiesc JSON ce va fi trimis catre server
	private function createJSON($loapi = false)
	{
		$request = array();
		$request['f_login'] = $this->f_login;
		$request['f_request'] = $this->f_request;
		$request['f_secure'] = $this->f_secure;

		if (!$loapi) {
			return json_encode(array('loapi' => $request));
		} else {
			return json_encode($request);
		}
	}

	//metoda pentru verificarea daca un string este JSON - folosit la primirea raspunsului de la server
	private function isJSON($string)
	{
		if (is_object(json_decode($string))) {
			return true;
		}
		return false;
	}

	//metoda pentru verificarea raspunsului obtinut de la server. O voi apela cand primesc raspunsul de la server
	private function processResponse($response, $loapi = false)
	{
		//daca nu primesc raspuns de la server
		if ($response == false) {
			return (object)array('status' => 'error', 'message' => $this->error['server']);
		} else {
			//verific daca raspunsul este de tip JSON
			if ($this->isJSON($response)) {
				$response = json_decode($response);
				if (!$loapi) {
					return $response->loapi;
				} else {
					return $response;
				}
			} else {
				return (object)array('status' => 'error', 'message' => $this->error['notJSON']);
			}
		}
	}

	//metoda comunicare cu server LO
	private function LOCommunicate($f_request, $urltopost, $loapi = false)
	{
		$this->setFRequest($f_request);

		$cc = new Curl\Post(
			$urltopost,
			array(
				'data' => array('loapijson' => $this->createJSON($loapi)),
			)
		);
		$response = $cc->send();

		return $this->processResponse($response->getResponse(), $loapi);
	}

	//////////////////////////////////////////////////////////////
	// 						END METODE PRIVATE					//
	//////////////////////////////////////////////////////////////
}

?>
