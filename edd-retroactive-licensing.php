<?php
/**
 * Plugin Name: Easy Digital Downloads - Retroactive Licensing
 * Plugin URI: http://aihr.us/easy-digital-downloads-retroactive-licensing/
 * Description: Send out license keys and activation reminders to users who bought products through Easy Digital Downloads before software licensing was enabled.
 * Version: 1.1.2
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
	const EDD_PT                 = 'download';
	const EDD_LICENSE_PT         = 'edd_license';
	const EDD_PAYMENT_PT         = 'edd_payment';
	const EDD_PLUGIN_FILE        = 'easy-digital-downloads/easy-digital-downloads.php';
	const EDDSL_PLUGIN_FILE      = 'edd-software-licensing/edd-software-licenses.php';
	const ID                     = 'edd-retroactive-licensing';
	const PLUGIN_FILE            = 'edd-retroactive-licensing/edd-retroactive-licensing.php';
	const REQUIRED_EDD_VERSION   = '1.8.5';
	const REQUIRED_EDDSL_VERSION = '2.1';
	const SLUG                   = 'eddrl_';
	const VERSION                = '1.1.2';

	private static $base;
	private static $post_types;

	public static $menu_id;
	public static $notice_key;
	public static $payment_history_url;
	public static $post_id;
	public static $settings_link;
	public static $settings_link_email;


	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );

		self::set_base();
		self::set_post_types();
	}


	public static function admin_init() {
		if ( ! self::version_check() )
			return;

		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );

		self::$settings_link       = '<a href="' . get_admin_url() . 'edit.php?post_type=' . self::EDD_PT . '&page=edd-settings&tab=extensions#EDD_Retroactive_Licensing">' . esc_html__( 'Settings', 'edd-retroactive-licensing' ) . '</a>';
		self::$settings_link_email = '<a href="' . get_admin_url() . 'edit.php?post_type=' . self::EDD_PT . '&page=edd-settings&tab=emails#EDD_Retroactive_Licensing">' . esc_html__( 'Emails', 'edd-retroactive-licensing' ) . '</a>';
	}


	public static function admin_menu() {
		self::$menu_id = add_submenu_page( 'edit.php?post_type=' . self::EDD_PT, esc_html__( 'EDD Retroactive Licensing Processer', 'edd-retroactive-licensing' ), esc_html__( 'Retroactive Licensing', 'edd-retroactive-licensing' ), 'manage_options', self::ID, array( __CLASS__, 'user_interface' ) );

		add_action( 'admin_print_scripts-' . self::$menu_id, array( __CLASS__, 'scripts' ) );
		add_action( 'admin_print_styles-' . self::$menu_id, array( __CLASS__, 'styles' ) );
	}


	public static function init() {
		add_action( 'wp_ajax_ajax_process_post', array( __CLASS__, 'ajax_process_post' ) );
		add_filter( 'edd_email_template_tags', array( __CLASS__, 'edd_email_template_tags' ), 10, 4 );
		add_filter( 'edd_settings_emails', array( __CLASS__, 'edd_settings_emails' ), 10, 1 );
		add_filter( 'edd_settings_extensions', array( __CLASS__, 'edd_settings_extensions' ), 10, 1 );

		load_plugin_textdomain( self::ID, false, 'edd-retroactive-licensing/languages' );

		self::$payment_history_url = admin_url( 'edit.php?post_type=download&page=edd-payment-history' );
	}


	public static function plugin_action_links( $links, $file ) {
		if ( $file == self::$base ) {
			array_unshift( $links, self::$settings_link_email );
			array_unshift( $links, self::$settings_link );

			$link = '<a href="' . get_admin_url() . 'edit.php?post_type=' . self::EDD_PT . '&page=' . self::ID . '">' . esc_html__( 'Process', 'edd-retroactive-licensing' ) . '</a>';
			array_unshift( $links, $link );
		}

		return $links;
	}


	public static function activation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		if ( ! is_plugin_active( EDD_Retroactive_Licensing::EDD_PLUGIN_FILE ) ) {
			deactivate_plugins( EDD_Retroactive_Licensing::PLUGIN_FILE );
			EDD_Retroactive_Licensing::set_notice( 'notice_version' );

			return;
		}

		if ( ! is_plugin_active( EDD_Retroactive_Licensing::EDDSL_PLUGIN_FILE ) ) {
			deactivate_plugins( EDD_Retroactive_Licensing::PLUGIN_FILE );
			EDD_Retroactive_Licensing::set_notice( 'notice_eddsl' );

			return;
		}
	}


	public static function deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	}


	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function user_interface() {
		// Capability check
		if ( ! current_user_can( 'manage_options' ) )
			wp_die( self::$post_id, esc_html__( 'Your user account doesn\'t have permission to access this.', 'edd-retroactive-licensing' ) );

?>

<div id="message" class="updated fade" style="display:none"></div>

<div class="wrap wpsposts">
	<div class="icon32" id="icon-tools"></div>
	<h2><?php _e( 'Easy Digitial Downloads - Retroactive Licensing Processer', 'edd-retroactive-licensing' ); ?></h2>

<?php

		// If the button was clicked
		if ( ! empty( $_POST[ self::ID ] ) || ! empty( $_REQUEST['posts'] ) ) {
			// Form nonce check
			check_admin_referer( self::ID );

			// Create the list of image IDs
			if ( ! empty( $_REQUEST['posts'] ) ) {
				$posts = explode( ',', trim( $_REQUEST['posts'], ',' ) );
				$posts = array_map( 'intval', $posts );
			} else {
				$unlicensed = self::get_unlicensed_payments();
				$inactive   = self::get_inactive_licenses();

				$posts = array_merge( $unlicensed, $inactive );
			}

			$count = count( $posts );
			if ( ! $count ) {
				echo '<h3>' . esc_html__( 'All Done', 'edd-retroactive-licensing' ) . '</h3>';
				echo '<p>' . esc_html__( 'No purchases needing licenses found.', 'edd-retroactive-licensing' ) . '</p>';
				echo '<p>' . sprintf( esc_html__( 'In case this wasn\'t expected, are you sure you\'ve allowed the products and enabled license provisioning in %s?', 'edd-retroactive-licensing' ), self::$settings_link ) . '</p>';
				echo '</div>';

				return;
			}

			$posts = "'" . implode( "','", $posts ) . "'";
			self::show_status( $count, $posts );
		} else {
			// No button click? Display the form.
			self::show_greeting();
		}
?>
	</div>
<?php
	}


	public static function get_inactive_licenses() {
		global $wpdb;

		$enable_licensing = self::get_edd_options( 'remind_enable' );
		if ( ! $enable_licensing )
			return array();

		$products = self::get_edd_options( 'allowed_products' );
		if ( empty( $products ) )
			return array();

		$products     = array_keys( $products );
		$products_csv = implode( ',', $products );

		$inactive_query = <<<EOD
			SELECT pm.meta_value
			FROM {$wpdb->postmeta} pm
			WHERE 1 = 1
				AND pm.meta_key = '_edd_sl_payment_id'
				AND pm.post_id IN (
					SELECT post_id
					FROM {$wpdb->postmeta}
					WHERE 1 = 1
						AND meta_key = '_edd_sl_status'
						AND meta_value LIKE 'inactive'
				)
				AND pm.post_id IN (
					SELECT post_id
					FROM {$wpdb->postmeta}
					WHERE 1 = 1
						AND meta_key = '_edd_sl_download_id'
						AND meta_value IN ( {$products_csv} )
				)
EOD;

		$inactives = $wpdb->get_col( $inactive_query );
		$inactives = apply_filters( 'eddrl_payments', $inactives );

		return $inactives;
	}


	public static function get_unlicensed_payments() {
		global $wpdb;

		$enable_licensing = self::get_edd_options( 'initial_enable' );
		if ( ! $enable_licensing )
			return array();

		$products = self::get_edd_options( 'allowed_products' );
		if ( empty( $products ) )
			return array();

		$products     = array_keys( $products );
		$products_csv = implode( ',', $products );

		$license_query = <<<EOD
			SELECT pm.meta_value
			FROM {$wpdb->postmeta} pm
			WHERE 1 = 1
				AND pm.meta_key = '_edd_sl_payment_id'
				AND pm.post_id NOT IN (
					SELECT post_id
					FROM {$wpdb->postmeta}
					WHERE 1 = 1
						AND meta_key = '_edd_sl_download_id'
						AND meta_value IN ( {$products_csv} )
				)
EOD;

		$post__not_in = $wpdb->get_col( $license_query );

		$post__in = array();
		foreach ( $products as $product ) {
			$args  = array(
				'download' => $product,
				'number' => -1,
			);
			$query = new EDD_Payments_Query( $args );

			$payments = $query->get_payments();

			foreach ( $payments as $payment )
				$post__in[] = $payment->ID;
		}

		$query = array(
			'post_status' => array( 'publish', 'edd_subscription' ),
			'post_type' => self::$post_types,
			'orderby' => 'post_modified',
			'order' => 'DESC',
			'posts_per_page' => 1,
		);

		if ( ! empty( $post__in ) && ! empty( $post__not_in ) ) {
			$post__in          = array_diff( $post__in, $post__not_in );
			$post__in          = array_unique( $post__in );
			$query['post__in'] = $post__in;
		} elseif ( ! empty( $post__in ) ) {
			$post__in          = array_unique( $post__in );
			$query['post__in'] = $post__in;
		} elseif ( ! empty( $post__not_in ) ) {
			$post__not_in          = array_unique( $post__not_in );
			$query['post__not_in'] = $post__not_in;
		}

		$results  = new WP_Query( $query );
		$query_wp = $results->request;
		$query_wp = preg_replace( '#\bLIMIT 0,.*#', '', $query_wp );

		$payments = $wpdb->get_col( $query_wp );
		$payments = apply_filters( 'eddrl_payments', $payments );

		return $payments;
	}


	public static function show_greeting() {
?>
	<form method="post" action="">
<?php wp_nonce_field( self::ID ); ?>

	<p><?php _e( 'Use this tool to provision licenses for unlicensed Easy Digital Downloads products.', 'edd-retroactive-licensing' ); ?></p>

	<p><?php _e( 'This processing is not reversible. Backup your database beforehand or be prepared to revert each transformed post manually.', 'edd-retroactive-licensing' ); ?></p>

	<p><?php printf( esc_html__( 'Please review your %s before proceeding.', 'edd-retroactive-licensing' ), self::$settings_link ); ?></p>

	<p><?php _e( 'To begin, just press the button below.', 'edd-retroactive-licensing' ); ?></p>

	<p><input type="submit" class="button hide-if-no-js" name="<?php echo self::ID; ?>" id="<?php echo self::ID; ?>" value="<?php _e( 'Perform EDD Retroactive Licensing', 'edd-retroactive-licensing' ) ?>" /></p>

	<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'edd-retroactive-licensing' ) ?></em></p></noscript>

	</form>
<?php
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function show_status( $count, $posts ) {
		echo '<p>' . esc_html__( 'Please be patient while this script run. This can take a while, up to a minute per post. Do not navigate away from this page until this script is done or the licensing will not be completed. You will be notified via this page when the licensing is completed.', 'edd-retroactive-licensing' ) . '</p>';

		echo '<p>' . sprintf( esc_html__( 'Estimated time required to send licenses is %1$s minutes.', 'edd-retroactive-licensing' ), number_format( $count * .33 ) ) . '</p>';

		$text_goback = ( ! empty( $_GET['goback'] ) ) ? sprintf( __( 'To go back to the previous page, <a href="%s">click here</a>.', 'edd-retroactive-licensing' ), 'javascript:history.go(-1)' ) : '';

		$text_failures = sprintf( __( 'All done! %1$s posts were successfully processed in %2$s seconds and there were %3$s failures. To try importing the failed posts again, <a href="%4$s">click here</a>. %5$s', 'edd-retroactive-licensing' ), "' + rt_successes + '", "' + rt_totaltime + '", "' + rt_errors + '", esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=' . self::EDD_PT . '&?page=' . self::ID . '&goback=1' ) ) . '&posts=' ) . "' + rt_failedlist + '", $text_goback );

		$text_nofailures = sprintf( esc_html__( 'All done! %1$s posts were successfully processed in %2$s seconds and there were no failures. %3$s', 'edd-retroactive-licensing' ), "' + rt_successes + '", "' + rt_totaltime + '", $text_goback );
?>

	<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'edd-retroactive-licensing' ) ?></em></p></noscript>

	<div id="wpsposts-bar" style="position:relative;height:25px;">
		<div id="wpsposts-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
	</div>

	<p><input type="button" class="button hide-if-no-js" name="wpsposts-stop" id="wpsposts-stop" value="<?php _e( 'Abort Licensing Posts', 'edd-retroactive-licensing' ) ?>" /></p>

	<h3 class="title"><?php _e( 'Status', 'edd-retroactive-licensing' ) ?></h3>

	<p>
		<?php printf( esc_html__( 'Total Payments: %s', 'edd-retroactive-licensing' ), $count ); ?><br />
		<?php printf( esc_html__( 'Payments Processed: %s', 'edd-retroactive-licensing' ), '<span id="wpsposts-debug-successcount">0</span>' ); ?><br />
		<?php printf( esc_html__( 'License Failures: %s', 'edd-retroactive-licensing' ), '<span id="wpsposts-debug-failurecount">0</span>' ); ?>
	</p>

	<ol id="wpsposts-debuglist">
		<li style="display:none"></li>
	</ol>

	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function($){
			var i;
			var rt_posts = [<?php echo $posts; ?>];
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
	public static function ajax_process_post() {
		error_reporting( 0 ); // Don't break the JSON result
		header( 'Content-type: application/json' );

		$payment_id = intval( $_REQUEST['id'] );
		$post       = get_post( $payment_id );

		if ( ! $post || ! in_array( $post->post_type, self::$post_types )  )
			die( json_encode( array( 'error' => sprintf( esc_html__( 'Failed Licensing: %s is incorrect post type.', 'edd-retroactive-licensing' ), esc_html( $payment_id ) ) ) ) );

		$success = self::handle_licensing( $payment_id, $post );
		if ( true === $success )
			die( json_encode( array( 'success' => sprintf( __( '&quot;<a href="%1$s" target="_blank">%2$s</a>&quot; Payment ID %3$s was successfully sent a license.', 'edd-retroactive-licensing' ), self::get_order_url( $payment_id ), esc_html( get_the_title( $payment_id ) ), $payment_id ) ) ) );
		elseif ( 'reminded' === $success )
			die( json_encode( array( 'success' => sprintf( __( '&quot;<a href="%1$s" target="_blank">%2$s</a>&quot; Payment ID %3$s was reminded to set their license.', 'edd-retroactive-licensing' ), self::get_order_url( $payment_id ), esc_html( get_the_title( $payment_id ) ), $payment_id ) ) ) );
		else
			die( json_encode( array( 'error' => sprintf( __( '&quot;<a href="%1$s" target="_blank">%2$s</a>&quot; Payment ID %3$s was NOT licensed because "%4$".', 'edd-retroactive-licensing' ), self::get_order_url( $payment_id ), esc_html( get_the_title( $payment_id ) ), $payment_id, $success ) ) ) );
	}


	public static function generate_license_keys( $payment_id ) {
		$payment_id = absint( $payment_id );
		if ( empty( $payment_id ) )
			return esc_html__( 'Empty `$payment_id`', 'edd-retroactive-licensing' );

		$downloads = edd_get_payment_meta_downloads( $payment_id );
		if ( empty( $downloads ) )
			return esc_html__( 'No payment downloads found', 'edd-retroactive-licensing' );

		foreach ( $downloads as $download ) {
			$type = edd_get_download_type( $download['id'] );
			edd_software_licensing()->generate_license( $download['id'], $payment_id, $type );
		}

		return true;
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
		}

		do_action( 'eddrl_styles' );
	}


	public static function notice_version() {
		$edd_slug  = 'easy-digital-downloads';
		$is_active = is_plugin_active( self::EDD_PLUGIN_FILE );

		if ( $is_active ) {
			$link = sprintf( __( '<a href="%1$s">update to</a>', 'edd-retroactive-licensing' ), self_admin_url( 'update-core.php' ) );
		} else {
			$plugins = get_plugins();
			if ( empty( $plugins[ self::EDD_PLUGIN_FILE ] ) ) {
				$install = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $edd_slug ), 'install-plugin_' . $edd_slug ) );
				$link    = sprintf( __( '<a href="%1$s">install</a>', 'edd-retroactive-licensing' ), $install );
			} else {
				$activate = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . self::EDD_PLUGIN_FILE ), 'activate-plugin_' . self::EDD_PLUGIN_FILE ) );
				$link     = sprintf( __( '<a href="%1$s">activate</a>', 'edd-retroactive-licensing' ), $activate );
			}
		}

		$content  = '<div class="error"><p>';
		$content .= sprintf( __( 'Plugin %3$s has been deactivated. Please %1$s Easy Digital Sales version %2$s or newer before activating %3$s.', 'edd-retroactive-licensing' ), $link, self::REQUIRED_EDD_VERSION, 'EDD Retroactive Licensing' );
		$content .= '</p></div>';

		echo $content;
	}


	public static function version_check() {
		$edd_okay   = true;
		$eddsl_okay = true;

		if ( is_null( self::$base ) )
			self::set_base();

		if ( ! is_plugin_active( self::$base ) )
			$edd_okay = false;

		if ( is_plugin_inactive( self::EDD_PLUGIN_FILE ) || EDD_VERSION < self::REQUIRED_EDD_VERSION )
			$edd_okay = false;

		if ( ! $edd_okay && is_plugin_active( self::$base ) ) {
			deactivate_plugins( self::$base );
			self::set_notice( 'notice_version' );
		}

		if ( is_plugin_inactive( self::EDDSL_PLUGIN_FILE ) )
			$eddsl_okay = false;

		if ( ! $eddsl_okay && is_plugin_active( self::$base ) ) {
			deactivate_plugins( self::$base );
			self::set_notice( 'notice_eddsl' );
		}

		$good_version = $edd_okay && $eddsl_okay;

		// never going to fire because version isn't set at this point
		$prior_version = self::get_edd_options( 'version' );
		if ( $good_version && $prior_version ) {
			if ( $prior_version < self::VERSION ) {
				do_action( 'eddrl_update' );
				self::set_edd_options( self::SLUG . 'version', self::VERSION );
			}
		}

		if ( ! $good_version )
			self::check_notices();
		else
			self::clear_notices();

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


	public static function notice_eddsl() {
		$eddsl_slug = 'edd-software-licensing';

		$plugins = get_plugins();
		if ( empty( $plugins[ self::EDDSL_PLUGIN_FILE ] ) ) {
			$install = esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $eddsl_slug ), 'install-plugin_' . $eddsl_slug ) );
			$link    = sprintf( __( '<a href="%1$s">install</a>', 'edd-retroactive-licensing' ), $install );
		} else {
			$activate = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . self::EDDSL_PLUGIN_FILE ), 'activate-plugin_' . self::EDDSL_PLUGIN_FILE ) );
			$link     = sprintf( __( '<a href="%1$s">activate</a>', 'edd-retroactive-licensing' ), $activate );
		}

		$content  = '<div class="error"><p>';
		$content .= sprintf( __( 'Plugin %3$s has been deactivated. Please %1$s Easy Digital Sales - Software Licenses version %2$s or newer before activating %3$s.', 'edd-retroactive-licensing' ), $link, self::REQUIRED_EDDSL_VERSION, 'EDD Retroactive Licensing' );
		$content .= '</p></div>';

		echo $content;
	}


	public static function set_base() {
		self::$base = plugin_basename( __FILE__ );
	}


	public static function set_notice( $notice_name ) {
		self::set_notice_key();

		$notices = get_site_transient( self::$notice_key );
		if ( false === $notices )
			$notices = array();

		$notices[] = $notice_name;

		self::clear_notices();
		set_site_transient( self::$notice_key, $notices, HOUR_IN_SECONDS );
	}


	public static function clear_notices() {
		self::set_notice_key();

		delete_site_transient( self::$notice_key );
	}


	public static function check_notices() {
		self::set_notice_key();

		$notices = get_site_transient( self::$notice_key );

		if ( false === $notices )
			return;

		foreach ( $notices as $notice )
			add_action( 'admin_notices', array( 'EDD_Retroactive_Licensing', $notice ) );

		self::clear_notices();
	}


	public static function set_notice_key() {
		if ( is_null( self::$notice_key ) )
			self::$notice_key = self::SLUG . 'notices';
	}


	public static function set_post_types() {
		if ( is_null( self::$post_types ) )
			self::$post_types = array( self::EDD_PAYMENT_PT );
	}


	public static function get_order_url( $payment_id ) {
		$link_base = self::$payment_history_url . '&view=view-order-details';
		$link      = add_query_arg( 'id', $payment_id, $link_base );

		return $link;
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function handle_licensing( $payment_id, $post ) {
		$emailed = false;
		$stage   = self::check_stage( $payment_id );


		switch ( $stage ) {
		case 'initial':
			$licensed = self::generate_license_keys( $payment_id );
			if ( true !== $licensed )
				return $licensed;

			$emailed = self::send_email( $payment_id, $stage );
			break;

		case 'remind':
			$emailed = self::send_email( $payment_id, $stage );
			if ( true === $emailed )
				$emailed = 'reminded';
			break;

		default:
			break;
		}

		return $emailed;
	}


	public static function check_stage( $payment_id ) {
		$args = array(
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'    => '_edd_sl_payment_id',
					'value'   => $payment_id,
				),
			),
			'post_type'   => 'edd_license',
		);

		$licenses = get_posts( $args );

		if ( empty( $licenses ) )
			$stage = 'initial';
		else
			$stage = 'remind';

		return $stage;
	}


	public static function edd_settings_extensions( $settings ) {
		$settings[] = array(
			'id' => self::SLUG . 'header',
			'name' => '<h3 id="EDD_Retroactive_Licensing">' . esc_html__( 'Retroactive Licensing', 'edd-retroactive-licensing' ) . '</h3>',
			'type' => 'header',
		);

		$pages         = get_pages();
		$pages_options = array( 0 => '' ); // Blank option
		if ( $pages )
			foreach ( $pages as $page )
				$pages_options[ $page->ID ] = $page->post_title;

			$settings[] = array(
				'id' => self::SLUG . 'contact_link',
				'name' => esc_html__( 'Contact Page Link', 'edd-retroactive-licensing' ),
				'desc' => esc_html__( 'This is a feedback page for users to contact you.', 'edd-retroactive-licensing' ),
				'type' => 'select',
				'options' => $pages_options,
			);

		$products = self::get_licensed_products();
		if ( ! empty( $products ) )
			$settings[] = array(
				'id' => self::SLUG . 'allowed_products',
				'name' => esc_html__( 'Allowed Products', 'edd-retroactive-licensing' ),
				'desc' => esc_html__( 'These products have licensing enabled. Check the products you want retroactive licensing to work with.', 'edd-retroactive-licensing' ),
				'type' => 'multicheck',
				'options' => $products,
			);

		$settings[] = array(
			'id' => self::SLUG . 'initial_header',
			'name' => '<strong>' . esc_html__( 'License Provisioning', 'edd-retroactive-licensing' ) . '</strong>',
			'type' => 'header',
		);

		$settings[] = array(
			'id' => self::SLUG . 'initial_enable',
			'name' => esc_html__( 'Enabled?', 'edd-retroactive-licensing' ),
			'desc' => esc_html__( 'Check this to enable licensing provision.', 'edd-retroactive-licensing' ),
			'type' => 'checkbox',
		);

		$settings[] = array(
			'id' => self::SLUG . 'remind_header',
			'name' => '<strong>' . esc_html__( 'License Reminders', 'edd-retroactive-licensing' ) . '</strong>',
			'type' => 'header',
		);

		$settings[] = array(
			'id' => self::SLUG . 'remind_enable',
			'name' => esc_html__( 'Enabled?', 'edd-retroactive-licensing' ),
			'desc' => esc_html__( 'Check this to enable sending reminders to activate licenses.', 'edd-retroactive-licensing' ),
			'type' => 'checkbox',
		);

		return $settings;
	}


	public static function edd_settings_emails( $settings ) {
		$settings[] = array(
			'id' => self::SLUG . 'header',
			'name' => '<h3 id="EDD_Retroactive_Licensing">' . esc_html__( 'Retroactive Licensing', 'edd-retroactive-licensing' ) . '</h3>',
			'type' => 'header',
		);

		$settings[] = array(
			'id' => self::SLUG . 'disable_admin_notices',
			'name' => esc_html__( 'Disable Licensing Notifications', 'edd-retroactive-licensing' ),
			'desc' => esc_html__( 'Check this box if you do not want to receive emails when sales recovery attempts are made.', 'edd-retroactive-licensing' ),
			'type' => 'checkbox',
		);

		$settings[] = array(
			'id' => self::SLUG . 'initial_header',
			'name' => '<strong>' . esc_html__( 'License Provisioning', 'edd-retroactive-licensing' ) . '</strong>',
			'type' => 'header',
		);

		$settings[] = array(
			'id' => self::SLUG . 'initial_subject',
			'name' => esc_html__( 'Licensing Subject', 'edd-retroactive-licensing' ),
			'type' => 'text',
			'std' => esc_html__( '{sitename}: Product Licensing', 'edd-retroactive-licensing' ),
		);

		$settings[] = array(
			'id' => self::SLUG . 'initial_email',
			'name' => esc_html__( 'Licensing Content', 'edd-retroactive-licensing' ),
			'desc' => self::template_tags(),
			'type' => 'rich_editor',
			'std' => self::email_body_template(),
		);

		$settings[] = array(
			'id' => self::SLUG . 'initial_admin_subject',
			'name' => esc_html__( 'Licensing Notification Subject', 'edd-retroactive-licensing' ),
			'type' => 'text',
			'std' => esc_html__( '{sitename}: Product Licensing', 'edd-retroactive-licensing' ),
		);

		$settings[] = array(
			'id' => self::SLUG . 'remind_header',
			'name' => '<strong>' . esc_html__( 'License Reminders', 'edd-retroactive-licensing' ) . '</strong>',
			'type' => 'header',
		);

		$settings[] = array(
			'id' => self::SLUG . 'remind_subject',
			'name' => esc_html__( 'Reminder Subject', 'edd-retroactive-licensing' ),
			'type' => 'text',
			'std' => esc_html__( '{sitename}: Activate Your License', 'edd-retroactive-licensing' ),
		);

		$settings[] = array(
			'id' => self::SLUG . 'remind_email',
			'name' => esc_html__( 'Reminder Content', 'edd-retroactive-licensing' ),
			'desc' => self::template_tags(),
			'type' => 'rich_editor',
			'std' => self::email_body_template( 'reminder' ),
		);

		$settings[] = array(
			'id' => self::SLUG . 'remind_admin_subject',
			'name' => esc_html__( 'Reminder Notification Subject', 'edd-retroactive-licensing' ),
			'type' => 'text',
			'std' => esc_html__( '{sitename}: Activate Your License', 'edd-retroactive-licensing' ),
		);

		return $settings;
	}


	public static function template_tags() {
		$tags   = array();
		$tags[] = esc_html__( 'Enter the email contents that is sent for retroactive licensing. HTML is accepted. Additional EDD template tags:', 'edd-retroactive-licensing' );
		$tags[] = '{admin_order_details_url} - ' . esc_html__( 'Admin order details URL', 'edd-retroactive-licensing' );
		$tags[] = '{admin_order_details} - ' . esc_html__( 'Admin order details tag - Automatically prepended to admin notifications', 'edd-retroactive-licensing' );
		$tags[] = '{contact_url} - ' . esc_html__( 'Contact page URL', 'edd-retroactive-licensing' );
		$tags[] = '{contact} - ' . esc_html__( 'Contact page tag', 'edd-retroactive-licensing' );
		$tags[] = '{site_url} - ' . esc_html__( 'Site URL', 'edd-retroactive-licensing' );
		$tags[] = '{users_orders_url} - ' . esc_html__( 'User\'s orders URL', 'edd-retroactive-licensing' );
		$tags[] = '{users_orders} - ' . esc_html__( 'User\'s orders tag - Automatically prepended to admin notifications', 'edd-retroactive-licensing' );

		$tags = implode( '<br />', $tags );

		return apply_filters( 'eddrl_template_tags', $tags );
	}


	public static function email_body_template( $mode = false ) {
		switch ( $mode ) {
		case 'reminder' :
			$template = __(
				'Hello {name},

We\'re sending you a reminder to activate your software license for a {date} purchased item from {sitename} that requires licensing for automatic upgrades.

',
				'edd-retroactive-licensing'
			);
			break;

		default:
			$template = __(
				'Hello {name},

We\'re sending you a software license for a {date} purchased item from {sitename} that now requires licensing for automatic upgrades.

',
				'edd-retroactive-licensing'
			);
			break;
		}

		$template .= __(
			'We apologize for the inconvenience of having to set the license, but this few minute task will continue to ensure that your purchased software is the latest release with bug fixes and new enhancements.

In general, to set the license, copy and paste your product\'s license from below into the appropriate license key field at WP Admin > Downloads > Settings, License tab.

<strong>Licenses</strong>

{license_keys}

<strong>Item Links</strong>

{file_urls}

',
			'edd-retroactive-licensing'
		);

		$template .= __(
			'If you have any questions, please visit {contact} to send them.
<hr />
<a href="{site_url}">{sitename}</a> appreciates your business.',
			'edd-retroactive-licensing'
		);

		return $template;
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.LongVariable)
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function edd_email_template_tags( $message, $payment_data, $payment_id, $admin_notice ) {
		$admin_order_details_url = self::get_order_url( $payment_id );
		$admin_order_details     = self::get_order_link( $payment_id );

		$contact_link = self::get_edd_options( 'contact_link' );
		$links        = self::create_link( $contact_link );
		if ( $links ) {
			$contact     = $links['tag'];
			$contact_url = $links['link'];
		} else {
			$contact     = '';
			$contact_url = '';
		}

		$payment_meta      = edd_get_payment_meta( $payment_id );
		$email             = $payment_meta['email'];
		$users_orders_text = __( 'View <a href="%1$s">user\'s orders</a>.', 'edd-retroactive-licensing' );
		$users_orders_url  = add_query_arg( 'user', $email, self::$payment_history_url );
		$users_orders      = sprintf( $users_orders_text, $users_orders_url );

		$message = str_replace( '{admin_order_details_url}', $admin_order_details_url, $message );
		$message = str_replace( '{admin_order_details}', $admin_order_details, $message );
		$message = str_replace( '{contact_url}', $contact_url, $message );
		$message = str_replace( '{contact}', $contact, $message );
		$message = str_replace( '{site_url}', site_url(), $message );
		$message = str_replace( '{users_orders_url}', $users_orders_url, $message );
		$message = str_replace( '{users_orders}', $users_orders, $message );

		return $message;
	}


	public static function get_email_from() {
		return self::get_edd_options( 'from_email', get_option( 'admin_email' ) );
	}


	public static function get_email_headers( $payment_id, $payment_data ) {
		$from_name  = self::get_edd_options( 'from_name', get_bloginfo( 'name' ) );
		$from_email = self::get_email_from();

		$headers  = 'From: ' . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
		$headers .= 'Reply-To: '. $from_email . "\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		$headers  = apply_filters( 'eddrl_get_email_headers', $headers, $payment_id, $payment_data );

		return $headers;
	}


	public static function get_full_name( $payment_id ) {
		$user_id   = edd_get_payment_user_id( $payment_id );
		$user_info = edd_get_payment_meta_user_info( $payment_id );

		if ( ! empty( $user_id ) && $user_id > 0 ) {
			$user_data = get_userdata( $user_id );
			$name      = $user_data->display_name;
		} elseif ( ! empty( $user_info['first_name'] ) && ! empty( $user_info['last_name'] ) ) {
			$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
		} elseif ( ! empty( $user_info['first_name'] ) ) {
			$name = $user_info['first_name'];
		}

		return $name;
	}


	public static function get_email_to( $payment_id ) {
		$email = edd_get_payment_user_email( $payment_id );
		$name  = self::get_full_name( $payment_id );

		if ( $name )
			$to = $name . ' <' . $email . '>';
		else
			$to = $email;

		$to = apply_filters( 'eddrl_get_email_to', $to, $payment_id );

		return $to;
	}


	public static function get_email_body( $email_text, $payment_data, $payment_id, $admin_notice = false ) {
		if ( $admin_notice )
			$email_text = '{admin_order_details} {users_orders}<hr />' . $email_text;

		$email_body = edd_email_template_tags( $email_text, $payment_data, $payment_id, $admin_notice );
		$email_body = apply_filters( 'eddrl_get_email_body', $email_body, $email_text, $payment_data, $payment_id );
		$email_body = apply_filters( 'edd_purchase_receipt', $email_body, $payment_id, $payment_data );

		$content  = edd_get_email_body_header();
		$content .= $email_body;
		$content .= edd_get_email_body_footer();

		return $content;
	}


	public static function send_email( $payment_id, $stage ) {
		$admin_notice  = ! self::get_edd_options( $stage . '_disable_admin_notices' );
		$admin_subject = self::get_edd_options( $stage . '_admin_subject' );
		$email_text    = self::get_edd_options( $stage . '_email' );
		$payment_data  = edd_get_payment_meta( $payment_id );
		$subject       = self::get_edd_options( $stage . '_subject' );

		$headers = self::get_email_headers( $payment_id, $payment_data );
		$to      = self::get_email_to( $payment_id );

		$email_subject = edd_email_template_tags( $subject, $payment_data, $payment_id );
		$email_body    = self::get_email_body( $email_text, $payment_data, $payment_id );
		$attachments   = apply_filters( 'eddrl_process_attachments', array(), $payment_id, $payment_data );

		$success = wp_mail( $to, $email_subject, $email_body, $headers, $attachments );
		if ( $success ) {
			$text = esc_html__( 'Retroactive licensing %2$s email sent: "%1$s"', 'edd-retroactive-licensing' );
			edd_insert_payment_note( $payment_id, sprintf( $text, $email_subject, $stage ) );

			if ( $admin_notice ) {
				$to            = edd_get_admin_notice_emails();
				$admin_subject = edd_email_template_tags( $admin_subject, $payment_data, $payment_id, true );
				$email_body    = self::get_email_body( $email_text, $payment_data, $payment_id, true );
				wp_mail( $to, $admin_subject, $email_body, $headers );
			}

			do_action( 'eddrl_post_licensing', $payment_id, $stage );
		} else
			return esc_html__( '`wp_mail` failed to send', 'edd-retroactive-licensing' );

		return true;
	}


	/**
	 * If incoming link is empty, then get_site_url() is used instead.
	 */
	public static function create_link( $link ) {
		if ( empty( $link ) )
			$link = get_site_url();

		if ( preg_match( '#^\d+$#', $link ) ) {
			$permalink = get_permalink( $link );
			$title     = get_the_title( $link );

			$tag  = '<a href="';
			$tag .= $permalink;
			$tag .= '" title="';
			$tag .= $title;
			$tag .= '">';
			$tag .= $title;
			$tag .= '</a>';
		} else {
			$orig_link = $link;
			$do_http   = true;

			if ( 0 === strpos( $link, '/' ) )
				$do_http = false;

			if ( $do_http && 0 === preg_match( '#https?://#', $link ) )
				$link = 'http://' . $link;

			$permalink = $link;

			$tag  = '<a href="';
			$tag .= $permalink;
			$tag .= '">';
			$tag .= $orig_link;
			$tag .= '</a>';
		}

		return array(
			'link' => $permalink,
			'tag' => $tag,
		);
	}


	public static function get_order_link( $payment_id = null ) {
		$order_link = __( 'View <a href="%1$s">order details</a>.', 'edd-sales-recovery', 'edd-retroactive-licensing' );
		$order_url  = self::get_order_url( $payment_id );
		$order_link = sprintf( $order_link, $order_url );

		return $order_link;
	}


	public static function get_licensed_products() {
		global $wpdb;

		$product_query = array(
			'post_status' => 'publish',
			'post_type' => self::EDD_PT,
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_edd_sl_enabled',
					'value' => 1,
					'compare' => '=',
				),
			),
		);

		$results  = new WP_Query( $product_query );
		$query_wp = $results->request;
		$query_wp = preg_replace( '#\bLIMIT 0,.*#', '', $query_wp );
		$results  = $wpdb->get_col( $query_wp );

		$products = array();
		foreach ( $results as $result )
			$products[ $result ] = get_the_title( $result );

		$products = apply_filters( 'eddrl_products', $products );

		return $products;
	}


}


if ( ! class_exists( 'EDD_License' ) )
	include_once dirname( __FILE__ ) . '/lib/EDD_License_Handler.php';

$license = new EDD_License( __FILE__, 'Retroactive Licensing', EDD_Retroactive_Licensing::VERSION, 'Michael Cannon' );


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
	if ( EDD_Retroactive_Licensing::version_check() ) {
		global $EDD_Retroactive_Licensing;
		if ( is_null( $EDD_Retroactive_Licensing ) )
			$EDD_Retroactive_Licensing = new EDD_Retroactive_Licensing();
	}
}


?>
