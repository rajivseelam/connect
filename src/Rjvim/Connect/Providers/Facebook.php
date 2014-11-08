<?php namespace Rjvim\Connect\Providers;

use Config;
use Redirect;
use Request;
use Rjvim\Connect\Models\OAuthAccount;

use Rjvim\Connect\Providers\LaravelFacebookRedirectLoginHelper;
use Facebook\FacebookSession;
use Facebook\FacebookRequestException;
use Facebook\FacebookRequest;
use Facebook\GraphUser;

class Facebook implements ProviderInterface{


	protected $client;
	protected $scopes;

	/**
	 * Constructor for Connect Library
	 */
	public function __construct($client, $scope)
	{
		$this->scopes = $scope;

		$this->client = $client;

		$config = Config::get('connect::facebook.clients.'.$client);


		FacebookSession::setDefaultApplication($config['client_id'], $config['client_secret']);
	}

	public function getScopes()
	{

		if(is_array($this->scopes))
		{
			$scopes = array();

			foreach($this->scopes as $s)
			{
				$scopes = array_merge(Config::get('connect::facebook.scopes.'.$s),$scopes);
			}
		}
		else
		{
			$scopes = Config::get('connect::facebook.scopes.'.$this->scopes);
		}

		return $scopes;

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function authenticate()
	{
		return Redirect::to($this->getAuthUrl());
	}

	/**
	 * Find user using sentry methods
	 *
	 * @return void
	 * @author 
	 **/
	public function findUser($email)
	{
		$sentry = \App::make('sentry');

		try
		{

			$user = $sentry->findUserByLogin($email);

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
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function takeCare()
	{
		$req = Request::instance();

		$config = Config::get('connect::facebook.clients.'.$this->client);

		$helper = new LaravelFacebookRedirectLoginHelper($config['redirect_uri']);

		try {

		  $session = $helper->getSessionFromRedirect();

		} catch(FacebookRequestException $ex) {

			dd($ex);

		} catch(\Exception $ex) {

		  	dd($ex);
		}

		if($session)
		{

		  try {

		    $user_profile = (new FacebookRequest(
		      $session, 'GET', '/me'
		    ))->execute()->getGraphObject(GraphUser::className());

		  } catch(FacebookRequestException $e) {

		    echo "Exception occured, code: " . $e->getCode();
		    echo " with message: " . $e->getMessage();

		  } 


			$result['uid'] = $user_profile->getId();
			$result['email'] = $user_profile->getEmail();
			$result['url'] = $user_profile->getLink();
			$result['location'] = $user_profile->getLocation();

			$fresult = $this->findUser($result['email']);

			if($fresult['found'])
			{
				$fuser = $fresult['user'];

				$result['first_name'] = $fuser->first_name;
				$result['last_name'] = $fuser->last_name;
			}
			else
			{

				$result['first_name'] = $user_profile->getFirstName();
				$result['last_name'] = $user_profile->getLastName();

			}

			$result['access_token'] = $session->getLongLivedSession()->getToken();

			return $result;

		}

	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function updateOAuthAccount($user,$userData)
	{	
		$scope = $this->scopes;

		$oauth = OAuthAccount::firstOrCreate(
						array(
							'user_id' => $user->id, 
							'provider' => 'facebook'
						));

		$oauth->access_token = $userData['access_token'];
		$oauth->uid = $userData['uid'];
		$oauth->location = $userData['location'];
		$oauth->url = $userData['url'];

		if(!is_array($scope))
		{
			$scope = (array) $scope;
		}

		$scopes = array();

		foreach($scope as $s)
		{
			$scopes['facebook.'.$s] = 1;
		}

		$oauth->scopes = $scopes;

		$oauth->save();

		return true;

	}

		/**
	 * undocumented function
	 *
	 * @return void
	 * @author 
	 **/
	public function getAuthUrl()
	{

		$config = Config::get('connect::facebook.clients.'.$this->client);

		$helper = new LaravelFacebookRedirectLoginHelper($config['redirect_uri']);

		return $helper->getLoginUrl($this->getScopes());
	}



}