<?php

namespace jqs\phpbtsync;

class phpbtsync {
	/*
	 	
	 */

	private static $host = '127.0.0.1';
	private static $port = '8888';
	private static $login = null;
	private static $password = null;
	private static $instance;
	public static $lastError = [];
	public static $response = null;
	public static $responseInfo = null;
	public static $responseError = null;
	public static $responseErrorNumber = null;

	private static $methods = [
		'getFolders'		=> ['get_folders', [], ['secret']],
		'addFolder'			=> ['add_folder', ['dir'], ['secret', 'selective_sync']],
		'removeFodler'		=> ['remove_folder', ['dir'], ['secret']],
		'getFiles'			=> ['get_files', ['secret'], ['path']],
		'setFilePrefs'		=> ['set_file_prefs', ['secret', 'file', 'download'], []],
		'getSecrets'		=> ['get_secrets', [], ['secret', 'type']], 
		'getFolderPrefs'	=> ['get_folder_prefs', ['secret'], []],
		'setFolderPrefs'	=> ['set_folder_prefs', ['secret'], ['use_dht', 'use_hosts', 'search_lan', 'use_relay_server', 'use_tracker', 'use_sync_trash']],
		'getFolderHosts'	=> ['get_folder_hosts', ['secret'], []],
		'setFolderhosts'	=> ['set_folder_hosts', ['secret', 'hosts'], []],
		'getPreferences'	=> ['get_prefs', [], []],
		// Add advanced Prefs
		'setPreferences'	=> ['set_prefs', ['device_name', 'download_limit', 'lang', 'listening_port', 'upload_limit', 'use_upnp'], []],
		'getOS'				=> ['get_os', [], []],
		'getVersion'		=> ['get_version', [], []],
		'getSpeed'			=> ['get_speed', [], []],
		'shutdown'			=> ['shutdown', [], []],
	];

	private function __construct() {
		// Dead to me
	}

	public static function getInstance($options = array()) {
		if (count($options)>0 || is_null(self::$instance)) {
			foreach (['host', 'port', 'login', 'password'] as $option) {
				self::$$option = (isset($options[$option])) ? $options[$option] : self::$$option;
			}
			// (re)initialize our instance
			self::$instance = new self();
		}
		return self::$instance;
	}

	// Methods
	public function __call($name, $args) {
		if (array_key_exists($name, self::$methods)) {
			$error = [];
			$params = [];
			// Test all required/otional arguments
			if (is_array(self::$methods[$name][1])) {
				// Walk each arg and make sure all of the required method args are accounted for
				foreach(array_keys(self::$methods[$name][1]) as $key) {
					if (!array_key_exists($key, $args[0])) {
						$error['error'][] = "Missing required param: $key";
					}
				}
			}
			// Walk each arg and make sure it exists in either [1] or [2]
			if (count($args[0])) {
				foreach($args[0] as $key => $value) {
					if (!in_array($key, self::$methods[$name][1]) && !in_array($key, self::$methods[$name][2])) {
						$error['warn'][] = $key . ' does not exist in required or option parameters. Dropping';
					} else {
						$params[$key] = $value;
					}
				}
			}
			self::$lastError = $error;
			if (count($error['error'])) {
				return false;
			}
			// Issue call
			$resp = self::sendCall(self::$methods[$name][0], http_build_query($params));
			// return response
			return $resp;
			//$out[] = $name;
			$out['args'] = $args;
			$out['zz'] = self::$methods[$name];
			$out['req'] = array_keys(self::$methods[$name][1]);
			$out['opt'] = array_keys(self::$methods[$name][2]);
			$out['method'] = self::$methods[$name][0];
			$out['p'] = http_build_query($params);
			$out['url'] = self::sendCall(self::$methods[$name][0], http_build_query($params));
			$out['err'] = self::$lastError;
			return print_r($out, true);

		} else {
			// Nothing to do
			return false;
		}
	}

	private function sendCall($method, $params) {
		$url = [];
		$url[] = 'http://';
		$url[] = self::$host;
		$url[] = ':' . ((strlen(self::$port)) ? self::$port : '80');
		$url[] = '/api?method=';
		$url[] = $method;
		if (strlen($params)) {
			$url[] = '&' . $params;
		}
		$theUrl = implode('', $url);
		echo $theUrl, "\n";
		self::$response = null;
		self::$responseInfo = null;
		self::$responseError = null;
		self::$responseErrorNumber = null;
		$curl = curl_init($theUrl);
		curl_setopt_array($curl, [
				CURLOPT_RETURNTRANSFER=>1,
				CURLOPT_NOBODY=>0,
				CURLOPT_FOLLOWLOCATION=>1,
				CURLOPT_TIMEOUT=>30
			]);
		self::$response = curl_exec($curl);
		self::$responseInfo = curl_getinfo($curl);
		self::$responseError = curl_error($curl);
		self::$responseErrorNumber = curl_errno($curl);
		curl_close($curl);
		if(intval(self::$responseInfo['http_code']) == 200) {
			return self::$response;
		}
		return false;
	}
}