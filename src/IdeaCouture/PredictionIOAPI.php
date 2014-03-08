<?php
/**
 * Prediction.IO WP.
 *
 * @package   PredictionIOAPI
 * @author    Matt Read <mread@ideacouture.com>
 * @license   GPL-2.0+
 * @link      http://www.ideacouture.com
 * @copyright 2013 Idea Couture
 */

namespace IdeaCouture;

/**
 * A wrapper class that interacts with the WordPress plugin and the Prediction.IO
 * SDK object
 *
 * @package PredictionIOAPI
 * @author  Matt Read <mread@ideacouture.com>
 */
class PredictionIOAPI {
	/**
	 * Instance of the PredictionIOClient object.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected $client = null;

	/**
	 * Name of the recommendation engine.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $recommendation_engine = null;

	/**
	 * Name of the similarity engine
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $similarity_engine = null;


	/**
	 * The constructor for PredictionIOAPI
	 *
	 * @param PredictionIO\PredictionIOClient $client A instance of the PredictionIOClient object used 
	 *		to execute commands against a PredictionIO server
	 */
	public function __construct(\PredictionIO\PredictionIOClient $client, $recommendation_engine = null, $similarity_engine = null)
	{
		if( ! $client ) 
			throw InvalidArgumentException('Must supply a PredictionIOClient variable');

		// Set the instance of the PredictionIOClient
		$this->client = $client;

		// Set the name of the recommendation engine if present
		if(isset($recommendation_engine)) {
			$this->recommendation_engine = $recommendation_engine;
		}

		// Set the name of the similarity engine if present
		if(isset($similarity_engine)) {
			$this->similarity_engine = $similarity_engine;
		}

	}

	/** 
	 * Default setter method using php magic methods
	 *
	 * @param $property The name of the property to set
	 * @param $value The value of the property to set
	 *
	 * @since 1.0.0
	 */
	public function __set($property, $value) {
		if(property_exists($this, $property)) {
			$this->$property = $value;
		}

		return $this;
	}

	/** 
	 * Default getter method using php magic methods
	 *
	 * @param $property The name of the property to get
	 * @param $value The value of the property to get
	 *
	 * @since 1.0.0
	 */
	public function __get($property) {
		if(property_exists($this, $property)) {
			return $this->$property;
		}
	}


	/**
	 * Dump out the current client
	 *
	 */
	public function debugclient()
	{
		print_r($this->client);
	}

	/**
	 * A private function to check if the passed in user already exists on the server
	 *
	 * Since Prediction.io servers have a RESTful API, if the user is not found a
	 * 404 error is thrown by the server and the caught exception is used to determine
	 * the users outcome
	 *
	 * @param int $user_id The user id of the user which will be checked
	 *
	 * @throws Exception if the REST server is unable to return a user
	 *
	 * @return boolean $current_user A boolean which indicates if the user has been found or not
	 */
	private function checkUser($user_id)
	{
		$current_user = true;

		try {
			$command = $this->client->getCommand( 'get_user', array( 'pio_uid' => $user_id) );
			$reponse = $this->client->execute($command);
		} catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
			if( $e->getResponse()->getStatusCode() === 404 ) {
				$current_user = false;
			}
		}

		return $current_user;
	}

	/**
	 * A public function that adds a user to the prediction.io server
	 *
	 * Add a new user id to the prediction.io server.  Defaults to automatically check if
	 * the user exists before adding it to the server.
	 * 
	 * @param int $user_id The user id of the user to add
	 * @param boolean $overwrite A boolean to determine if user should be added regardless if it already
	 *		exists
	 *
	 */
	public function addUser($user_id, $overwrite = false)
	{
		// Ensure that a new user will be added or updated if $overwrite is true
		if( !$this->checkUser($user_id) || $overwrite ) {
			$command = $this->client->getCommand('create_user', array('pio_uid' => $user_id));
			$response = $this->client->execute($command);
		}

	}


	/**
	 * A private function to check if the passed in item already exists on the server
	 *
	 * Since Prediction.io servers have a RESTful API, if the item is not found a
	 * 404 error is thrown by the server and the caught exception is used to determine
	 * the item outcome
	 *
	 * @param int $item_id The item id of the item which will be checked
	 *
	 * @throws Exception if the REST server is unable to return a item
	 *
	 * @return boolean $current_item A boolean which indicates if the item has been found or not
	 */
	private function checkItem($item_id)
	{
		$current_item = true;

		try {
			$command = $this->client->getCommand( 'get_item', array( 'pio_iid' => $item_id) );
			$reponse = $this->client->execute($command);
		} catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {
			if( $e->getResponse()->getStatusCode() === 404 ) {
				$current_item = false;
			}
		}

		return $current_item;
	}

	/**
	 * A public function that adds an item to the prediction.io server
	 *
	 * Add a new item id to the prediction.io server.  Defaults to automatically check if
	 * the item exists before adding it to the server.
	 * 
	 * @param int $item_id The item id of the item to add
	 * @param string $item_type The type of item which is to be added
	 * @param boolean $overwrite A boolean to determine if item should be added regardless if it already
	 *		exists
	 *
	 */
	public function addItem($item_id, $item_type = 'post', $overwrite = false)
	{

		// Ensure that a new item will be added or updated if $overwrite is true
		if( !$this->checkItem($item_id) || $overwrite ){
			// Setup the command to execute the add Item
			$command = $this->client->getCommand('create_item', 
				array(
					'pio_iid' => $item_id,
					'pio_itypes' => $item_type
				)
			);
			$reponse = $this->client->execute($command);
		}
	}

	/**
	 * A public function to record a view
	 *
	 * Register a view with a given user and item
	 *
	 * @param int|string $user_id The user to associate with the view
	 * @param int\string $item_id The user to associate with the view
	 * @param string $action The action the user took (can be view, like or dislike)
	 *
	 * @since 1.0.0
	 */
	public function registerAction($user_id, $item_id, $action)
	{
		// Identify the current user in question
		$this->client->identify($user_id);

		// Create the command to register the page view
		$command = $this->client->getCommand('record_action_on_item', array(
			'pio_action' => $action,
			'pio_iid' => $item_id
		));

		// Execute the command
		$this->client->execute($command);
	}

	/**
	 * A public function to return recommendation results
	 *
	 * @param int|string $user_id The user id of the user to get results for
	 * @param string $item_type The type of item to return
	 * @param int $number_of_items The number of items to return
	 * @param string $recommendation_engine_name The name of the recommendation engine
	 *
	 * @return array $recommended_items The recommended items returned by the Prediction.IO Server
	 *
	 * @throws Exception $e The exception that gets returned if the command failed
	 *
	 * @since 1.0.0
	 *
	 */
	public function getRecommendations($user_id, $item_type, $number_of_items, $recommendation_engine_name = null)
	{
		// Identify the current user in question
		$this->client->identify($user_id);

		// Create the command to retrieve the recommendations
		$command = $this->client->getCommand('itemrec_get_top_n', array(
			'pio_engine' => isset($recommendation_engine_name) ? $recommendation_engine_name : $this->recommendation_engine,
			'pio_type' => $item_type,
			'pio_n' => $number_of_items
		));

		try {
			// Execute the command on the server
			$recommended_items = $this->client->execute($command);		
		} catch(\Guzzle\Http\Exception\ClientErrorResponseException $e) {
			echo 'Caught Exception: ', $e->getMessage(), "\n";
		}
		
		return $recommended_items;
	}

	/**
	 * A public function to return similar results
	 *
	 * @param int|string $user_id The user id of the user to get results for
	 * @param string $item_type The type of item to return
	 * @param int $number_of_items The number of items to return
	 * @param string $similarty_engine_name The name of the similarity engine
	 *
	 * @return array $similar_items The similar items returned by the Prediction.IO Server
	 *
	 * @throws Exception $e The exception that gets returned if the command failed
	 *
	 * @since 1.0.0
	 *
	 */
	public function getSimilarities($user_id, $item_type, $number_of_items, $similarity_engine_name)
	{
		// Identify the current user in question
		$this->client->identify($user_id);

		// Create the command to retrieve the similarities
		$command = $this->client->getCommand('itemrec_get_top_n', array(
			'pio_engine' => isset($similarities_engine_name) ? $similarities_engine_name : $this->similarity_engine,
			'pio_types' => $item_type,
			'pio_n' => $number_of_items
		));

		try {
			// Execute the command on the server
			$similar_items = $this->client->execute($command);		
		} catch(\Guzzle\Http\Exception\ClientErrorResponseException $e) {
			echo 'Caught Exception: ', $e->getMessage(), "\n";
		}
		
		return $similar_items;
	}

	/**
	 * Return a list of valid post types
	 *
	 * @return array The valid post types
	 */

	public function get_post_types() {
		$post_types = get_post_types();
		$unwanted_post_types = array('attachment', 'revision', 'nav_menu_item', 'tablepress_table');

		// Remove the unwanted post types from the post types returned by WP
		$valid_post_types = array_diff($post_types, $unwanted_post_types);

		return $valid_post_types;
	}
}