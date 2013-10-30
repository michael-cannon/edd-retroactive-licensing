<?php
/**
 * Plugin Name: EDD Retroactive Licensing
 * Plugin URI: http://aihr.us/easy-digital-downloads-retroactive-licensing/
 * Description: Send out license keys to users who bought software through Easy Digital Downloads before licensing was enabled.
 * Version: 0.0.1
 * Author: Michael Cannon
 * Author URI: http://aihr.us/about-aihrus/michael-cannon-resume/
 * License: GPLv2 or later
 */


/**
 * Copyright 2013 Michael Cannon (email: mc@aihr.us)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
class EDD_Retroactive_Licensing {
	const EDD_ID               = 'download';
	const EDD_PLUGIN_FILE      = 'easy-digital-downloads/easy-digital-downloads.php';
	const ID                   = 'edd-retroactive-licensing';
	const PLUGIN_FILE          = 'edd-retroactive-licensing/edd-retroactive-licensing.php';
	const REQUIRED_EDD_VERSION = '1.8.2.1';
	const SLUG                 = 'eddrl_';
	const VERSION              = '0.0.1';

	private static $base;
	private static $post_types;

	public static $donate_button;
	public static $menu_id;
	public static $settings_link;
	public static $settings_link_email;


	public function __construct() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_shortcode( 'eddrl_shortcode', array( $this, 'eddrl_shortcode' ) );
		self::$base = plugin_basename( __FILE__ );
	}


	public function admin_init() {
		if ( ! self::version_check() )
			return;

		$this->update();

		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

		self::$donate_button = <<<EOD
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="WM4F995W9LHXE">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
EOD;

		self::$settings_link       = '<a href="' . get_admin_url() . 'edit.php?post_type=' . self::EDD_ID . '&page=edd-settings&tab=extensions#EDD_Retroactive_Licensing">' . esc_html__( 'Settings' ) . '</a>';
		self::$settings_link_email = '<a href="' . get_admin_url() . 'edit.php?post_type=' . self::EDD_ID . '&page=edd-settings&tab=emails#EDD_Retroactive_Licensing">' . esc_html__( 'Emails' ) . '</a>';
	}


	public function admin_menu() {
		self::$menu_id = add_management_page( esc_html__( 'EDD Retroactive Licensing Processer', 'edd-retroactive-licensing' ), esc_html__( 'EDD Retroactive Licensing Processer', 'edd-retroactive-licensing' ), 'manage_options', self::ID, array( $this, 'user_interface' ) );

		add_action( 'admin_print_scripts-' . self::$menu_id, array( $this, 'scripts' ) );
		add_action( 'admin_print_styles-' . self::$menu_id, array( $this, 'styles' ) );
	}


	public function init() {
		add_action( 'wp_ajax_ajax_process_post', array( $this, 'ajax_process_post' ) );
		load_plugin_textdomain( self::ID, false, 'edd-retroactive-licensing/languages' );
		self::set_post_types();
	}


	public function plugin_action_links( $links, $file ) {
		if ( $file == self::$base ) {
			array_unshift( $links, self::$settings_link_email );
			array_unshift( $links, self::$settings_link );

			$link = '<a href="' . get_admin_url() . 'tools.php?page=' . self::ID . '">' . esc_html__( 'Process', 'edd-retroactive-licensing' ) . '</a>';
			array_unshift( $links, $link );
		}

		return $links;
	}


	public function activation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		if ( ! is_plugin_active( EDD_Retroactive_Licensing::EDD_PLUGIN_FILE ) ) {
			deactivate_plugins( EDD_Retroactive_Licensing::PLUGIN_FILE );
			add_action( 'admin_notices', array( 'EDD_Retroactive_Licensing', 'notice_edd_version' ) );
			return;
		}
	}


	public function deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	}


	public function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		global $wpdb;

		require_once 'lib/class-edd-retroactive-licensing-settings.php';
		$delete_data = self::get_edd_options( 'delete_data', false );
		if ( $delete_data ) {
			delete_option( EDD_Retroactive_Licensing_Settings::ID );
			$wpdb->query( 'OPTIMIZE TABLE `' . $wpdb->options . '`' );
		}
	}


	public static function plugin_row_meta( $input, $file ) {
		if ( $file != self::$base )
			return $input;

		$disable_donate = self::get_edd_options( 'disable_donate' );
		if ( $disable_donate )
			return $input;

		$links = array(
			'<a href="http://aihr.us/about-aihrus/donate/"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" alt="PayPal - The safer, easier way to pay online!" /></a>',
			'<a href="http://aihr.us/downloads/edd-retroactive-licensing-premium-wordpress-plugin/">Purchase EDD Retroactive Licensing Premium</a>',
		);

		$input = array_merge( $input, $links );

		return $input;
	}


	public static function set_post_types() {
		$post_types       = get_post_types( array( 'public' => true ), 'names' );
		self::$post_types = array();
		foreach ( $post_types as $post_type )
			self::$post_types[] = $post_type;
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function user_interface() {
		// Capability check
		if ( ! current_user_can( 'manage_options' ) )
			wp_die( $this->post_id, esc_html__( "Your user account doesn't have permission to access this.", 'edd-retroactive-licensing' ) );

?>

<div id="message" class="updated fade" style="display:none"></div>

<div class="wrap wpsposts">
	<div class="icon32" id="icon-tools"></div>
	<h2><?php _e( 'EDD Retroactive Licensing Processer', 'edd-retroactive-licensing' ); ?></h2>

<?php
		if ( self::get_edd_options( 'debug_mode' ) ) {
			$posts_to_import = self::get_edd_options( 'posts_to_import' );
			$posts_to_import = explode( ',', $posts_to_import );
			foreach ( $posts_to_import as $post_id ) {
				$this->post_id = $post_id;
				$this->ajax_process_post();
			}

			exit( __LINE__ . ':' . basename( __FILE__ ) . " DONE<br />\n" );
		}

		// If the button was clicked
		if ( ! empty( $_POST[ self::ID ] ) || ! empty( $_REQUEST['posts'] ) ) {
			// Form nonce check
			check_admin_referer( self::ID );

			// Create the list of image IDs
			if ( ! empty( $_REQUEST['posts'] ) ) {
				$posts = explode( ',', trim( $_REQUEST['posts'], ',' ) );
				$posts = array_map( 'intval', $posts );
			} else {
				$posts = self::get_posts_to_process();
			}

			$count = count( $posts );
			if ( ! $count ) {
				echo '	<p>' . _e( 'All done. No posts needing processing found.', 'edd-retroactive-licensing' ) . '</p></div>';
				return;
			}

			$posts = implode( ',', $posts );
			$this->show_status( $count, $posts );
		} else {
			// No button click? Display the form.
			$this->show_greeting();
		}
?>
	</div>
<?php
	}


	public static function get_posts_to_process() {
		global $wpdb;

		$query = array(
			'post_status' => array( 'publish', 'private' ),
			'post_type' => self::$post_types,
			'orderby' => 'post_modified',
			'order' => 'DESC',
		);

		$include_ids = self::get_edd_options( 'posts_to_import' );
		if ( $include_ids ) {
			$query[ 'post__in' ] = str_getcsv( $include_ids );
		} else {
			$query['posts_per_page'] = 1;
			$query['meta_query']     = array(
				array(
					'key' => 'TBD',
					'value' => '',
					'compare' => '!=',
				),
			);
			unset( $query['meta_query'] );
		}

		$skip_ids = self::get_edd_options( 'skip_importing_post_ids' );
		if ( $skip_ids )
			$query[ 'post__not_in' ] = str_getcsv( $skip_ids );

		$results  = new WP_Query( $query );
		$query_wp = $results->request;

		$limit = self::get_edd_options( 'limit' );
		if ( $limit )
			$query_wp = preg_replace( '#\bLIMIT 0,.*#', 'LIMIT 0,' . $limit, $query_wp );
		else
			$query_wp = preg_replace( '#\bLIMIT 0,.*#', '', $query_wp );

		$posts = $wpdb->get_col( $query_wp );

		return $posts;
	}


	public function show_greeting() {
?>
	<form method="post" action="">
<?php wp_nonce_field( self::ID ); ?>

	<p><?php _e( 'Use this tool to process posts for TBD.', 'edd-retroactive-licensing' ); ?></p>

	<p><?php _e( 'This processing is not reversible. Backup your database beforehand or be prepared to revert each transformmed post manually.', 'edd-retroactive-licensing' ); ?></p>

	<p><?php printf( esc_html__( 'Please review your %s before proceeding.', 'edd-retroactive-licensing' ), self::$settings_link ); ?></p>

	<p><?php _e( 'To begin, just press the button below.', 'edd-retroactive-licensing' ); ?></p>

	<p><input type="submit" class="button hide-if-no-js" name="<?php echo self::ID; ?>" id="<?php echo self::ID; ?>" value="<?php _e( 'Process EDD Retroactive Licensing', 'edd-retroactive-licensing' ) ?>" /></p>

	<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'edd-retroactive-licensing' ) ?></em></p></noscript>

	</form>
<?php
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function show_status( $count, $posts ) {
		echo '<p>' . esc_html__( 'Please be patient while this script run. This can take a while, up to a minute per post. Do not navigate away from this page until this script is done or the import will not be completed. You will be notified via this page when the import is completed.', 'edd-retroactive-licensing' ) . '</p>';

		echo '<p>' . sprintf( esc_html__( 'Estimated time required to import is %1$s minutes.', 'edd-retroactive-licensing' ), ( $count * 1 ) ) . '</p>';

		$text_goback = ( ! empty( $_GET['goback'] ) ) ? sprintf( __( 'To go back to the previous page, <a href="%s">click here</a>.', 'edd-retroactive-licensing' ), 'javascript:history.go(-1)' ) : '';

		$text_failures = sprintf( __( 'All done! %1$s posts were successfully processed in %2$s seconds and there were %3$s failures. To try importing the failed posts again, <a href="%4$s">click here</a>. %5$s', 'edd-retroactive-licensing' ), "' + rt_successes + '", "' + rt_totaltime + '", "' + rt_errors + '", esc_url( wp_nonce_url( admin_url( 'tools.php?page=' . self::ID . '&goback=1' ) ) . '&posts=' ) . "' + rt_failedlist + '", $text_goback );

		$text_nofailures = sprintf( esc_html__( 'All done! %1$s posts were successfully processed in %2$s seconds and there were no failures. %3$s', 'edd-retroactive-licensing' ), "' + rt_successes + '", "' + rt_totaltime + '", $text_goback );
?>

	<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'edd-retroactive-licensing' ) ?></em></p></noscript>

	<div id="wpsposts-bar" style="position:relative;height:25px;">
		<div id="wpsposts-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
	</div>

	<p><input type="button" class="button hide-if-no-js" name="wpsposts-stop" id="wpsposts-stop" value="<?php _e( 'Abort Processing Posts', 'edd-retroactive-licensing' ) ?>" /></p>

	<h3 class="title"><?php _e( 'Debugging Information', 'edd-retroactive-licensing' ) ?></h3>

	<p>
		<?php printf( esc_html__( 'Total Postss: %s', 'edd-retroactive-licensing' ), $count ); ?><br />
		<?php printf( esc_html__( 'Posts Processed: %s', 'edd-retroactive-licensing' ), '<span id="wpsposts-debug-successcount">0</span>' ); ?><br />
		<?php printf( esc_html__( 'Process Failures: %s', 'edd-retroactive-licensing' ), '<span id="wpsposts-debug-failurecount">0</span>' ); ?>
	</p>

	<ol id="wpsposts-debuglist">
		<li style="display:none"></li>
	</ol>

	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function($){
			var i;
			var rt_posts = [<?php echo esc_attr( $posts ); ?>];
			var rt_total = rt_posts.length;
			var rt_count = 1;
			var rt_percent = 0;
			var rt_successes = 0;
			var rt_errors = 0;
			var rt_failedlist = '';
			var rt_resulttext = '';
			var rt_timestart = new Date().getTime();
			var rt_timeend = 0;
			var rt_totaltime = 0;
			var rt_continue = true;

			// Create the progress bar
			$( "#wpsposts-bar" ).progressbar();
			$( "#wpsposts-bar-percent" ).html( "0%" );

			// Stop button
			$( "#wpsposts-stop" ).click(function() {
				rt_continue = false;
				$( '#wpsposts-stop' ).val( "<?php echo esc_html__( 'Stopping, please wait a moment.', 'edd-retroactive-licensing' ); ?>" );
			});

			// Clear out the empty list element that's there for HTML validation purposes
			$( "#wpsposts-debuglist li" ).remove();

			// Called after each import. Updates debug information and the progress bar.
			function WPSPostsUpdateStatus( id, success, response ) {
				$( "#wpsposts-bar" ).progressbar( "value", ( rt_count / rt_total ) * 100 );
				$( "#wpsposts-bar-percent" ).html( Math.round( ( rt_count / rt_total ) * 1000 ) / 10 + "%" );
				rt_count = rt_count + 1;

				if ( success ) {
					rt_successes = rt_successes + 1;
					$( "#wpsposts-debug-successcount" ).html(rt_successes);
					$( "#wpsposts-debuglist" ).append( "<li>" + response.success + "</li>" );
				}
				else {
					rt_errors = rt_errors + 1;
					rt_failedlist = rt_failedlist + ',' + id;
					$( "#wpsposts-debug-failurecount" ).html(rt_errors);
					$( "#wpsposts-debuglist" ).append( "<li>" + response.error + "</li>" );
				}
			}

			// Called when all posts have been processed. Shows the results and cleans up.
			function WPSPostsFinishUp() {
				rt_timeend = new Date().getTime();
				rt_totaltime = Math.round( ( rt_timeend - rt_timestart ) / 1000 );

				$( '#wpsposts-stop' ).hide();

				if ( rt_errors > 0 ) {
					rt_resulttext = '<?php echo $text_failures; ?>';
				} else {
					rt_resulttext = '<?php echo $text_nofailures; ?>';
				}

				$( "#message" ).html( "<p><strong>" + rt_resulttext + "</strong></p>" );
				$( "#message" ).show();
			}

			// Regenerate a specified image via AJAX
			function WPSPosts( id ) {
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: "ajax_process_post",
						id: id
					},
					success: function( response ) {
						if ( response.success ) {
							WPSPostsUpdateStatus( id, true, response );
						}
						else {
							WPSPostsUpdateStatus( id, false, response );
						}

						if ( rt_posts.length && rt_continue ) {
							WPSPosts( rt_posts.shift() );
						}
						else {
							WPSPostsFinishUp();
						}
					},
					error: function( response ) {
						WPSPostsUpdateStatus( id, false, response );

						if ( rt_posts.length && rt_continue ) {
							WPSPosts( rt_posts.shift() );
						}
						else {
							WPSPostsFinishUp();
						}
					}
				});
			}

			WPSPosts( rt_posts.shift() );
		});
	// ]]>
	</script>
<?php
	}


	/**
	 * Process a single post ID (this is an AJAX handler)
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function ajax_process_post() {
		if ( ! self::get_edd_options( 'debug_mode' ) ) {
			error_reporting( 0 ); // Don't break the JSON result
			header( 'Content-type: application/json' );
			$this->post_id = intval( $_REQUEST['id'] );
		}

		$post = get_post( $this->post_id );
		if ( ! $post || ! in_array( $post->post_type, self::$post_types )  )
			die( json_encode( array( 'error' => sprintf( esc_html__( 'Failed Processing: %s is incorrect post type.', 'edd-retroactive-licensing' ), esc_html( $this->post_id ) ) ) ) );

		$this->do_something( $this->post_id, $post );

		die( json_encode( array( 'success' => sprintf( __( '&quot;<a href="%1$s" target="_blank">%2$s</a>&quot; Post ID %3$s was successfully processed in %4$s seconds.', 'edd-retroactive-licensing' ), get_permalink( $this->post_id ), esc_html( get_the_title( $this->post_id ) ), $this->post_id, timer_stop() ) ) ) );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function do_something( $post_id, $post ) {
		// do something there with the post
		// use error_log to track happenings
	}


	public function admin_notices_0_0_1() {
		$content  = '<div class="updated fade"><p>';
		$content .= sprintf( __( 'If your EDD Retroactive Licensing display has gone to funky town, please <a href="%s">read the FAQ</a> about possible CSS fixes.', 'edd-retroactive-licensing' ), 'https://aihrus.zendesk.com/entries/23722573-Major-Changes-Since-2-10-0' );
		$content .= '</p></div>';

		echo $content;
	}


	public function update() {
		$prior_version = self::get_edd_options( 'version' );
		if ( $prior_version ) {
			if ( $prior_version < '0.0.1' )
				add_action( 'admin_notices', array( $this, 'admin_notices_0_0_1' ) );

			if ( $prior_version < self::VERSION ) {
				do_action( 'eddrl_update' );
				self::set_edd_options( self::SLUG . 'version', self::VERSION );
			}
		}
	}


	public static function scripts() {
		if ( is_admin() ) {
			wp_enqueue_script( 'jquery' );

			wp_register_script( 'jquery-ui-progressbar', plugins_url( 'js/jquery.ui.progressbar.js', __FILE__ ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-widget' ), '1.10.3' );
			wp_enqueue_script( 'jquery-ui-progressbar' );
		}

		do_action( 'eddrl_scripts' );
	}


	public static function styles() {
		if ( is_admin() ) {
			wp_register_style( 'jquery-ui-progressbar', plugins_url( 'css/redmond/jquery-ui-1.10.3.custom.min.css', __FILE__ ), false, '1.10.3' );
			wp_enqueue_style( 'jquery-ui-progressbar' );
		} else {
			wp_register_style( __CLASS__, plugins_url( 'edd-retroactive-licensing.css', __FILE__ ) );
			wp_enqueue_style( __CLASS__ );
		}

		do_action( 'eddrl_styles' );
	}


	public static function eddrl_shortcode( $atts ) {
		self::scripts();
		self::styles();

		return __CLASS__ . ' shortcode';
	}


	public function notice_edd_version() {
		$edd_slug  = 'easy-digital-downloads';
		$is_active = is_plugin_active( self::EDD_PLUGIN_FILE );

		if ( $is_active ) {
			$link = sprintf( __( '<a href="%1$s">update to</a>' ), self_admin_url( 'update-core.php' ) );
		} else {
			$plugins = get_plugins();
			if ( empty( $plugins[ self::EDD_PLUGIN_FILE ] ) ) {
				$install = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $edd_slug ), 'install-plugin_' . $edd_slug ) );
				$link    = sprintf( __( '<a href="%1$s">install</a>' ), $install );
			} else {
				$activate = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . self::EDD_PLUGIN_FILE ), 'activate-plugin_' . self::EDD_PLUGIN_FILE ) );
				$link     = sprintf( __( '<a href="%1$s">activate</a>' ), $activate );
			}
		}

		$content  = '<div class="error"><p>';
		$content .= sprintf( __( 'Plugin EDD Retroactive Licensing has been deactivated. Please %1$s Easy Digital Sales version %2$s or newer before activating EDD Retroactive Licensing.' ), $link, self::REQUIRED_EDD_VERSION );
		$content .= '</p></div>';

		echo $content;
	}


	public function version_check() {
		$good_version = true;

		if ( is_plugin_inactive( self::EDD_PLUGIN_FILE ) || EDD_VERSION < self::REQUIRED_EDD_VERSION )
			$good_version = false;

		if ( ! $good_version && is_plugin_active( self::$base ) ) {
			deactivate_plugins( self::$base );
			add_action( 'admin_notices', array( $this, 'notice_edd_version' ) );
		}

		return $good_version;
	}


	public static function get_edd_options( $key = null, $default = null ) {
		global $edd_options;

		if ( is_null( $key ) )
			return $edd_options;
		elseif ( isset( $edd_options[ self::SLUG . $key ] ) )
			return $edd_options[ self::SLUG . $key ];
		elseif ( isset( $edd_options[ $key ] ) )
			return $edd_options[ $key ];
		else
			return $default;
	}


	public static function set_edd_options( $key, $default ) {
		global $edd_options;

		$edd_options[ $key ] = $default;
	}


}


if ( ! class_exists( 'EDD_License' ) )
	include_once dirname( __FILE__ ) . '/lib/EDD_License_Handler.php';

$license = new EDD_License( __FILE__, 'EDD Retroactive Licensing', EDD_Retroactive_Licensing::VERSION, 'Michael Cannon' );


register_activation_hook( __FILE__, array( 'EDD_Retroactive_Licensing', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'EDD_Retroactive_Licensing', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'EDD_Retroactive_Licensing', 'uninstall' ) );


add_action( 'plugins_loaded', 'eddrl_init', 99 );


/**
 *
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
function eddrl_init() {
	if ( ! is_admin() )
		return;

	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( EDD_Retroactive_Licensing::PLUGIN_FILE ) ) {
		if ( is_plugin_active( EDD_Retroactive_Licensing::EDD_PLUGIN_FILE ) ) {
			global $EDD_Retroactive_Licensing;
			if ( is_null( $EDD_Retroactive_Licensing ) )
				$EDD_Retroactive_Licensing = new EDD_Retroactive_Licensing();
		} else {
			deactivate_plugins( EDD_Retroactive_Licensing::PLUGIN_FILE );
			add_action( 'admin_notices', array( 'EDD_Retroactive_Licensing', 'notice_edd_version' ) );
		}
	}
}


?>
