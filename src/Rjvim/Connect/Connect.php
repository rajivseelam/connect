<?php namespace Rjvim\Connect;

use Config;
use Request;
use Response;
use Redirect;
use Google_Client;

class Connect {


	protected $sentry;

	/**
	 * Constructor for Connect Library
	 */

	public function __construct()
	{
		$this->sentry = \App::make('sentry');
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function handle($provider)
	{
		$req = Request::instance();

		if($req->has('code'))
		{
			$userData = $provider->takeCare();
		}
		else
		{
			return $provider->authenticate();
		}

		$user = $this->findUser(array('type' => 'login','value' => $userData['email']));

		if($user['found'])
		{
			//If a user is found - check is he has a oauthaccount - update or create accordingly
			$user = $user['user'];
		}
		else
		{
			//If a user is not found, create a user and a oauth account for him
			$user = $this->createUser($userData,true);

			\Event::fire('oauth_account_created', array($user));
		}

		$provider->updateOAuthAccount($user,$userData);

		//Then log in a user
		$this->sentry->login($user);

		if(Config::get('connect::ajax'))
		{
			return Response::json('success',200);
		}

		return Redirect::route(Config::get('connect::route'));
	}

	/**
	 * Authenticate using Google
	 * 
	 * @param  string $client [description]
	 * @param  string $scope  [description]
	 * @param  string $state  [description]
	 * @return [type]         [description]
	 */
	public function google($client = 'default',$scope = 'default', $state = 'default')
	{
		if($state == 'youtube_access')
		{
			$provider = new Providers\Youtube($client,$scope,$state);
		}
		else
		{
			$provider = new Providers\Google($client,$scope,$state);
		}

		return $this->handle($provider);

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function github($client = 'default',$scope = 'default')
	{
		$provider = new Providers\Github($client,$scope);

		return $this->handle($provider);

	}
	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function facebook($client = 'default',$scope = 'default')
	{
		$provider = new Providers\Facebook($client,$scope);

		return $this->handle($provider);

	}


	/**
	 * Find user using sentry methods
	 *
	 * @return void
	 * @author 
	 **/
	public function findUser($criteria)
	{
		try
		{

			if($criteria['type'] == 'id')
			{
				$user = $this->sentry->findUserById($criteria['value']);
			}

			if($criteria['type'] == 'login')
			{
				$user = $this->sentry->findUserByLogin($criteria['value']);
			}

			$result['found'] = true;
			$result['user'] = $user;

		}
		catch (\Cartalyst\Sentry\Users\UserNotFoundException $e)
		{
		    $result['found'] = false;
		}

		return $result;

	}

	/**
	 * Create a user
	 *
	 * @return Create user
	 * @author Rajiv Seelam
	 **/
	public function createUser($data,$activate = false)
	{

		$password = isset($data['password']) ? $data['password'] : str_random(10);

		$user = $this->sentry->createUser(array(
			        'email'       => $data['email'],
			        'first_name'  => $data['first_name'],
			        'last_name'   => $data['last_name'],
			        'password'    => $password,
			        'activated'   => $activate,
			    ));

		return $user;

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getAuthUrl($provider,$client = 'default',$scope = 'default', $state = 'default')
	{
		$client = new Providers\Google($client,$scope,$state,true);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function google_client($oauthAccount, $client = 'default',$scope = 'default', $state = 'default')
	{
		$provider = new Providers\Google($client,$scope,$state,true);

		$gClient = $provider->prepareClient($client,$scope,$state,true);

		$gClient->setAccessToken(unserialize($oauthAccount->signature));

		if($gClient->isAccessTokenExpired())
		{
			$gClient->refreshToken($oauthAccount->refresh_token);

			$response = $gClient->getAccessToken();

			$actual_response = $response;

			$response = json_decode($response);

			$oauthAccount->access_token = $response->access_token;

			if(isset($response->refresh_token))
			{
				$oauthAccount->refresh_token = $response->refresh_token;
			}

			if(isset($response->created))
			{
				$oauthAccount->created = $response->created;
			}

			$oauthAccount->expires_in = $response->expires_in;

			$oauthAccount->signature = serialize($actual_response);

			$oauthAccount->save();
		}

		return $gClient;

	}
}