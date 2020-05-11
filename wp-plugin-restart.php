<?php
	/**
	 * Plugin Name:       Better Plugin Restart
	 * Plugin URI:        https://pluginsbay.com/
	 * Description:       Reset (deactivate + activate) plugins and remove (deactivate + delete) with a single click
	 * Version:           1.0.0
	 * Author:            PluginsBay
	 * Author URI:        https://www.wporb.com
	 * License:           GPL-2.0+
	 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	 */

	// If this file is called directly, abort.
	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	/**
	 * Current plugin version.
	 * Start at version 1.0.0 and use SemVer - https://semver.org
	 */
	defined( 'WPPLUGINRESET_PLUGIN_NAME' ) or define( 'WPPLUGINRESET_PLUGIN_NAME', 'wppluginreset' );
	defined( 'WPPLUGINRESET_PLUGIN_VERSION' ) or define( 'WPPLUGINRESET_PLUGIN_VERSION', '1.0.0' );
	defined( 'WPPLUGINRESET_BASE_NAME' ) or define( 'WPPLUGINRESET_BASE_NAME', plugin_basename( __FILE__ ) );
	defined( 'WPPLUGINRESET_ROOT_PATH' ) or define( 'WPPLUGINRESET_ROOT_PATH', plugin_dir_path( __FILE__ ) );
	defined( 'WPPLUGINRESET_ROOT_URL' ) or define( 'WPPLUGINRESET_ROOT_URL', plugin_dir_url( __FILE__ ) );

	if ( ! class_exists( 'WPPluginRestart' ) ) {
		/**
		 * Restart plugin class
		 */
		class WPPluginRestart {

			public function __construct() {

				load_plugin_textdomain( 'wppluginreset', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

				add_filter( 'plugin_action_links', array( $this, 'on_plugin_action_links' ), 10, 2 );
				add_action( 'admin_init', array( $this, 'admin_init_restart' ) );
			}

			/**
			 * plugin_action_links action callback
			 */
			public function on_plugin_action_links( $links, $plugin = '' ) {
				if ( $plugin != '' ) {
					try {
						// coerce links to array
						if ( ! is_array( $links ) ) {
							$links = $links && is_string( $links ) ? (array) $links : array();
						}


						if ( is_plugin_active( $plugin ) ) {

							//for active plugins
							if ( current_user_can( 'deactivate_plugins' ) && current_user_can( 'activate_plugins' ) ) {
								//deactivate-activate
								$link_da       = sprintf( admin_url( 'index.php?wppluginreset=%s&wppluginreset_mode=%s' ), esc_attr( $plugin ), 'da' );
								$link_da_nonce = wp_nonce_url( $link_da, 'wppluginreset', 'wppluginreset_nonce' );
								$links[]       = '<a title="' . esc_html__( 'Deactivate + Activate', 'wppluginreset' ) . '" href="' . esc_url( $link_da_nonce ) . '">' . esc_html__( 'Restart', 'wppluginreset' ) . '</a>';
							}


							if ( current_user_can( 'deactivate_plugins' ) && current_user_can( 'delete_plugins' ) ) {
								//deactivate-delete
								$link_dd       = sprintf( admin_url( 'index.php?wppluginreset=%s&wppluginreset_mode=%s' ), esc_attr( $plugin ), 'dd' );
								$link_dd_nonce = wp_nonce_url( $link_dd, 'wppluginreset', 'wppluginreset_nonce' );
								$links[]       = '<a onClick="return confirm(\''.esc_html__('Are you sure you want to deactivate and then delete this plugin?', 'wppluginreset').'\')" title="' . esc_html__( 'Deactivate + Delete', 'wppluginreset' ) . '" href="' . esc_url( $link_dd_nonce ) . '">' . esc_html__( 'Delete', 'wppluginreset' ) . '</a>';
							}
						} else {
							//for inactive plugins
							if ( current_user_can( 'activate_plugins' ) && current_user_can( 'deactivate_plugins' ) ) {
								//ad
								$link_ad       = sprintf( admin_url( 'index.php?wppluginreset=%s&wppluginreset_mode=%s' ), esc_attr( $plugin ), 'ad' );
								$link_ad_nonce = wp_nonce_url( $link_ad, 'wppluginreset', 'wppluginreset_nonce' );
								$links[]       = '<a title="' . esc_html__( 'Activate + Deactivate', 'wppluginreset' ) . '" href="' . esc_url( $link_ad_nonce ) . '">' . esc_html__( 'ReSleep', 'wppluginreset' ) . '</a>';
							}

						}

					}
					catch ( Exception $e ) {
						// $links[] = esc_html( 'Debug: '.$e->getMessage() );
					}
				}

				return $links;
			}//end method on_plugin_action_links

			/**
			 * Restart plugin
			 */
			public static function admin_init_restart() {
				if ( isset( $_REQUEST['wppluginreset'] ) && sanitize_text_field( $_REQUEST['wppluginreset'] ) != '' ) {
					$plugin             = esc_attr( sanitize_text_field( $_REQUEST['wppluginreset'] ) );
					$wppluginreset_mode = esc_attr( sanitize_text_field( $_REQUEST['wppluginreset_mode'] ) );

					check_admin_referer( 'wppluginreset', 'wppluginreset_nonce' );

					if($plugin != '' && $wppluginreset_mode != ''){
						switch ($wppluginreset_mode){
							case 'da':

								if ( current_user_can( 'deactivate_plugins' ) && current_user_can( 'activate_plugins' ) && is_plugin_active( $plugin ) ) {
									deactivate_plugins( $plugin );

									if ( ! is_plugin_active( $plugin ) ) {
										activate_plugins( $plugin );
									}
								}

								break;

							case 'dd':

								if ( current_user_can( 'deactivate_plugins' ) && current_user_can( 'delete_plugins' ) && is_plugin_active( $plugin ) ) {
									deactivate_plugins( $plugin );

									if ( ! is_plugin_active( $plugin ) ) {
										delete_plugins( array($plugin) );
									}
								}

								break;

							case 'ad':

								if ( current_user_can( 'activate_plugins' ) && current_user_can( 'deactivate_plugins' ) && !is_plugin_active( $plugin ) ) {
									activate_plugins( $plugin );

									if ( is_plugin_active( $plugin ) ) {
										deactivate_plugins( $plugin );
									}
								}

								break;
						}
					}

					wp_safe_redirect( admin_url( 'plugins.php?plugin_status=all' ) );
					exit();
				}
			}//end method admin_init_restart

		}//end class WPPluginRestart
	}


	/**
	 * Init the plugin
	 */
	function wppluginreset_load_plugin() {
		if ( class_exists( 'WPPluginRestart' ) ) {
			new WPPluginRestart();
		}
	}

	add_action( 'plugins_loaded', 'wppluginreset_load_plugin', 5 );
