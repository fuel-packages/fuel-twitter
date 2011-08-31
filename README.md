# Fuel Twitter Package

This is a port of Elliot Haughin's CodeIgniter Twitter library.

You can find his library here [http://www.haughin.com/code/twitter/](http://www.haughin.com/code/twitter/)

## Installation

If you are installing this as a submodule (recommended) in your git repo root, run this command:

	$ git submodule add git://github.com/dhorrigan/fuel-twitter.git fuel/packages/twitter/

Then you you need to initialize and update the submodule:

	$ git submodule update --init fuel/packages/twitter/

Alternatively you can download it and extract it into `fuel/packages/twitter/`.

*Oil install coming soon*

## Example Login Contoller:

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