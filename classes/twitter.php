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

class TwitterException extends \Exception {}

class Twitter {

	protected static $oauth = null;

	public static function _init()
	{
		static::$oauth = new \Twitter_Oauth();
	}

	public static function __callStatic($method, $args)
	{
		if (is_callable(array(static::$oauth, $method)))
		{
			return call_user_func_array(array(static::$oauth, $method), $args);
		}

		throw new \BadMethodCallException("Method Twitter::$method does not exist.");
	}
	
	private function __construct() { }
	
	public static function logged_in()
	{
		return static::$oauth->loggedIn();
	}
	
	public static function set_callback($url)
	{
		static::$oauth->setCallback($url);
	}
	
	public static function login()
	{
		return static::$oauth->login();
	}
	
	public static function logout()
	{
		return static::$oauth->logout();
	}
	
	public static function get_tokens()
	{
		return array(
			'oauth_token' => static::$oauth->getAccessKey(),
			'oauth_token_secret' => static::$oauth->getAccessSecret()
		);
	}
	
	public static function set_tokens($tokens)
	{
		return static::$oauth->setAccessTokens($tokens);
	}
}