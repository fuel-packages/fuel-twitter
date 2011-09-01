# Fuel Twitter Package

A super simple Twitter package for Fuel.  This is based off of Elliot Haughin's CodeIgniter Twitter library.

## About

* Version: 1.0.1
* License: MIT License
* Author: Dan Horrigan
* CI Library Author: Elliot Haughin
* Original URL: [http://www.haughin.com/code/twitter/](http://www.haughin.com/code/twitter/)

## Installation

### Git Submodule

If you are installing this as a submodule (recommended) in your git repo root, run this command:

	$ git submodule add git://github.com/dhorrigan/fuel-twitter.git fuel/packages/twitter/

Then you you need to initialize and update the submodule:

	$ git submodule update --init fuel/packages/twitter/

### Download

Alternatively you can download it and extract it into `fuel/packages/twitter/`.

### Using Oil

	$ php oil package install twitter

## Configuration

Configuration is easy.  First thing you will need to do is to register your app with twitter (if you haven't already) at [https://dev.twitter.com/apps/new.](https://dev.twitter.com/apps/new.).

Next, copy the `config/twitter.php` from the package up into your `app/config/` directory.  Open it up and enter your API keys.

*Note: It will use different keys for different environments by default.*

## Common Methods

### Twitter::logged\_in()

Simply checks if the current session is logged in through Twitter.

```php
if (Twitter::logged_in())
{
	echo 'You are logged in!';
}
```

### Twitter::login()

Starts the login process.  Sends a request to the Twitter Oauth to log you in.

```php
if ( ! Twitter::logged_in())
{
	Twitter::login();
}
```

### Twitter::set\_callback($url)

Sets the callback URL to use.  This is the URL that Twitter will redirect the user to after their credentials have been verified on twitter.com.

```php
Twitter::set_callback(Uri::create('twitter/callback'));
```

### Twitter::get\_tokens()

Gets all of the user's and app's Oauth tokens.

```php
Twitter::get_tokens();

/*
Returns an array in the following format:
array(
	'consumer_key'    => '',
	'consumer_secret' => '',
	'access_key'      => '',
	'access_secret'   => '',
)
*/
```

### Twitter::get($api\_path, $args = array())

Makes a GET request to the Twitter API using the given API path and args.  See [https://dev.twitter.com/docs/api](https://dev.twitter.com/docs/api) for the URLs.

```php
// Verifies the user and returns all of the user's information
$twitter_user = Twitter::get('account/verify_credentials');
```

### Twitter::post($api\_path, $args = array())

Makes a POST request to the Twitter API using the given API path and args.  See [https://dev.twitter.com/docs/api](https://dev.twitter.com/docs/api) for the URLs.

```php
// Updates the current user's status (it Tweets)
$result = Twitter::post('statuses/update', array('status' => 'Using this new awesome cool Twitter package for Fuel!'));
```

### Twitter::search($args)

Sends a request to the Twitter Search API with the given arguments.

```php
// Gets all the tweets with the hashtag of #fuelphp
Twitter::search(array('q' => urlencode('#fuelphp')));
```

## Example Login Controller:

```php
<?php

class Controller_Twitter extends Controller {

	public function action_login()
	{
		if ( ! Twitter::logged_in() )
		{
			Twitter::set_callback(Uri::create('twitter/callback'));
			Twitter::login();
		}
		else
		{
			Response::redirect(Uri::create('/'));
		}
	}

	public function action_logout()
	{
		Session::destroy();
		Response::redirect(Uri::create('/'));
	}

	public function action_callback()
	{
		$tokens = Twitter::get_tokens();
		$twitter_user = Twitter::get('account/verify_credentials');

		// Update or create the user.  We update every time a user logs in
		// so that if they update their profile, we get that update.
		$user = Model_User::find_by_screen_name($twitter_user->screen_name);
		if ( ! $user)
		{
			$user = new Model_User();
		}
		$user->screen_name = $twitter_user->screen_name;
		$user->name = $twitter_user->name;
		$user->description = $twitter_user->description;
		$user->avatar = $twitter_user->profile_image_url;
		$user->oauth_token = $tokens['oauth_token'];
		$user->oauth_token_secret = $tokens['oauth_token_secret'];
		$user->save();
		
		Session::set('user_id', $user->id);
		
		Response::redirect(Uri::create('/'));
	}
	
}
```