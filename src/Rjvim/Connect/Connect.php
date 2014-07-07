<?php namespace Rjvim\Connect;

use Config;
use Request;
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
		}

		$provider->updateOAuthAccount($user,$userData);

		//Then log in a user
		$this->sentry->login($user);

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
		$provider = new Providers\Google($client,$scope,$state,true);

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


}