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

class Twitter_Connection {
	
	/**
	 * Multi Curl
	 */	
	protected $_mch = null;
	
	/**
	 * Curl
	 */	
	protected $_ch = null;
	
	/**
	 * Propperties
	 */
	protected $_properties = array();
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_mch = curl_multi_init();
		
		$this->_properties = array(
			'code' 		=> CURLINFO_HTTP_CODE,
			'time' 		=> CURLINFO_TOTAL_TIME,
			'length'	=> CURLINFO_CONTENT_LENGTH_DOWNLOAD,
			'type' 		=> CURLINFO_CONTENT_TYPE
		);
	}
	
	/**
	 * Initiates a Curl connection
	 *
	 * @param	string		$url	url to connect to
	 */
	protected function init_connection($url)
	{
		$this->_ch = curl_init($url);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
	}
	
	/**
	 * Executes a curl request using get
	 *
	 * @param	string	$url		url to connect to
	 * @param	array	$params		connection/request parameters
	 *
	 */
	public function get($url, $params)
	{
		
		$this->init_connection($url);
		$response = $this->add_curl($url, $params);
	    
	    return $response;
	}
	
	/**
	 * Executes a curl request using get
	 *
	 * @param	string	$url		url to connect to
	 * @param	array	$params		connection/request parameters
	 *
	 */
	public function post($url, $params)
	{
		$post = http_build_query($params['request'], '', '&');
		
		$this->init_connection($url, $params);
		curl_setopt($this->_ch, CURLOPT_POST, 1);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $post);
		
		$response = $this->add_curl($url, $params);

	    return $response;
	}
	
	/**
	 * Adds OAuth headers
	 *
	 * @param	resource	curl resource
	 * @param	string		the url
	 * @param	array		the headers
	 */
	protected function add_oauth_headers(&$ch, $url, $oauth_headers)
	{
		$_h = array('Expect:');
		$url_parts = parse_url($url);
		$oauth = 'Authorization: OAuth realm="' . $url_parts['path'] . '",';
		
		foreach ( $oauth_headers as $name => $value )
		{
			$oauth .= "{$name}=\"{$value}\",";
		}
				
		$_h[] = substr($oauth, 0, -1);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $_h);
	}
	
	/**
	 * Adds a curl resource to the multi curl pile
	 *
	 * @param	string		the url
	 * @param	array		parameters
	 * @return	object		the curl response / Twitter_Oauth_Response
	 */
	protected function add_curl($url, $params = array())
	{
		if ( ! empty($params['oauth']) )
		{
			$this->add_oauth_headers($this->_ch, $url, $params['oauth']);
		}
		
		$ch = $this->_ch;
		
		$key = (string) $ch;
		$this->_requests[$key] = $ch;
		
		$response = curl_multi_add_handle($this->_mch, $ch);

		if ( $response === CURLM_OK or $response === CURLM_CALL_MULTI_PERFORM )
		{
			do
			{
				$mch = curl_multi_exec($this->_mch, $active);
			} 
			while($mch === CURLM_CALL_MULTI_PERFORM);
			
			return $this->get_response($key);
		}
		else
		{
			return $response;
		}
	}
	
	/**
	 * Returns a OAuth response.
	 *
	 * @param	string		the reponses key
	 * @return	object		the curl response / Twitter_Oauth_Response
	 */
	protected function get_response($key = null)
	{
		if (empty($key)) return false;
		
		if ( isset($this->_responses[$key]) )
		{
			return $this->_responses[$key];
		}
		
		$running = null;
		
		do
		{
			$response = curl_multi_exec($this->_mch, $running_curl);
			
			if ( $running !== null and $running_curl != $running )
			{
				$this->set_response($key);
				
				if (isset($this->_responses[$key]))
				{
					$response = new \Twitter_Oauth_Response( (object) $this->_responses[$key] );
					
					if ($response->__resp->code !== 200)
					{
						throw new \TwitterException(isset($response->__resp->data->error) ? $response->__resp->data->error : $response->__resp->data, $response->__resp->code);
					}
					
					return $response;
				}
			}
			
			$running = $running_curl;
			
		} 
		while ($running_curl > 0);
		
	}
	
	/**
	 * Stores the curl response.
	 *
	 * @param	string		the reponses key
	 */
	protected function set_response($key)
	{
		while($done = curl_multi_info_read($this->_mch))
		{
			$key = (string) $done['handle'];
			$this->_responses[$key]['data'] = curl_multi_getcontent($done['handle']);
			
			foreach ( $this->_properties as $curl_key => $value )
			{
				$this->_responses[$key][$curl_key] = curl_getinfo($done['handle'], $value);
				curl_multi_remove_handle($this->_mch, $done['handle']);
			}
		}
	}
}
