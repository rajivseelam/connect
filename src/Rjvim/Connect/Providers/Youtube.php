<?php namespace Rjvim\Connect\Providers;

use Config;
use Google_Client;
use Request;
use Redirect;
use Google_Service_Plus;
use Rjvim\Connect\Models\OAuthAccount;

class Youtube extends Google{


	protected $sentry;

	/**
	 * Constructor for Connect Library
	 */

	public function __construct($client, $scope, $state = 'default')
	{
		parent::__construct($client, $scope, $state);

		$this->sentry = \App::make('sentry');
	}

    /**
	 * Get User Data from Google
	 *
	 * @return void
	 * @author 
	 **/
	public function getGoogleUserData()
	{
		$result = array();

		$plus = new Google_Service_Plus($this->client);

		$person = $plus->people->get('me');

		if($person->getEmails()[0]->getType() == 'account')
		{
			$email = $person->getEmails()[0]->getValue();
		}
		else
		{
			$email = 'Not Found';
		}

		$result['uid'] = $person->id;
		$result['email'] = $this->sentry->getUser()->email;
		$result['url'] = $person->getUrl();
		$result['image'] = $person->getImage()->getUrl();
		$result['channel'] = $this->getChannelId();

		return $result;
	}

	/**
	 * Update Google OAuth information for user
	 *
	 * @return void
	 * @author 
	 **/
	public function updateOAuthAccount($user,$gUserData)
	{
		$response = $this->client->getAccessToken();

		$scope = $this->scopes;

		$actual_response = $response;

		$response = json_decode($response);

		$oauth = OAuthAccount::firstOrCreate(
						array(
							'user_id' => $user->id, 
							'provider' => 'youtube',
							'channel' => $gUserData['channel']
						));

		$oauth->image_url = $gUserData['image'];
		$oauth->url = $gUserData['url'];
		$oauth->uid = $gUserData['uid'];
		$oauth->channel = $gUserData['channel'];

		$oauth->access_token = $response->access_token;

		if(isset($response->refresh_token))
		{
			$oauth->refresh_token = $response->refresh_token;
		}

		if(isset($response->created))
		{
			$oauth->created = $response->created;
		}

		$oauth->expires_in = $response->expires_in;

		$oauth->signature = serialize($actual_response);

		if(!is_array($scope))
		{
			$scope = (array) $scope;
		}

		$scopes = array();

		foreach($scope as $s)
		{

			$scopes['google.'.$s] = 1;

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
	public function getChannelId($channelId = 'mine')
	{
		$youtube = new \Google_Service_YouTube($this->client);

		$result = $youtube
			->channels
			->listChannels('contentDetails,snippet',array('mine' => true))
			->getItems()[0]->getId();

		return $result;
	}


	/**
	 * Get the list of channels list.
	 *
	 * You can provide a Id also with the channel
	 *
	 * @return void
	 * @author 
	 **/
	public function getChannelsList($channelId = 'mine')
	{
		$youtube = new \Google_Service_YouTube($this->client);

		if($channelId == 'mine')
		{

			/**
			 * A similar call to
			 * GET https://www.googleapis.com/youtube/v3/channels?part=contentDetails&mine=true&key={YOUR_API_KEY}
			 */

			$result = $youtube
						->channels
						->listChannels('contentDetails',array('mine' => true))
						->getItems()[0]
						->getContentDetails()
						->getRelatedPlaylists();
		}
		else
		{
			$result = $youtube
				->channels
				->listChannels('contentDetails',array('id' => $channelId))
				->getItems()[0]
				->getContentDetails()
				->getRelatedPlaylists();
		}


		return $result;
	}


}
