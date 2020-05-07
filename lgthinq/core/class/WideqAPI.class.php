<?php
/*
 * this example use python wideq lib with Flask server and curl requests
 */

class WideqAPI {

	const TOKEN_KEY = 'jeedom_token';
	
	/**
	 * headers are the jeedom_token for authentication with python server
	 */
	private $headers = [];
	
	/**
	 * port is the jeedom variable PortServerLg
	 */
	private $portServeLg = 5025;
	
	/**
	 * optionnal timeout
	 */
	private $timeout = null;
	
	/**
	 * debug level
	 */
	private $debug = false;
	
	/*
	 * keep every requests for logging
	 */
	public static $requests = [];
	
	/*   ************************ Static Methods *************************** */
	
	/*   **********************Instance Methods ************************* */
	
	/**
	 * call to the python REST API for wideq LG lib
	 * return a json result
	 */
	private function callRestApi($cmd) {

		$time = microtime(true);
		$headersResponse = [];
		$headersLength = 0;

		$url = 'http://127.0.0.1:' . $this->portServeLg . '/' . trim($cmd, '/');
		
		$ch = curl_init();
		$hasHeaders = !empty($this->headers);
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_HEADER => $hasHeaders,
			CURLOPT_RETURNTRANSFER => true,
		]);
		if($this->timeout !== null){
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		}
		if(!empty($this->headers)){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
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
		if($this->debug)
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$curl_error = curl_error($ch);
			curl_close($ch);
			throw new Exception(__('Echec de la requête http : ', __FILE__) . $url . ' Curl error : ' . $curl_error, 404);
		}

		// for debug mode: show the request
		if($this->debug)
			$information = curl_getinfo($ch);

		curl_close($ch);

		$return = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
		if($return == null){
			$body = trim( substr( $result, $headersLength)); // remove headers
			$return = json_decode($body, true, 512, JSON_BIGINT_AS_STRING);
		}
		
		if(strpos($cmd, 'token') === 0){
			if(isset($body)){
				file_put_contents("body.token.json", $body);
			}
			file_put_contents("respone.token.json", $result);
		}
		
		// show result for debug
		$time = (microtime(true) - $time) * 1000;
		$arr = ['cmd' => $cmd, 'time' => $time, 'result' => $return, 'headers' => $this->headers];
		if($this->debug)
			$arr['info'] = $information;
		self::$requests[] = $arr;

		return $return;
	}
	
	public function __construct($args = []){
		if(isset($args['headers'])) $this->headers = $args['headers'];
		if(isset($args['port'])) $this->port = $args['port'];
		if(isset($args['debug'])) $this->debug = $args['debug'];
		//LgLog::debug('construct WideqAPI '.json_encode($args));
	}
	
	/**
	 * ping the server
	 */	
	public function ping(){
		return WideqAPI::callRestApi("ping");
	}
	
	/**
	 * get the LG gateway url
	 */	
	public function gateway($country, $language){
		return WideqAPI::callRestApi("gateway/$country/$language");
	}
	
	/**
	 * send redirect URL with token and access
	 */
	public function token($url){
		$url = urlencode($url);
		$result = WideqAPI::callRestApi("token/$url");
		if(isset($result[self::TOKEN_KEY])) {
			$this->headers = [
				self::TOKEN_KEY . ': ' . $result[self::TOKEN_KEY]
			];
		}else{
			$result['message'] = "No {self::TOKEN_KEY} ! ($url)\n";
		}

		return $result;
	}
	
	/**
	 * list of every registered devices
	 */
	public function ls(){
		return WideqAPI::callRestApi('ls');
	}
	
	/**
	 * monitor one device by id
	 */
	public function mon($device){
		return WideqAPI::callRestApi("mon/$device");
	}

	/**
	 * change log level or the python REST API
	 */
	public function changeLog($log){
		$result = WideqAPI::callRestApi("log/$log");
		return $result['result'] == 'ok';
	}
	
	/**
	 * save every tokens and config as json file
	 */
	public function save($file = null){
		if($file == null)
			return WideqAPI::callRestApi("save");
		else
			return WideqAPI::callRestApi("save/$file");
	}
	
}
