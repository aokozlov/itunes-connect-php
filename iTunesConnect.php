<?php

/**
 * iTunesConnect
 *
 * API for getting iOS apps analytics from iTunesConnect
 *
 * @author Alexander Kozlov <aokozlov@yandex.ru>
 * @version 0.1
 */
class iTunesConnect {

	protected $https;
	private $urls = array(
		'auth1' => "https://idmsa.apple.com/appleauth/auth/signin",
		'auth2' => "https://itunesconnect.apple.com/WebObjects/iTunesConnect.woa/wa/route?noext",
		'auth3' => "https://itunesconnect.apple.com/WebObjects/iTunesConnect.woa",
		"campaign-list" =>"https://analytics.itunes.apple.com/analytics/api/v1/data/sources/campaign-list/",
	);

	/**
	 * Constructor. Initialize cURL
	 *
	 * @throws \Exception
	 */
	public function __construct() {
		$this->https = curl_init();
		if (!$this->https){
			throw new \Exception('cURL initialization failed');
		}
	}

	/**
	 * Retrieve cookies using to autenticate XHR requests to iTC
	 *
	 * @param string $account Account name
	 * @param string $password Account password
	 * @return array
	 */
	public function getAuthCookies($account, $password) {
		$userdata = array(
			"accountName"	=>$account,
			"password"		=>$password,
			"rememberMe"	=>true
		);
		$cookies = $this->get_itctx(array_merge($this->get_myacinfo($userdata), $this->get_woinst_wosid()));
		if (isset($cookies['myacinfo']) && isset($cookies['woinst']) && isset($cookies['wosid']) && isset($cookies['itctx'])){
			return $cookies;
		}
		return array();
	}

	/**
	 * Retrieve campaigns data
	 *
	 * @param mixed $appIds App IDs array
	 * @param string $sdate Start date
	 * @param string $edate End date
	 * @param array $cookies Auth cookies (from getAuthCookies)
	 * @return array
	 */
	public function getCampaignsData($appIds, $sdate, $edate, $cookies) {
		if (!is_array($appIds)) {
			$appIds = array($appIds,);
		}
		$request = array(
			"adamId"	=>$appIds,
			"measures"	=>array("pageViewCount", "units", "sales", "sessions"),
			"frequency"	=>"DAY",
			"startTime"	=>$this->formatDate($sdate),
			"endTime"	=>$this->formatDate($edate),
		);

		$response = $this->request($this->urls['campaign-list'], $request, 'POST', false, $cookies, array(
			"x-requested-by: analytics.itunes.apple.com"));

		return json_decode($response, true);
	}

	/**
	 * Format date from any valid string to iTC format
	 *
	 * @param string $date_str strtotime valid date string
	 * @return string
	 */
	private function formatDate($date_str) {
		return date('Y-m-d\TH:i:s\Z', strtotime($date_str));
	}

	/**
	 * Send request to iTC
	 *
	 * @param string $url Request URL
	 * @param array $data Request data
	 * @param array $request_type Tupe of request: POST/GET
	 * @param bool $return_headers Flag to return headers instead of body
	 * @param array $cookies Auth cookies to fulfill request
	 * @param array $more_headers Additional request headers
	 * @throws \Exception
	 */
	private function request($url, $data, $request_type = 'POST', $return_headers = true, $cookies = array(), $more_headers = array()) {
		if (!curl_setopt($this->https, CURLOPT_URL, $url)){
			throw new \Exception('cURL configuration (CURLOPT_URL) failed');
		}
		if (!curl_setopt($this->https, CURLOPT_RETURNTRANSFER, true)){
			throw new \Exception('cURL configuration (CURLOPT_RETURNTRANSFER) failed');
		}
		if (!curl_setopt($this->https, CURLOPT_ENCODING, "")){
			throw new \Exception('cURL configuration (CURLOPT_ENCODING) failed');
		}
		if (!curl_setopt($this->https, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1)){
			throw new \Exception('cURL configuration (CURLOPT_HTTP_VERSION) failed');
		}
		if (!curl_setopt($this->https, CURLOPT_MAXREDIRS, 5)){
			throw new \Exception('cURL configuration (CURLOPT_MAXREDIRS) failed');
		}
		if (!curl_setopt($this->https, CURLOPT_TIMEOUT, 30)){
			throw new \Exception('cURL configuration (CURLOPT_TIMEOUT) failed');
		}
		if (!curl_setopt($this->https, CURLOPT_SSL_VERIFYPEER, false)){
			throw new \Exception('cURL configuration (CURLOPT_SSL_VERIFYPEER) failed');
		}
		if (!curl_setopt($this->https, CURLOPT_CUSTOMREQUEST, $request_type)){
			throw new \Exception('cURL configuration (CURLOPT_CUSTOMREQUEST) failed');
		}
		if (($request_type === 'POST') && (count($data) > 0)) {
			if(!curl_setopt($this->https, CURLOPT_POSTFIELDS, json_encode($data))){
				throw new \Exception('cURL configuration (CURLOPT_POSTFIELDS) failed');
			}
		}
		if ($return_headers) {
			if(!curl_setopt($this->https, CURLOPT_HEADER, true)){
				throw new \Exception('cURL configuration (CURLOPT_HEADER) failed');
			}
		}
		else {
			if(!curl_setopt($this->https, CURLOPT_HEADER, false)){
				throw new \Exception('cURL configuration (CURLOPT_HEADER) failed');
			}
		}
		$headers = array(
			"cache-control: no-cache",
			"content-type: application/json"
		);
		if (count($cookies) > 0) {
			$headers[] = "cookie: " . $this->getCookieString($cookies);
		}
		if (count($more_headers) > 0) {
			$headers = array_merge($headers, $more_headers);
		}
		if(!curl_setopt($this->https, CURLOPT_HTTPHEADER, $headers)){
			throw new \Exception('cURL configuration (CURLOPT_HTTPHEADER) failed');
		}
		$response = curl_exec($this->https);
		curl_reset($this->https);
		return $response;
	}

	/**
	 * Make cookies string forrequest from cookies array
	 *
	 * @param array $cookies Array of cookies
	 * @return string
	 */
	private function getCookieString($cookies) {
		$res = "";
		foreach ($cookies as $key=> $val) {
			$res .= $key . "=" . $val . "; ";
		}
		return $res;
	}

	/**
	 * Parses cookies string from response
	 *
	 * @param string $response iTC API response string
	 * @return array
	 */
	private function parseCookies($response) {
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		$cookies = array();
		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}
		return $cookies;
	}

	/**
	 * Gets 'myacinfo' auth cookie from iTC
	 *
	 * @param array $auth_data Authorization data: account, pass, flag to remember user
	 * @return array
	 */
	private function get_myacinfo($auth_data) {
		$response	 = $this->request($this->urls['auth1'], $auth_data, 'POST', true);
		$cookies	 = $this->parseCookies($response);
		if (isset($cookies['myacinfo'])) {
			return array('myacinfo'=>$cookies['myacinfo']);
		}
		return array();
	}

	/**
	 * Gets 'woinst' and 'wosid' auth cookies from iTC
	 *
	 * @return array
	 */
	private function get_woinst_wosid() {
		$response	 = $this->request($this->urls['auth2'], null, 'GET', true);
		$cookies	 = $this->parseCookies($response);
		if (isset($cookies['woinst']) && isset($cookies['wosid'])) {
			return array(
				'woinst'=>$cookies['woinst'],
				'wosid'	=>$cookies['wosid'],
			);
		}

		return array();
	}

	/**
	 * Gets itctx auth cookie from iTC
	 *
	 * @param array $cookies Array containing 'myacinfo', 'woinst' and 'wosid' cookies
	 * @return array
	 */
	private function get_itctx($cookies) {
		$response	 = $this->request($this->urls['auth3'], null, 'GET', true, $cookies);
		$cooks		 = $this->parseCookies($response);
		if (isset($cooks['itctx'])) {
			$cookies['itctx'] = $cooks['itctx'];
			return $cookies;
		}
		return array();
	}

}
