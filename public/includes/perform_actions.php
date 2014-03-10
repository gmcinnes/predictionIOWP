<?php

require_once( $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
require_once( $_SERVER['DOCUMENT_ROOT'] . "/wp/wp-load.php");

use \PredictionIO\PredictionIOClient;
use IdeaCouture\PredictionIOAPI;

// Sanitize the get variables.... just in case
$sanitized_get = sanitize_array($_GET);

// Check to see if an action is being requested
if(isset($_GET['action'])) {
	
	$options = get_option('piwp_connection_settings');

	$predictionIOAPI = new PredictionIOAPI(
		PredictionIO\PredictionIOClient::factory(array(
			'appkey' => $options['app_key'],
			'apiurl' => $options['api_url']
			)),
			$options['recommendation_engine'],
			$options['similarity_engine']
	);

	// Cool! We know have a Mars predictionIOAPI object that we can perform actions against
	// Get the user id
	// TODO: This should come from the MarsAccount
	$user_id = !empty($sanitized_get['user_id']) ? $sanitized_get['user_id'] : mars::user_id;

	switch($_GET['action']) {
		case 'register_user':
			$response = $predictionIOAPI->addUser($user_id);
			break;

		case 'register_item_view':
			// Only register the action if the item_id is set
			if(!empty($sanitized_get['item_id'])) {
				$response = $predictionIOAPI->registerAction($user_id, $sanitized_get['item_id'], 'view');
			}
			break;
	}

	send_image();

}

function send_image() {
	$transparent_image = '../assets/spacer.gif';
	header('Content-Type: image/gif');
	
	if(file_exists($transparent_image)) {
		$fp = fopen($transparent_image, 'rb');
		header("Content-Length: " . filesize($transparent_image));
		fpassthru($fp);
		exit;

	} elseif (imagetypes() & IMG_GIF) {
		// Create a 1x1 pixel gif
		$generated_image = imagecreatetruecolor(1, 1);
		$white_background = imagecolorallocate($generated_image, 255, 255, 255);

		// Make the background transparent
		imagecolortransparent($generated_image, $white_background);
		imagefill($generated_image, 0, 0, $white_background);

		imagegif($generated_image);
		imagedestroy($generated_image);
	}
}