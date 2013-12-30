<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   PredictionIOWP
 * @author    Matt Read <mread@ideacouture.com>
 * @license   GPL-2.0+
 * @link      http://www.ideacouture.com
 * @copyright 2013 Idea Couture
 */
?>

<div class="wrap">
	<div id="icon-themes" class="icon32"></div>
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<?php settings_errors(); ?>


	<h2 class="nav-tab-wrapper">
		<a href="?page=<?php echo $this->plugin_slug; ?>&tab=connection_settings" class="nav-tab <?php echo $active_tab == 'connection_settings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Connection Settings', $this->plugin_slug ); ?></a>
		<a href="?page=<?php echo $this->plugin_slug; ?>&tab=server_commands" class="nav-tab <?php echo $active_tab == 'server_commands' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Server Commands', $this->plugin_slug ); ?></a>
	</h2>

	<?php
		if($active_tab == 'connection_settings') {
	?>
	<form method="post" action="options.php">
	<?php		
			settings_fields( 'piwp_connection_settings' );
			do_settings_sections( 'piwp_connection_settings' );
			submit_button();
	?>
	</form>
	<?php
		} elseif ($active_tab == 'server_commands' ) {
			// Load the server commands panel
			include_once( 'server_commands.php' );
		}

	?>
</div>
