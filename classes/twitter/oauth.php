<?php
/**
 * Fuel Twitter Package
 *
 * This is a port of Elliot Haughin's CodeIgniter Twitter library.
 * You can find his library here http://www.haughin.com/code/twitter/
 *
 * @copyright  2011 Dan Horrigan
 * @license    MIT License
 */
 
namespace Twitter;

class Twitter_Oauth extends \Twitter_Connection {
	
	private $_tokens = array();
	private $_authorizationUrl 	= 'http://api.twitter.com/oauth/authenticate';
	private $_requestTokenUrl 	= 'http://api.twitter.com/oauth/request_token';
	private $_accessTokenUrl 	= 'http://api.twitter.com/oauth/access_token';
	private $_signatureMethod 	= 'HMAC-SHA1';
	private $_version 			= '1.0';
	private $_apiUrl 			= 'http://api.twitter.com';
	private $_searchUrl			= 'http://search.twitter.com/';
	private $_callback = NULL;
	private $_errors = array();
	private $_enable_debug = FALSE;
	
	function __construct()
	{
		parent::__construct();
		
		$config = \Config::load('twitter', true);

		$this->_tokens = array(
			'consumer_key' 		=> $config[$config['active']]['twitter_consumer_key'],
			'consumer_secret' 	=> $config[$config['active']]['twitter_consumer_secret'],
			'access_key'		=> $this->_getAccessKey(),
			'access_secret' 	=> $this->_getAccessSecret()
		);

		$this->_checkLogin();
	}
	
	function __destruct()
	{
		if ( !$this->_enable_debug ) return;
		
		if ( !empty($this->_errors) )
		{
			foreach ( $this->_errors as $key => $e )
			{
				echo '<pre>'.$e.'</pre>';
			}
		}
	}
	
	public function enable_debug($debug)
	{
		$debug = (bool) $debug;
		$this->_enable_debug = $debug;
	}
	
	public function call($method, $path, $args = NULL)
	{
		$response = $this->_httpRequest(strtoupper($method), $this->_apiUrl.'/'.$path.'.json', $args);
		
		// var_dump($response);
		// die();
		
		return ( $response === NULL ) ? FALSE : $response->_result;
	}
	
	public function search($args = NULL)
	{
		$response = $this->_httpRequest('GET', $this->_searchUrl.'search.json', $args);
		
		return ( $response === NULL ) ? FALSE : $response->_result;
	}
	
	public function loggedIn()
	{
		$access_key = $this->_getAccessKey();
		$access_secret = $this->_getAccessSecret();
		
		$loggedIn = FALSE;
		
		if ( $this->_getAccessKey() !== NULL && $this->_getAccessSecret() !== NULL )
		{
			$loggedIn = TRUE;
		}
		
		return $loggedIn;
	}
	
	private function _checkLogin()
	{
		if ( isset($_GET['oauth_token']) )
		{
			$this->_setAccessKey($_GET['oauth_token']);
			$token = $this->_getAccessToken();
			
			$token = $token->_result;
			
			$token = ( is_bool($token) ) ? $token : (object) $token;
			
			if ( !empty($token->oauth_token) && !empty($token->oauth_token_secret) )
			{
				$this->_setAccessKey($token->oauth_token);
				$this->_setAccessSecret($token->oauth_token_secret);
			}
			
			\Response::redirect(\Uri::current());
			return NULL;
		}
	}
	
	public function login()
	{
		if ( ($this->_getAccessKey() === NULL || $this->_getAccessSecret() === NULL) )
		{
			\Response::redirect($this->_getAuthorizationUrl());
			return;
		}
		
		return $this->_checkLogin();
	}
	
	public function logout()
	{
		\Session::delete('twitter_oauth_tokens');
	}
	
	public function getTokens()
	{
		return $this->_tokens;
	}
	
	private function _getConsumerKey()
	{
		return $this->_tokens['consumer_key'];
	}
	
	private function _getConsumerSecret()
	{
		return $this->_tokens['consumer_secret'];
	}
	
	public function getAccessKey(){ return $this->_getAccessKey(); }
	
	private function _getAccessKey()
	{
		$tokens = \Session::get('twitter_oauth_tokens');
		return ( $tokens === FALSE || !isset($tokens['access_key']) || empty($tokens['access_key']) ) ? NULL : $tokens['access_key'];
	}
	
	private function _setAccessKey($access_key)
	{
		$tokens = \Session::get('twitter_oauth_tokens');
		
		if ( $tokens === FALSE || !is_array($tokens) )
		{
			$tokens = array('access_key' => $access_key);
		}
		else
		{
			$tokens['access_key'] = $access_key;
		}
		
		\Session::set('twitter_oauth_tokens', $tokens);
	}
	
	public function getAccessSecret(){ return $this->_getAccessSecret(); }
	
	private function _getAccessSecret()
	{
		$tokens = \Session::get('twitter_oauth_tokens');
		return ( $tokens === FALSE || !isset($tokens['access_secret']) || empty($tokens['access_secret']) ) ? NULL : $tokens['access_secret'];
	}
	
	private function _setAccessSecret($access_secret)
	{
		$tokens = \Session::get('twitter_oauth_tokens');
		
		if ( $tokens === FALSE || !is_array($tokens) )
		{
			$tokens = array('access_secret' => $access_secret);
		}
		else
		{
			$tokens['access_secret'] = $access_secret;
		}
		
		\Session::set('twitter_oauth_tokens', $tokens);
	}
	
	private function _setAccessTokens($tokens)
	{
		$this->_setAccessKey($tokens['oauth_token']);
		$this->_setAccessSecret($tokens['oauth_token_secret']);
	}
	
	public function setAccessTokens($tokens)
	{
		return $this->_setAccessTokens($tokens);
	}
	
	private function _getAuthorizationUrl()
	{
		$token = $this->_getRequestToken();
		return $this->_authorizationUrl.'?oauth_token=' . $token->oauth_token;
	}
	
	private function _getRequestToken()
	{
		return $this->_httpRequest('GET', $this->_requestTokenUrl);
	}
	
	private function _getAccessToken()
	{
		return $this->_httpRequest('GET', $this->_accessTokenUrl);
	}
	
	protected function _httpRequest($method = null, $url = null, $params = null)
	{
		if( empty($method) || empty($url) ) return FALSE;
		if ( empty($params['oauth_signature']) ) $params = $this->_prepareParameters($method, $url, $params);
		
		$this->_connection = new \Twitter_Connection();
		
		try {
			switch ( $method )
			{
				case 'GET':
					return $this->_connection->get($url, $params);
				break;

				case 'POST':
					return $this->_connection->post($url, $params);
				break;

				case 'PUT':
					return NULL;
				break;

				case 'DELETE':
					return NULL;
				break;
			}
		} catch (\TwitterException $e) {
			$this->_errors[] = $e;
		}
	}
	
	private function _getCallback()
	{
		return $this->_callback;
	}
	
	public function setCallback($url)
	{
		$this->_callback = $url;
	}
	
	private function _prepareParameters($method = NULL, $url = NULL, $params = NULL)
	{
		if ( empty($method) || empty($url) ) return FALSE;
		
		$callback = $this->_getCallback();
		
		if ( !empty($callback) )
		{
			$oauth['oauth_callback'] = $callback;
		}
		
		$this->setCallback(NULL);
		
		$oauth['oauth_consumer_key'] 		= $this->_getConsumerKey();
		$oauth['oauth_token'] 				= $this->_getAccessKey();
		$oauth['oauth_nonce'] 				= $this->_generateNonce();
		$oauth['oauth_timestamp'] 			= time();
		$oauth['oauth_signature_method'] 	= $this->_signatureMethod;
		$oauth['oauth_version'] 			= $this->_version;
		
		array_walk($oauth, array($this, '_encode_rfc3986'));
		
		if ( is_array($params) )
		{
			array_walk($params, array($this, '_encode_rfc3986'));
		}
		
		$encodedParams = array_merge($oauth, (array)$params);
		
		ksort($encodedParams);
		
		$oauth['oauth_signature'] = $this->_encode_rfc3986($this->_generateSignature($method, $url, $encodedParams));
		return array('request' => $params, 'oauth' => $oauth);
	}

	private function _generateNonce()
	{
		return md5(uniqid(rand(), TRUE));
	}
	
	private function _encode_rfc3986($string)
	{
		return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode(($string))));
	}
	
	private function _generateSignature($method = null, $url = null, $params = null)
	{
		if( empty($method) || empty($url) ) return FALSE;
		
		// concatenating
		$concatenatedParams = '';
		
		foreach ($params as $k => $v)
		{
			$v = $this->_encode_rfc3986($v);
			$concatenatedParams .= "{$k}={$v}&";
		}
		
		$concatenatedParams = $this->_encode_rfc3986(substr($concatenatedParams, 0, -1));

		// normalize url
		$normalizedUrl = $this->_encode_rfc3986($this->_normalizeUrl($url));
		$method = $this->_encode_rfc3986($method); // don't need this but why not?

		$signatureBaseString = "{$method}&{$normalizedUrl}&{$concatenatedParams}";
		return $this->_signString($signatureBaseString);
	}
	
	private function _normalizeUrl($url = NULL)
	{
		$urlParts = parse_url($url);

		if ( !isset($urlParts['port']) ) $urlParts['port'] = 80;

		$scheme = strtolower($urlParts['scheme']);
		$host = strtolower($urlParts['host']);
		$port = intval($urlParts['port']);

		$retval = "{$scheme}://{$host}";
		
		if ( $port > 0 && ( $scheme === 'http' && $port !== 80 ) || ( $scheme === 'https' && $port !== 443 ) )
		{
			$retval .= ":{$port}";
		}
		
		$retval .= $urlParts['path'];
		
		if ( !empty($urlParts['query']) )
		{
			$retval .= "?{$urlParts['query']}";
		}
		
		return $retval;
	}
	
	private function _signString($string)
	{
		$retval = FALSE;
		switch ( $this->_signatureMethod )
		{
			case 'HMAC-SHA1':
				$key = $this->_encode_rfc3986($this->_getConsumerSecret()) . '&' . $this->_encode_rfc3986($this->_getAccessSecret());
				$retval = base64_encode(hash_hmac('sha1', $string, $key, true));
			break;
		}

		return $retval;
	}

}