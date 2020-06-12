<?php
/*
 * this example use python wideq lib with Flask server and curl requests
 */

class LgApiException extends \Exception {}

class WideqAPI {

	const TOKEN_KEY = 'jeedom_token';

	/**
	 * headers are the jeedom_token for authentication with python server
	 */
	private $headers = [];

	/**
	 * port is the jeedom variable PortServerLg
	 */
	private $port = 5025;

	/**
	 * url is the jeedom variable UrlServerLg
	 */
	private $url = 'http://127.0.0.1';

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

		$url = $this->url . ':' . $this->port . '/' . trim($cmd, '/');

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
		// for debug mode: show the request
		if($this->debug)
			$information = curl_getinfo($ch);

		$return = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
		if($return == null){
			$body = trim( substr( $result, $headersLength)); // remove headers
			$return = json_decode($body, true, 512, JSON_BIGINT_AS_STRING);
		}

		$err = curl_errno($ch); // technical error
		if($err) {
			$curl_error = curl_error($ch);
		}else if(isset($return['state']) && $return['state'] == 'error') { // check functionnal error
			$err = isset($return['code']) ? $return['code'] : 500;
			$curl_error = isset($return['result']) ? json_encode($return['result']) : 'Functionnal Error 500';
		}

		curl_close($ch);

		if(isset($return['result'])){
			$return = $return['result'];
		}

		// mock response
		// $filename = '../../test/mock/' . substr( str_replace(['.', '\\', '/', '?', ':', '=', '&'], '_', urldecode($cmd)), 0, 20);
		// file_put_contents($filename.'.json', json_encode( $return, JSON_PRETTY_PRINT));
		// file_put_contents($filename.'.txt', $result);
		// for TEST only

		// show result for debug
		$time = (microtime(true) - $time) * 1000;
		$arr = ['cmd' => $cmd, 'time' => $time, 'result' => $return, 'headers' => $this->headers];
		if($this->debug)
			$arr['info'] = $information;
		self::$requests[] = $arr;

		if ($err) {
			throw new LgApiException('Echec de la requête http : ' . $url . ' Curl error : ' . $curl_error, $err);
		}

		return $return;
	}

	public function __construct($args = []){
		if(isset($args['headers'])) $this->headers = $args['headers'];
		if(isset($args['port'])) $this->port = $args['port'];
		if(isset($args['debug'])) $this->debug = $args['debug'];
		if(isset($args['url'])) $this->url = $args['url'];
	}

	/**
	 * ping the server
	 */
	public function ping(){
		return self::callRestApi("ping");
	}

	/**
	 * get the LG gateway url
	 */
	public function gateway($country, $language){
		return self::callRestApi("gateway/$country/$language");
	}

	/**
	 * send redirect URL with token and access
	 */
	public function token($url){
		$url = urlencode($url);
		$result = self::callRestApi("token/$url");
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
	 * list of every registered devices, keys by id.
	 */
	public function ls(){
		$arr = self::callRestApi('ls');
		$return = [];
		foreach($arr as $key => $obj){
			if(isset($obj['id']))
				$return[$obj['id']] = $obj;
			else
				$return[] = $obj; // missing id ?
		}
		return $return;
	}

	/**
	 * monitor one device by id
	 */
	public function mon($device){
		return self::callRestApi("mon/$device");
	}

	/**
	 * change log level or the python REST API
	 * raise LgApiException in case of error
	 */
	public function changeLog($log){
		$result = self::callRestApi("log/$log");
		return true;
	}

	/**
	 * save every tokens and config as json file
	 */
	public function save($file = null){
		if($file == null)
			return self::callRestApi("save");
		else
			return self::callRestApi("save/$file");
	}

	/**
	 * function to test 404 error
	 */
	public function fail(){return self::callRestApi("fail");}
}
