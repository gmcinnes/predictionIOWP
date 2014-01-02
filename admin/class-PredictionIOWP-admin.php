<?php
/**
 * Prediction.IO WP.
 *
 * @package   PredictionIOWP_Admin
 * @author    Matt Read <mread@ideacouture.com>
 * @license   GPL-2.0+
 * @link      http://www.ideacouture.com
 * @copyright 2013 Idea Couture
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-PredictionIOWP.php`
 *
 * @package PredictionIOWP_Admin
 * @author  Matt Read <mread@ideacouture.com>
 */

/**
 * Load in composer's autoloader to load in the PredictionIO PHP SDK
 */

require_once( $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
use PredictionIO\PredictionIOClient;

/**
 *	Load in the custom Prediction.IO API Class
 */
use IdeaCouture\PredictionIOAPI;

/**
 * Custom WordPress PredictionIOWP_Admin plugin class
 *
 * @since	1.0.0
 */
class PredictionIOWP_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Instance of the predictionIOAPI
	 *
	 * @since	1.0.0
	 *
	 * @var 	object
	 */
	protected $predictionIOAPI = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		/*
		 * Call $plugin_slug from public plugin class.
		 *
		 */
		$plugin = PredictionIOWP::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Create the PredictionIOAPI object if the app_key has been set
		$options = get_option('piwp_connection_settings');
		if(!empty($options['app_key']) && $this->predictionIOAPI === null) {
			$this->predictionIOAPI = new PredictionIOAPI(
				PredictionIOClient::factory(array(
					'appkey' => $options['app_key'],
					'apiurl' => $options['api_url']
					)),
				'ItemRecommendations',
				'ItemSimilarity');
		}


		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */

		add_action('admin_init', array( $this, 'piwp_initialize_options') );

		/*
		 * Setup the Server Command callbacks
		 */
		add_action( 'wp_ajax_piwp_populate_users', array( $this, 'piwp_populate_users') );
		add_action( 'wp_ajax_piwp_populate_posts', array( $this, 'piwp_populate_posts') );
	
		add_action( 'admin_notices', array($this, 'server_commands_notice' ) );
		add_action( 'admin_notices', array($this, 'predictionio_client_notice' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @TODO:
	 *
	 * - Rename "PredictionIOWP" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), PredictionIOWP::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @TODO:
	 *
	 * - Rename "PredictionIOWP" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts()
	{

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), PredictionIOWP::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Prediction.IO WordPress Admin Settings', $this->plugin_slug ),
			__( 'Prediction.IO Settings', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		$active_tab = '';

		if( isset( $_GET[ 'tab' ] ) ) {
			$active_tab = $_GET[ 'tab' ];
		} else if( $active_tab == 'server_commands' ) {
			$active_tab = 'server_commands';
		} else {
			$active_tab = 'connection_settings';
		}

		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @param arra $links Existing wordpress links
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * Creates a default array
	 *
	 * Apply a list of default values to the piwp_default_connection_settings_options tag
	 * 
	 * @return mixed The result of $defaults after all the hooked functions are applied to it
	 *
	 * @since    1.0.0
	 *
	 */
	function piwp_default_connection_settings_options() {
		$defaults = array(
			'app_key' => '',
			'api_url' => 'http://localhost:8000'
		);

		return apply_filters( 'piwp_default_connection_settings_options', $defaults );
	}

	/**
	 * Initialize the Plugin Admin Options
	 *
	 * This function creates a section for holding the various connection settings using the WP SettingsAPI
	 * It also registers custom settings for the connection setting section
	 *
	 * @since 1.0.0
	 */
	function piwp_initialize_options() {

		// If the plugin options don't exist, create them
		if( get_option( 'piwp_connection_settings' ) == false ) {
			add_option( 'piwp_connection_settings', apply_filters('piwp_default_connection_settings_options', $this->piwp_default_connection_settings_options() ));
		}

		// Register a section
		add_settings_section(
			'piwp_connection_settings_section',
			__('Prediction.IO WordPress Settings', $this->plugin_slug),
			array($this, 'piwp_settings_callback'),
			'piwp_connection_settings'
		);

		add_settings_field(
			'app_key',
			__('Prediction.IO APP Key', $this->plugin_slug),
			array($this, 'piwp_appkey_callback'),
			'piwp_connection_settings',
			'piwp_connection_settings_section'
		);

		add_settings_field(
			'api_url',
			__('Prediction.IO App Server URL', $this->plugin_slug),
			array($this, 'piwp_appurl_callback'),
			'piwp_connection_settings',
			'piwp_connection_settings_section'
		);

		register_setting(
			'piwp_connection_settings',
			'piwp_connection_settings'
		);
	}

	/**
	 * Settings for the Prediction.IO Connection Settings Admin page
	 *
	 * A callback function that gets executed when the connection settings section is displayed
	 *
	 * @since 	1.0.0
	 */
	function piwp_settings_callback() {
		echo '<p>' . __( 'Configure the connetion settings to the Prediction.IO server', $this->plugin_slug ) . '</p>';
	}

	/**
	 * The callback function for the app_key setting
	 * 
	 * @since 1.0.0
	 * 
	 */
	function piwp_appkey_callback() {
		$options = get_option( 'piwp_connection_settings' );

		echo '<input type="text" id="app_key" name="piwp_connection_settings[app_key]" value="' . $options['app_key'] . '" />';
	}

	/**
	 * The callback function for the api_url setting
	 * 
	 * @since 1.0.0
	 * 
	 */
	function piwp_appurl_callback() {
		$options = get_option( 'piwp_connection_settings' );

		echo '<input type="text" id="app_key" name="piwp_connection_settings[api_url]" value="' . $options['api_url'] . '" />';
	}

	/**
	 * A custom notice function that displays a notice if the app_key is not set
	 *
	 * @since 1.0.0
	 *
	 */
	function server_commands_notice(){
		$app_key = get_option('piwp_connection_settings');
		$app_key = $app_key['app_key'];

		if( isset($_REQUEST['tab']) && $_REQUEST['tab'] === 'server_commands' && empty($app_key) ) {
?>
		<div class="error">
			<p><?php echo __('You must enter an app key to use server commands', $this->plugin_slug); ?></p>
		</div>
<?			
		}
	}
	/**
	 * A custom notice function that displays a notice if the PredictionIOClient object is unable to
	 * be created
	 * 
	 * @since 1.0.0
	 */
	function predictionio_client_notice(){
		if($this->predictionIOAPI === null){
?>
		<div class="error">
			<p><?php echo __('The Prediction.IO Client must be setup to execute this function.', $this->plugin_slug); ?></p>
		</div>
<?			
		}
	}

	/**
	 * Populate all the users in the Prediciton.IO Server
	 *
	 * An internal function to populate users with the Prediction.IO Server
	 *
	 * @since 1.0.0
	 */
	public function piwp_populate_users() {
		global $wpdb;

		// Ensure that the Prediction.IO Client has been initalized
		if($this->predictionIOAPI === null) {
			do_action('prediction_io_notice');
			die();
		}

		$this->predictionIOAPI->adduser('2');
		$this->predictionIOAPI->adduser('2', true);
		
		die();
	}

	/**
	 * Populate a single user
	 *
	 * Add a single user to the Prediction.IO Server
	 *
	 * @param int|string $user_id The user id of the user to add
	 *
	 * @since 1.0.0
	 */
	public function piwp_populate_user($user_id) {
		// Ensure that the Prediction.IO Client has been initalized
		if($this->predictionIOAPI === null) {
			do_action('prediction_io_notice');
			die();
		}

		$this->predictionIOAPI->addUser($user_id);
		die();
	}

	/**
	 * Populate all or a subset of posts in the Prediction.IO Server
	 *
	 * @param array $item_ids A list of items to populate
	 *
	 * @since 1.0.0
	 *
	 */
	public function piwp_populate_posts($item_ids = null) {
		global $wpdb;
		
		// Ensure that the Prediction.IO Client has been initalized
		if($this->predictionIOAPI === null) {
			do_action('prediction_io_notice');
			die();
		}

		// Check if there are a list of item_ids to add or to default to all posts
		if($item_ids !== null) {
			// Get all the posts
			$query_string = "
				SELECT $wpdb->posts.id 
			 	FROM $wpdb->posts 
			 	WHERE $wpdb->posts.post_status = 'publish' 
			 	AND $wpdb->posts.post_type = 'post' 
			 	ORDER BY $wpdb->posts.post_date DESC
			";

			$item_ids = $wpdb->get_results($query_string);
		}

		// Add the posts to the Prediction.IO Server
		foreach($item_ids as $item) {
			// Check if this is an object or array string
			if(is_object($item)) {
				$item = $item->id;
			}

			$this->predictionIOAPI->addItem($item, 'post');
		}

		$return = array(
			'message' => 'The posts have been populated'
		);

		wp_send_json($return);
		die();
	}

	/**
	 * Populate a single item
	 *
	 * @param int|string $item_id The item id of the item to add to the Prediction.IO
	 *		server
	 * @param string $item_type The type of item to add
	 *
	 * @since 1.0.0
	 *
	 */
	public function piwp_populate_item($item_id, $item_type) {
		// Ensure that the Prediction.IO Client has been initalized
		if($this->predictionIOAPI === null) {
			do_action('prediction_io_notice');
			die();
		}

		$this->predictionIOAPI->addItem($item_id, $item_type);
		die();
	}

	/**
	* Record a page view
	*
	* @param int|string $user_id The user idea of the user to record the page view
	* @param int|string $item_id The id of the item to record the view on
	*
	* @since 1.0.0
	*
	*/
	public function piwp_record_item_view($user_id, $item_id) {
		// Ensure that the Prediction.IO Client has been initalized
		if($this->predictionIOAPI === null) {
			do_action('prediction_io_notice');
			die();
		}

		// Set the identity of the user to mark the item view
		$this->predictionIOAPI->registerView($user_id, $item_id);
		die();
	}
}