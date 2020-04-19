<?php
/*
 * this example use python wideq lib with Flask server and curl requests
 */

class WideqAPI {

	/**
	 * headers are the jeedom_token for authentication with python server
	 */
	private $headers = [];
	
	/*
	 * keep every requests for logging
	 */
	public static $requests = [];
	
	/*   ************************ Static Methods *************************** */
	
	/**
	 * call to the python REST API for wideq LG lib
	 * return a json result
	 */
	public static function callRestApi($cmd, $headers = [], $timeout = null, $debug = false) {

		$time = microtime(true);
		$headersResponse = [];
		$headersLength = 0;

		//$url = 'http://127.0.0.1:' . config::byKey('port_server', 'lgthinq', 5025) . '/' . trim($cmd, '/');
		$url = 'http://127.0.0.1:5025/' . trim($cmd, '/');
		$ch = curl_init();
		$hasHeaders = !empty($headers);
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_HEADER => $hasHeaders,
			CURLOPT_RETURNTRANSFER => true,
		]);
		if($timeout !== null){
			curl_setopt($ch, CURLOPT_TIMEOUT, $_timeout);
		}
		if(!empty($headers)){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		
		// this function is called by curl for each header received
		curl_setopt($ch, CURLOPT_HEADERFUNCTION,
		  function($curl, $header) use (&$headersResponse, &$headersLength)
		  {
			$len = strlen($header);
			$headersLength += $len;
			$header = explode(':', $header, 2);
			if (count($header) < 2) // ignore invalid headers
			  return $len;
			$headersResponse[strtolower(trim($header[0]))][] = trim($header[1]);
			return $len;
		  }
		);

		// for debug mode:
		if($debug)
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$curl_error = curl_error($ch);
			curl_close($ch);
			throw new Exception(__('Echec de la requête http : ', __FILE__) . $url . ' Curl error : ' . $curl_error, 404);
		}

		// for debug mode: show the request
		if($debug)
			$information = curl_getinfo($ch);

		curl_close($ch);

		$return = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
		if($return == null){
			$body = trim( substr( $result, $headersLength)); // remove headers
			$return = json_decode($body, true, 512, JSON_BIGINT_AS_STRING);
		}
		
		if(strpos($cmd, 'token') === 0){
			file_put_contents("body.token.json", $body);
			file_put_contents("respone.token.json", $result);
		}
		
		// show result for debug
		$time = (microtime(true) - $time) * 1000;
		$arr = ['cmd' => $cmd, 'time' => $time, 'result' => $return, 'headers' => $headers];
		if($debug)
			$arr['info'] = $information;
		self::$requests[] = $arr;

		return $return;
	}
	
	/*   **********************Instance Methods ************************* */
	
	/**
	 * get the LG gateway url
	 */	
	public function gateway($country, $language){
		$result = WideqManager::callRestApi("gateway/$country/$language");
		return isset($result['url']) ? $result['url'] : false;
	}
	
	/**
	 * send redirect URL with token and access
	 */
	public function token($url){
		$url = urlencode($url);
		$result = WideqManager::callRestApi("token/$url", null, null, true);
		if(isset($result['jeedom_token'])) {
			$this->headers = [
				'jeedom_token' => 'jeedom_token: ' . $result['jeedom_token']
			];
		}else{
			$result['message'] = "No jeedom token ! ($url)\n";
		}

		return $result;
	}
	
	/**
	 * list of every registered devices
	 */
	public function ls(){
		echo 'send LS request with headers:';
		var_dump($this->headers);
		return WideqManager::callRestApi('ls', $this->headers);
	}
	
	/**
	 * monitor one device by id
	 */
	public function mon($device){
		return WideqManager::callRestApi("mon/$device", $this->headers);
	}

	/**
	 * change log level or the python REST API
	 */
	public function changeLog($log){
		$result = WideqManager::callRestApi("log/$log");
		return $result['result'] == 'ok';
	}
	
	/**
	 * save every tokens and config as json file
	 */
	public function save($file = null){
		if($file == null)
			return WideqManager::callRestApi("save", $this->headers);
		else
			return WideqManager::callRestApi("save/$file", $this->headers);
	}
	
}
