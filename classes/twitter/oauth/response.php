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
	
	/**
	 * Response
	 */
	public $__resp;
	
	/**
	 * Constructor
	 *
	 * @param	object	response object
	 */
	public function __construct($resp)
	{
		$this->__resp = $resp;

		if (strpos($this->__resp->type, 'json') !== false)
		{
			$this->__resp->data = json_decode($this->__resp->data);
		}
	}

	/**
	 * Rerouted acces to a value from the response data.
	 *
	 * @param	string		key to get from the response data
	 */
	public function __get($name)
	{
		if ($this->__resp->code < 200 or $this->__resp->code > 299) return false;
		
		if (is_string($this->__resp->data))
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
		
		if ($name === '_result')
		{
			return $result;
		}

		if (is_array($result))
		{
			return $result[$name];
		}
		return $result->{$name};
	}
}