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
	
	// Allow multi-threading.
	
	private $_mch = NULL;
	private $_properties = array();
	
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
	
	private function _initConnection($url)
	{
		$this->_ch = curl_init($url);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
	}
	
	public function get($url, $params)
	{
		if ( count($params['request']) > 0 )
		{
			$url .= '?';
		
			foreach( $params['request'] as $k => $v )
			{
				$url .= "{$k}={$v}&";
			}
			
			$url = substr($url, 0, -1);
		}
		
		$this->_initConnection($url);
		$response = $this->_addCurl($url, $params);

	    return $response;
	}
	
	public function post($url, $params)
	{
		// Todo
		$post = '';
		
		foreach ( $params['request'] as $k => $v )
		{
			$post .= "{$k}={$v}&";
		}
		
		$post = substr($post, 0, -1);
		
		$this->_initConnection($url, $params);
		curl_setopt($this->_ch, CURLOPT_POST, 1);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $post);
		
		$response = $this->_addCurl($url, $params);

	    return $response;
	}
	
	private function _addOauthHeaders(&$ch, $url, $oauthHeaders)
	{
		$_h = array('Expect:');
		$urlParts = parse_url($url);
		$oauth = 'Authorization: OAuth realm="' . $urlParts['path'] . '",';
		
		foreach ( $oauthHeaders as $name => $value )
		{
			$oauth .= "{$name}=\"{$value}\",";
		}
		
		$_h[] = substr($oauth, 0, -1);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $_h);
	}
	
	private function _addCurl($url, $params = array())
	{
		if ( !empty($params['oauth']) )
		{
			$this->_addOauthHeaders($this->_ch, $url, $params['oauth']);
		}
		
		$ch = $this->_ch;
		
		$key = (string) $ch;
		$this->_requests[$key] = $ch;
		
		$response = curl_multi_add_handle($this->_mch, $ch);

		if ( $response === CURLM_OK || $response === CURLM_CALL_MULTI_PERFORM )
		{
			do {
				$mch = curl_multi_exec($this->_mch, $active);
			} while ( $mch === CURLM_CALL_MULTI_PERFORM );
			
			return $this->_getResponse($key);
		}
		else
		{
			return $response;
		}
	}
	
	private function _getResponse($key = NULL)
	{
		if ( $key == NULL ) return FALSE;
		
		if ( isset($this->_responses[$key]) )
		{
			return $this->_responses[$key];
		}
		
		$running = NULL;
		
		do
		{
			$response = curl_multi_exec($this->_mch, $running_curl);
			
			if ( $running !== NULL && $running_curl != $running )
			{
				$this->_setResponse($key);
				
				if ( isset($this->_responses[$key]) )
				{
					$response = new \Twitter_Oauth_Response( (object) $this->_responses[$key] );
					
					if ( $response->__resp->code !== 200 )
					{
						throw new \TwitterException(isset($response->__resp->data->error) ? $response->__resp->data->error : $response->__resp->data, $response->__resp->code);
					}
					
					return $response;
				}
			}
			
			$running = $running_curl;
			
		} while ( $running_curl > 0);
		
	}
	
	private function _setResponse($key)
	{
		while( $done = curl_multi_info_read($this->_mch) )
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
