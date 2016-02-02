<?php

class iTunesConnect {

	protected $https;
	private $AUTH_URL1		 = "https://idmsa.apple.com/appleauth/auth/signin";
	private $AUTH_URL2		 = "https://itunesconnect.apple.com/WebObjects/iTunesConnect.woa/wa/route?noext";
	private $AUTH_URL3		 = "https://itunesconnect.apple.com/WebObjects/iTunesConnect.woa";
	private $CAMPAIGNS_URL	 = "https://analytics.itunes.apple.com/analytics/api/v1/data/sources/campaign-list/";

	public function __construct() {
		$this->https = curl_init();
	}

	public function getAuthCookies($account, $password) {
		$userdata = array(
			"accountName"	=>$account,
			"password"		=>$password,
			"rememberMe"	=>true
		);
		return $this->get_itctx(array_merge($this->get_myacinfo($userdata), $this->get_woinst_wosid()));
	}

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

		$response = $this->request($this->CAMPAIGNS_URL, $request, 'POST', false, $cookies, array(
			"x-requested-by: analytics.itunes.apple.com"));

		return json_decode($response, true);
	}

	private function formatDate($date_str) {
		return date('Y-m-d\TH:i:s\Z', strtotime($date_str));
	}

	private function request($url, $data, $request_type = 'POST', $return_headers = true, $cookies = array(), $more_headers = array()) {
		curl_setopt($this->https, CURLOPT_URL, $url);
		curl_setopt($this->https, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->https, CURLOPT_ENCODING, "");
		curl_setopt($this->https, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($this->https, CURLOPT_MAXREDIRS, 5);
		curl_setopt($this->https, CURLOPT_TIMEOUT, 30);
		curl_setopt($this->https, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->https, CURLOPT_CUSTOMREQUEST, $request_type);
		if (($request_type === 'POST') && (count($data) > 0)) {
			curl_setopt($this->https, CURLOPT_POSTFIELDS, json_encode($data));
		}
		if ($return_headers) {
			curl_setopt($this->https, CURLOPT_HEADER, true);
		}
		else {
			curl_setopt($this->https, CURLOPT_HEADER, false);
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
		curl_setopt($this->https, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($this->https);
		curl_reset($this->https);
		return $response;
	}

	private function getCookieString($cookies) {
		$res = "";
		foreach ($cookies as $key=> $val) {
			$res .= $key . "=" . $val . "; ";
		}
		return $res;
	}

	private function parseCookies($response) {
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		$cookies = array();
		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}
		return $cookies;
	}

	private function get_myacinfo($auth_data) {
		$response	 = $this->request($this->AUTH_URL1, $auth_data, 'POST', true);
		$cookies	 = $this->parseCookies($response);
		if (isset($cookies['myacinfo'])) {
			return array('myacinfo'=>$cookies['myacinfo']);
		}
		return array();
	}

	private function get_woinst_wosid() {
		$response	 = $this->request($this->AUTH_URL2, null, 'GET', true);
		$cookies	 = $this->parseCookies($response);
		if (isset($cookies['woinst']) && isset($cookies['wosid'])) {
			return array(
				'woinst'=>$cookies['woinst'],
				'wosid'	=>$cookies['wosid'],
			);
		}

		return array();
	}

	private function get_itctx($cookies) {
		$response	 = $this->request($this->AUTH_URL3, null, 'GET', true, $cookies);
		$cooks		 = $this->parseCookies($response);
		if (isset($cooks['itctx'])) {
			$cookies['itctx'] = $cooks['itctx'];
			return $cookies;
		}
		return array();
	}

}
