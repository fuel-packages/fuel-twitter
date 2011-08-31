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

class Twitter_Oauth {
	
	protected $connection = null;
	protected $tokens = array();
	protected $auth_url           = 'http://api.twitter.com/oauth/authenticate';
	protected $request_token_url  = 'http://api.twitter.com/oauth/request_token';
	protected $access_token_url   = 'http://api.twitter.com/oauth/access_token';
	protected $signature_method   = 'HMAC-SHA1';
	protected $version            = '1.0';
	protected $api_url            = 'http://api.twitter.com';
	protected $search_url         = 'http://search.twitter.com/';
	protected $callback = null;
	protected $errors = array();
	protected $enable_debug = false;

	/**
	 * Loads in the Twitter config and sets everything up.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$config = \Config::load('twitter', true);

		$this->tokens = array(
			'consumer_key' 		=> $config[$config['active']]['twitter_consumer_key'],
			'consumer_secret' 	=> $config[$config['active']]['twitter_consumer_secret'],
			'access_key'		=> $this->get_access_key(),
			'access_secret' 	=> $this->get_access_secret()
		);

		$this->check_login();
	}

	/**
	 * If Debug mode is enabled and there are errors, it will display
	 * them.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if ( ! $this->enable_debug)
		{
			return;
		}
		
		if ( ! empty($this->errors))
		{
			foreach ($this->errors as $key => $e)
			{
				echo '<pre>'.$e.'</pre>';
			}
		}
	}

	/**
	 * Enables/Disables debug mode.
	 *
	 * @param   bool  $debug  Debug mode
	 * @return  $this
	 */
	public function enable_debug($debug)
	{
		$this->enable_debug = (bool) $debug;
		return $this;
	}
	
	/**
	 * Sends an HTTP request to the twitter API.
	 *
	 * @param   string  $method  The HTTP method
	 * @param   string  $path    The API URI
	 * @param   array   $args    An array of arguments to send
	 * @return  mixed
	 */
	public function call($method, $path, $args = null)
	{
		$response = $this->http_request(strtoupper($method), $this->api_url.'/'.$path.'.json', $args);

		return ( $response === null ) ? false : $response->_result;
	}

	/**
	 * Sends a GET request to the twitter API.
	 *
	 * @param   string  $path  The API URI
	 * @param   array   $args  An array of arguments to send
	 * @return  mixed
	 */
	public function get($path, $args = null)
	{
		return $this->http_request('GET', $this->api_url.'/'.$path.'.json', $args);
	}

	/**
	 * Sends a POST request to the twitter API.
	 *
	 * @param   string  $path  The API URI
	 * @param   array   $args  An array of arguments to send
	 * @return  mixed
	 */
	public function post($path, $args = null)
	{
		return $this->http_request('POST', $this->api_url.'/'.$path.'.json', $args);
	}

	/**
	 * Sends a PUT request to the twitter API.
	 *
	 * @param   string  $path  The API URI
	 * @param   array   $args  An array of arguments to send
	 * @return  mixed
	 */
	public function put($path, $args = null)
	{
		return $this->http_request('PUT', $this->api_url.'/'.$path.'.json', $args);
	}

	/**
	 * Sends a DELETE request to the twitter API.
	 *
	 * @param   string  $path  The API URI
	 * @param   array   $args  An array of arguments to send
	 * @return  mixed
	 */
	public function delete($path, $args = null)
	{
		return $this->http_request('DELETE', $this->api_url.'/'.$path.'.json', $args);
	}

	/**
	 * Sends a search request to the Twitter Search API.
	 *
	 * @param   array  $args  The search arguments
	 * @return  mixed
	 */
	public function search($args = null)
	{
		$response = $this->http_request('GET', $this->search_url.'search.json', $args);
		
		return ( $response === null ) ? false : $response->_result;
	}

	/**
	 * Checks if the user it logged in through Twitter.
	 *
	 * @return  bool  If the user is logged in
	 */
	public function logged_in()
	{
		$access_key = $this->get_access_key();
		$access_secret = $this->get_access_secret();
		
		$logged_in = false;
		
		if ($this->get_access_key() !== null && $this->get_access_secret() !== null)
		{
			$logged_in = true;
		}
		
		return $logged_in;
	}

	/**
	 * Checks to make sure the Oauth token and access tokens are correct.
	 * Redirects to the current page (refresh)
	 *
	 * @return  null
	 */
	protected function check_login()
	{
		if (isset($_GET['oauth_token']))
		{
			$this->set_access_key($_GET['oauth_token']);
			$token = $this->get_access_token();
			
			$token = $token->_result;
			
			$token = (is_bool($token)) ? $token : (object) $token;
			
			if ( ! empty($token->oauth_token) && ! empty($token->oauth_token_secret))
			{
				$this->set_access_key($token->oauth_token);
				$this->set_access_secret($token->oauth_token_secret);
			}
			
			\Response::redirect(\Uri::current());
			return null;
		}
	}

	/**
	 * Starts the login process.
	 *
	 * @return  null
	 */
	public function login()
	{
		if (($this->get_access_key() === null || $this->get_access_secret() === null))
		{
			\Response::redirect($this->get_auth_url());
			return;
		}
		
		return $this->check_login();
	}

	/**
	 * Logs the user out.
	 *
	 * @return  this
	 */
	public function logout()
	{
		\Session::delete('twitter_oauthtokens');
		return $this;
	}

	/**
	 * Gets the Oauth tokens.
	 *
	 * @return  array  All of the Oauth tokens
	 */
	public function get_tokens()
	{
		return $this->tokens;
	}
	
	/**
	 * Gets the Consumer Key
	 *
	 * @return  string  The Consumer Key
	 */
	public function get_consumer_key()
	{
		return $this->tokens['consumer_key'];
	}
	
	/**
	 * Gets the Consumer Secret
	 *
	 * @return  string  The Consumer Secret
	 */
	public function get_consumer_secret()
	{
		return $this->tokens['consumer_secret'];
	}
	
	/**
	 * Gets the Access Key from the Session.
	 *
	 * @return  string|null  The Access Key
	 */
	public function get_access_key()
	{
		$tokens = \Session::get('twitter_oauthtokens');
		return ($tokens === null || ! isset($tokens['access_key']) || empty($tokens['access_key'])) ? null : $tokens['access_key'];
	}

	/**
	 * Gets the Access Secret from the Session.
	 *
	 * @return  string|null  The Access Secret
	 */
	public function get_access_secret()
	{
		$tokens = \Session::get('twitter_oauthtokens');
		return ($tokens === false || ! isset($tokens['access_secret']) || empty($tokens['access_secret'])) ? null : $tokens['access_secret'];
	}
	
	/**
	 * Sets the access key in the session
	 *
	 * @param   string  $access_key  The access key
	 * @return  $this
	 */
	public function set_access_key($access_key)
	{
		$tokens = \Session::get('twitter_oauthtokens');
		
		if ($tokens === false || ! is_array($tokens))
		{
			$tokens = array('access_key' => $access_key);
		}
		else
		{
			$tokens['access_key'] = $access_key;
		}
		
		\Session::set('twitter_oauthtokens', $tokens);

		return $this;
	}

	/**
	 * Sets the access secret in the session
	 *
	 * @param   string  $access_secret  The access secret
	 * @return  $this
	 */
	public function set_access_secret($access_secret)
	{
		$tokens = \Session::get('twitter_oauthtokens');
		
		if ($tokens === false || ! is_array($tokens))
		{
			$tokens = array('access_secret' => $access_secret);
		}
		else
		{
			$tokens['access_secret'] = $access_secret;
		}
		
		\Session::set('twitter_oauthtokens', $tokens);

		return $this;
	}

	/**
	 * Sets the access tokens.
	 *
	 * Expects: array('oauth_token' => '', 'oauth_token_secret' => '')
	 *
	 * @param   array  $tokens  The access tokens
	 * @return  $this
	 */
	public function set_access_tokens($tokens)
	{
		$this->set_access_key($tokens['oauth_token']);
		$this->set_access_secret($tokens['oauth_token_secret']);

		return $this;
	}

	/**
	 * Gets the authentication URL
	 *
	 * @return  string  The authentication URL
	 */
	public function get_auth_url()
	{
		$token = $this->get_request_token();
		return $this->auth_url.'?oauth_token='.$token->oauth_token;
	}
	
	/**
	 * Gets the request token from Twitter
	 *
	 * @return  string  The request token
	 */
	protected function get_request_token()
	{
		return $this->http_request('GET', $this->request_token_url);
	}
	
	/**
	 * Gets the access token from Twitter
	 *
	 * @return  string  The access token
	 */
	protected function get_access_token()
	{
		return $this->http_request('GET', $this->access_token_url);
	}

	/**
	 * Sends the request to Twitter and returns the response.
	 *
	 * @param   string  $method  The HTTP method
	 * @param   string  $url     The URL of the request
	 * @param   array   $params  The request parameters
	 * @return  mixed   The response
	 */
	protected function http_request($method = null, $url = null, $params = null)
	{
		if (empty($method) || empty($url))
		{
			return false;
		}

		if (empty($params['oauth_signature']))
		{
			$params = $this->prep_params($method, $url, $params);
		}

		$this->connection = new \Twitter_Connection();

		try
		{
			switch ($method)
			{
				case 'GET':
					return $this->connection->get($url, $params);
				break;

				case 'POST':
					return $this->connection->post($url, $params);
				break;

				case 'PUT':
					return null;
				break;

				case 'DELETE':
					return null;
				break;
			}
		}
		catch (\TwitterException $e)
		{
			$this->errors[] = $e;
		}
	}

	/**
	 * Gets the callback URL.
	 *
	 * @return  string  The callback URL
	 */
	public function get_callback()
	{
		return $this->callback;
	}

	/**
	 * Sets the callback URL.
	 *
	 * @param   string  $url  The callback URL
	 * @return  $this
	 */
	public function set_callback($url)
	{
		$this->callback = $url;
		return $this;
	}

	/**
	 * Generates the parameters needed for a request.
	 *
	 * @param   string  $method  The HTTP method
	 * @param   string  $url     The URL of the request
	 * @param   array   $params  The request parameters
	 * @return  array   The params
	 */
	protected function prep_params($method = null, $url = null, $params = null)
	{
		if (empty($method) || empty($url))
		{
			return false;
		}
		
		if ( ! empty($callback = $this->get_callback()))
		{
			$oauth['oauth_callback'] = $callback;
		}
		
		$this->set_callback(null);
		
		$oauth['oauth_consumer_key']      = $this->get_consumer_key();
		$oauth['oauth_token']             = $this->get_access_key();
		$oauth['oauth_nonce']             = $this->generate_nonce();
		$oauth['oauth_timestamp']         = time();
		$oauth['oauth_signature_method']  = $this->signature_method;
		$oauth['oauth_version']           = $this->version;
		
		array_walk($oauth, array($this, 'encode_rfc3986'));
		
		if (is_array($params))
		{
			array_walk($params, array($this, 'encode_rfc3986'));
		}
		
		$encodedParams = array_merge($oauth, (array)$params);
		
		ksort($encodedParams);
		
		$oauth['oauth_signature'] = $this->encode_rfc3986($this->generate_signature($method, $url, $encodedParams));
		return array('request' => $params, 'oauth' => $oauth);
	}

	/**
	 * Generates a security nonce
	 *
	 * @return  string  The nonce
	 */
	protected function generate_nonce()
	{
		return md5(uniqid(rand(), true));
	}

	/**
	 * Encodes the given string according to RFC3986
	 *
	 * @param   string  $string  The string to encode
	 * @return  string  The encoded string
	 */
	protected function encode_rfc3986($string)
	{
		return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode(($string))));
	}

	/**
	 * Generates a signature for the request.
	 *
	 * @param   string  $method  The HTTP method
	 * @param   string  $url     The request URL
	 * @param   array   $params  The request parameters
	 * @return  string  The signature
	 */
	protected function generate_signature($method = null, $url = null, $params = null)
	{
		if (empty($method) || empty($url))
		{
			return false;
		}
		
		// concatenating
		$concat_params = '';
		
		foreach ($params as $k => $v)
		{
			$v = $this->encode_rfc3986($v);
			$concat_params .= "{$k}={$v}&";
		}
		
		$concat_params = $this->encode_rfc3986(substr($concat_params, 0, -1));

		// normalize url
		$normalized_url = $this->encode_rfc3986($this->normalize_url($url));
		$method = $this->encode_rfc3986($method); // don't need this but why not?

		return $this->sign_string("{$method}&{$normalized_url}&{$concat_params}");
	}

	/**
	 * Normalizes a given URL so that it is the proper format.
	 *
	 * @param   string  $url  The URL to normalize
	 * @return  string  The normalized URL
	 */
	protected function normalize_url($url = null)
	{
		$url_parts = parse_url($url);

		$url_parts['port'] = isset($url_parts['port']) ? $url_parts['port'] : 80;

		$scheme = strtolower($url_parts['scheme']);
		$host = strtolower($url_parts['host']);
		$port = intval($url_parts['port']);

		$retval = "{$scheme}://{$host}";
		
		if ($port > 0 && ( $scheme === 'http' && $port !== 80 ) || ( $scheme === 'https' && $port !== 443 ))
		{
			$retval .= ":{$port}";
		}
		
		$retval .= $url_parts['path'];
		
		if ( !empty($url_parts['query']) )
		{
			$retval .= "?{$url_parts['query']}";
		}
		
		return $retval;
	}

	/**
	 * Generates the signature.
	 *
	 * @return  string  The signature
	 */
	protected function sign_string($string)
	{
		$retval = false;
		switch ($this->signature_method)
		{
			case 'HMAC-SHA1':
				$key = $this->encode_rfc3986($this->get_consumer_secret()) . '&' . $this->encode_rfc3986($this->get_access_secret());
				$retval = base64_encode(hash_hmac('sha1', $string, $key, true));
			break;
		}

		return $retval;
	}

}