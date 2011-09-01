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

	/**
	 * Oh noz! a Singleton!!!
	 */
	private function __construct() { }

	/**
	 * @var  string  $version  The current version of the package
	 */
	public static $version = '1.0.1';

	/**
	 * @var  Twitter_Oauth  $oauth  Holds the Twitter_Oauth instance.
	 */
	protected static $oauth = null;

	/**
	 * Creates the Twitter_Oauth instance
	 *
	 * @return  void
	 */
	public static function _init()
	{
		static::$oauth = new \Twitter_Oauth();
	}

	/**
	 * Magic pass-through to the Twitter_Oauth instance.
	 *
	 * @param   string  $method  The called method
	 * @param   array   $args    The method arguments
	 * @return  mixed   The method results
	 * @throws  BadMethodCallException
	 */
	public static function __callStatic($method, $args)
	{
		if (is_callable(array(static::$oauth, $method)))
		{
			return call_user_func_array(array(static::$oauth, $method), $args);
		}

		throw new \BadMethodCallException("Method Twitter::$method does not exist.");
	}

	/**
	 * Gets the Oauth access tokens.
	 *
	 * @return  array  The access tokens
	 */
	public static function get_tokens()
	{
		return array(
			'oauth_token' => static::$oauth->get_access_key(),
			'oauth_token_secret' => static::$oauth->get_access_secret()
		);
	}

	/**
	 * An alias for Twitter_Oauth::set_access_tokens.
	 *
	 * @param   array  $tokens  The access tokens
	 * @return  Twitter_Oauth
	 */
	public static function set_tokens($tokens)
	{
		return static::$oauth->set_access_tokens($tokens);
	}
}