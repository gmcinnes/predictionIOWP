<?php
/**
 * Prediction.IO WP.
 *
 * @package   PredictionIOWP
 * @author    Matt Read <mread@ideacouture.com>
 * @license   GPL-2.0+
 * @link      http://www.ideacouture.com
 * @copyright 2013 Idea Couture
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-PredictionIOWP-admin.php`
 *
 * @package PredictionIOWP
 * @author  Matt Read <mread@ideacouture.com>
 */
/**
 * Load in composer's autoloader to load in the PredictionIO PHP SDK
 */

require_once( str_replace('/wp', '/', $_SERVER['DOCUMENT_ROOT']) . '/vendor/autoload.php');


use PredictionIO\PredictionIOClient;

/**
 *	Load in the custom Prediction.IO API Class
 */
use IdeaCouture\PredictionIOAPI;

/**
 * Custom WordPress PredictionIOWP plugin class
 *
 * @since	1.0.0
 */
class PredictionIOWP {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'PredictionIOWP';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of the predictionIOAPI
	 *
	 * @since	1.0.0
	 *
	 * @var 	object
	 */
	protected $predictionIOAPI = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		// Create the PredictionIOAPI object if the app_key has been set
		$options = get_option('piwp_connection_settings');
		if(!empty($options['app_key']) && $this->predictionIOAPI === null) {
			$this->predictionIOAPI = new PredictionIOAPI(
				PredictionIOClient::factory(array('appkey' => $options['app_key'])),
				$options['recommendation_engine'],
				$options['similarity_engine']
				);
		}

		$this->userid = -1;

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/*
		 * Add custom action hooks Prediction IO
		 */
		add_filter( 'the_content' , array( $this , 'register_user' ));
		add_filter( 'the_content' , array( $this , 'register_view_callback' ));	

		add_action( 'save_post' , array( $this , 'add_item' ));
		add_action( 'wp_ajax_register_action' , array( $this , 'register_action_ajax' ));

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}


	/**
	 * Ensure that the current user's ID has been added to prediction io
	 */
	function register_user($content) {
		$img =  $this->build_action_image('register_user', array('user_id' => $this->userid));

		return $content . $img;
	}

	/**
	 * A call back function that registers a view action
	 *
	 * This callback function records a view with a user / item pair
	 * and registers it with the Prediction.io server
	 *
	 * @params array $args The arguments array
	 *	* The first index [0] should be the user id
	 *	* The second index [1] should be the item id
	 *
	 * @since 1.0.0
	 *
	 */
	function register_view_callback($content) {
		global $post;
		if(is_singular()) {
			$img = $this->build_action_image('register_item_view', array('user_id' => $this->userid, 'item_id' => $post->ID));
			return $content . $img;
		}

		return $content;
	}

	/** 
	 * Add an item to the Prediction IO server
	 */
	function add_item( $post_id ) {
		$post = get_post($post_id);
		if(self::is_valid_post($post->post_status, $post->post_type)) {
			$this->predictionIOAPI->addItem($post_id, $post->post_type);
		}
	}

	/** 
	 * A simple hook for ajax calls to register action
	 */
	function register_action_ajax() {

		$sanitized_post = sanitize_array($_POST);

		$user_id = !empty($sanitized_post['user_id']) ? $sanitized_post['user_id'] : $this->user_id;

		$response = $this->predictionIOAPI->registerAction($user_id, $sanitized_post['item_id'], $sanitized_post['predictionAction']);

		wp_send_json(array(
			'response' => $response
		));
	}

	/**
	 * A builder for the fake image
	 */
	private function build_action_image($action, $params) {
		$url_string = plugins_url( 'includes/perform_actions.php', __FILE__ ) . "?action=$action&" . http_build_query($params);
		$img_string = '<img src="%s" class="perform_action" />';
		
		return sprintf($img_string, $url_string);
	}

	// Determine if this is a valid post that should be saved in Prediction.IO
	private function is_valid_post($post_status, $post_type) {
		return ($post_status === 'publish' && in_array($post_type, $this->predictionIOAPI->get_post_types()));
	} 

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}
}
