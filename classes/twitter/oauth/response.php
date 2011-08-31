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

class Twitter_Oauth_Response {
	
	private $__construct;

	public function __construct($resp)
	{
		$this->__resp = $resp;

		if ( strpos($this->__resp->type, 'json') !== FALSE )
		{
			$this->__resp->data = json_decode($this->__resp->data);
		}
	}

	public function __get($name)
	{
		if ($this->__resp->code < 200 || $this->__resp->code > 299) return FALSE;
		
		if ( is_string($this->__resp->data ) )
		{
			parse_str($this->__resp->data, $result);
		}
		else
		{
			$result = $this->__resp->data;
		}
		
		foreach($result as $k => $v)
		{
			$this->$k = $v;
		}
		
		if ( $name === '_result')
		{
			return $result;
		}

		return $result[$name];
	}
}