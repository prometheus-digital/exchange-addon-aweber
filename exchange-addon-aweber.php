<?php
/**
 * iThemes Exchange - AWeber Add-on.
 *
 * @package   TGM_Exchange_Aweber
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @link      http://thomasgriffinmedia.com/
 * @copyright 2013 Griffin Media, LLC. All rights reserved.
 *
 * @wordpress-plugin
 * Plugin Name:  iThemes Exchange - AWeber Add-on
 * Plugin URI:   http://ithemes.com/exchange/aweber/
 * Description:  Integrates AWeber into the iThemes Exchange plugin.
 * Version:      1.0.1
 * Author:       Thomas Griffin
 * Author URI:   http://thomasgriffinmedia.com/
 * Text Domain:  LION
 * Contributors: griffinjt
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:  /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

// Define constants.
define( 'TGM_EXCHANGE_AWEBER_FILE', __FILE__ );

// Register the plugin updater.
add_action( 'ithemes_updater_register', 'tgm_exchange_aweber_updater' );
/**
 * Registers the iThemes updater with the addon.
 *
 * @since 1.0.0
 *
 * @param object $updater The iThemes updater object.
 */
function tgm_exchange_aweber_updater( $updater ) {

    // Return early if not in the admin.
    if ( ! is_admin() ) return;

    // Load the updater class.
    require_once dirname( __FILE__ ) . '/lib/updater/load.php';

    // Register the addon with the updater.
    $updater->register( 'exchange-addon-aweber', __FILE__ );

}

// Register the addon with the Exchange engine.
add_action( 'it_exchange_register_addons', 'tgm_exchange_aweber_register' );
/**
 * Registers the AWeber addon with the Exchange addons engine.
 *
 * @since 1.0.0
 */
function tgm_exchange_aweber_register() {

    $versions         = get_option( 'it-exchange-versions', false );
    $current_version  = empty( $versions['current'] ) ? false : $versions['current'];

    if ( $current_version && version_compare( $current_version, '1.0.3', '>' ) ) {
        $options = array(
            'name'              => __( 'Aweber', 'tgm-exchange-aweber' ),
            'description'       => __( 'Adds an AWeber optin checkbox to the user registration form.', 'tgm-exchange-aweber' ),
            'author'            => 'Thomas Griffin',
            'author_url'        => 'http://thomasgriffinmedia.com/',
            'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/aweber50px.png' ),
            'file'              => dirname( __FILE__ ) . '/class-exchange-addon-aweber.php',
            'category'          => 'email',
            'settings-callback' => 'tgm_exchange_aweber_settings'
        );
        it_exchange_register_addon( 'aweber', $options );
    } else {
        add_action( 'admin_notices', 'tgm_exchange_aweber_nag' );
    }

}

/**
 * Callback function for outputting the addon settings view.
 *
 * @since 1.0.0
 */
function tgm_exchange_aweber_settings() {

    TGM_Exchange_Aweber::get_instance()->settings();

}

/**
 * Callback function for displaying upgrade nag.
 *
 * @since 1.0.0
 */
function tgm_exchange_aweber_nag() {

    TGM_Exchange_Aweber::get_instance()->nag();

}

register_activation_hook( __FILE__, 'tgm_exchange_aweber_activate' );
/**
 * Fired when the plugin is activated.
 *
 * @since 1.0.0
 *
 * @global int $wp_version The current version of WP on this install.
 *
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 */
function tgm_exchange_aweber_activate( $network_wide ) {

    global $wp_version;

    // If not WP 3.5 or greater, bail.
    if ( version_compare( $wp_version, '3.5.1', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 'Sorry, but your version of WordPress, <strong>' . $wp_version . '</strong>, does not meet the required version of <strong>3.5.1</strong> to run this plugin properly. The plugin has been deactivated. <a href="' . admin_url() . '">Click here to return to the Dashboard</a>.' );
    }

    // If our option does not exist, add it now.
    if ( is_multisite() ) :
        global $wpdb;
        $site_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" ) );
        foreach ( (array) $site_list as $site ) :
            switch_to_blog( $site->blog_id );
            $settings = get_option( 'tgm_exchange_aweber' );
            if ( ! $settings )
                update_option( 'tgm_exchange_aweber', tgm_exchange_aweber_defaults() );
            restore_current_blog();
        endforeach;
    else :
        $settings = get_option( 'tgm_exchange_aweber' );
        if ( ! $settings )
            update_option( 'tgm_exchange_aweber', tgm_exchange_aweber_defaults() );
    endif;

}

register_uninstall_hook( __FILE__, 'tgm_exchange_aweber_uninstall' );
/**
 * Fired when the plugin is uninstalled.
 *
 * @since 1.0.0
 *
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 */
function tgm_exchange_aweber_uninstall( $network_wide ) {

    // Remove any trace of our addon.
    if ( is_multisite() ) :
        global $wpdb;
        $site_list = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->blogs ORDER BY blog_id" ) );
        foreach ( (array) $site_list as $site ) :
            switch_to_blog( $site->blog_id );
            delete_option( 'tgm_exchange_aweber' );
            restore_current_blog();
        endforeach;
    else :
        delete_option( 'tgm_exchange_aweber' );
    endif;

}

/**
 * Sets addon option defaults.
 *
 * @since 1.0.0
 *
 * @return array $defaults Default options.
 */
function tgm_exchange_aweber_defaults() {

    $defaults                         = array();
    $defaults['aweber-auth']          = '';
    $defaults['aweber-auth-key']      = '';
    $defaults['aweber-auth-token']    = '';
    $defaults['aweber-access-token']  = '';
    $defaults['aweber-access-secret'] = '';
    $defaults['aweber-list']          = '';
    $defaults['aweber-label']         = __( 'Sign up to receive updates via email!', 'tgm-exchange-aweber' );
    $defaults['aweber-checked']       = 1;

    return $defaults;

}