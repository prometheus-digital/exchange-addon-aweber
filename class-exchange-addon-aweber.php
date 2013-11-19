<?php
/**
 * iThemes Exchange - AWeber Add-on class.
 *
 * @package   TGM_Exchange_Aweber
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @copyright 2013 Griffin Media, LLC. All rights reserved.
 */

/**
 * Main plugin class.
 *
 * @package TGM_Exchange_Aweber
 */
class TGM_Exchange_Aweber {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The name of the plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_name = 'iThemes Exchange - AWeber Add-on';

    /**
     * Unique plugin identifier.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_slug = 'exchange-addon-aweber';

    /**
     * Plugin textdomain.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $domain = 'LION';

    /**
     * Plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance = null;

    /**
     * Holds any error messages.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $errors = array();

    /**
     * Flag to determine if form was saved.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $saved = false;

    /**
     * Flag to determine if auth code was reset.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $reset = false;

    /**
     * Initialize the plugin class object.
     *
     * @since 1.0.0
     */
    private function __construct() {

        // Load plugin text domain.
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

        // Load the plugin.
        add_action( 'init', array( $this, 'init' ) );

        // Load ajax hooks.
        add_action( 'wp_ajax_tgm_exchange_aweber_update_lists', array( $this, 'lists' ) );

    }

    /**
     * Return an instance of this class.
     *
     * @since 1.0.0
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance )
            self::$instance = new self;

        return self::$instance;

    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {

        $domain = $this->domain;
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

    }

    /**
     * Loads the plugin.
     *
     * @since 1.0.0
     */
    public function init() {

        // Load admin assets.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Utility actions.
        add_filter( 'plugin_action_links_' . plugin_basename( TGM_EXCHANGE_AWEBER_FILE ), array( $this, 'plugin_links' ) );
        add_filter( 'it_exchange_theme_api_registration_password2', array( $this, 'output_optin' ) );
        add_action( 'it_exchange_content_checkout_logged_in_checkout_requirement_guest_checkout_end_form', array( $this, 'output_optin_guest' ) );
        add_action( 'it_exchange_register_user', array( $this, 'do_optin' ) );
        add_action( 'it_exchange_init_guest_checkout', array( $this, 'do_optin_guest' ) );

    }

    /**
     * Outputs update nag if the currently installed version does not meet the addon requirements.
     *
     * @since 1.0.0
     */
    public function nag() {

        ?>
        <div id="tgm-exchange-aweber-nag" class="it-exchange-nag">
            <?php
            printf( __( 'To use the AWeber add-on for iThemes Exchange, you must be using iThemes Exchange version 1.0.3 or higher. <a href="%s">Please update now</a>.', 'LION' ), admin_url( 'update-core.php' ) );
            ?>
        </div>
        <?php

    }

    /**
     * Register and enqueue admin-specific stylesheets.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on our addon page in the admin.
     */
    public function enqueue_admin_styles() {

        if ( ! $this->is_settings_page() ) return;

        wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'lib/css/admin.css', __FILE__ ), array(), $this->version );

    }

    /**
     * Register and enqueue admin-specific JS.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on our addon page in the admin.
     */
    public function enqueue_admin_scripts() {

        if ( ! $this->is_settings_page() ) return;

        wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'lib/js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since 1.0.0
     */
    public function settings() {

        // Save form settings if necessary.
        if ( isset( $_POST['tgm-exchange-aweber-form'] ) && $_POST['tgm-exchange-aweber-form'] )
            $this->save_form();

        ?>
        <div class="wrap tgm-exchange-aweber">
            <?php screen_icon( 'it-exchange' ); ?>
            <h2><?php _e( 'AWeber Settings', 'LION' ); ?></h2>

            <?php if ( ! empty( $this->errors ) ) : ?>
                <div id="message" class="error"><p><strong><?php echo implode( '<br>', $this->errors ); ?></strong></p></div>
            <?php endif; ?>

            <?php if ( $this->saved ) : ?>
                <div id="message" class="updated"><p><strong><?php _e( 'Your settings have been saved successfully!', 'LION' ); ?></strong></p></div>
            <?php endif; ?>

            <?php if ( $this->reset ) : ?>
                <div id="message" class="updated"><p><strong><?php _e( 'Your AWeber authorization code has been reset successfully!', 'LION' ); ?></strong></p></div>
            <?php endif; ?>

            <?php do_action( 'it_exchange_aweber_settings_page_top' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

            <div class="tgm-exchange-aweber-settings">
                <p><?php _e( 'To setup AWeber in Exchange, fill out the settings below.', 'LION' ); ?></p>
                <form class="tgm-exchange-aweber-form" action="admin.php?page=it-exchange-addons&add-on-settings=aweber" method="post">
                    <?php wp_nonce_field( 'tgm-exchange-aweber-form' ); ?>
                    <input type="hidden" name="tgm-exchange-aweber-form" value="1" />

                    <p><a href="#" id="tgm-exchange-aweber-auth-code" class="button button-secondary" title="<?php esc_attr_e( 'Click Here to Get Your AWeber Authorization Code', 'LION' ); ?>"><?php _e( 'Click Here to Get Your AWeber Authorization Code', 'LION' ); ?></a></p>

                    <table class="form-table">
                        <tbody>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-aweber-auth"><strong><?php _e( 'AWeber Auth Code', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-aweber-auth" type="password" name="_tgm_exchange_aweber[aweber-auth]" value="<?php echo $this->get_setting( 'aweber-auth' ); ?>" placeholder="<?php esc_attr_e( 'Enter your AWeber auth code here.', 'LION' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-aweber-lists"><strong><?php _e( 'AWeber List', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <div class="tgm-exchange-aweber-list-output">
                                        <?php echo $this->get_aweber_lists( $this->get_setting( 'aweber-auth' ) ); ?>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-aweber-label"><strong><?php _e( 'AWeber Label', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-aweber-label" type="text" name="_tgm_exchange_aweber[aweber-label]" value="<?php echo $this->get_setting( 'aweber-label' ); ?>" placeholder="<?php esc_attr_e( 'Enter your AWeber username here.', 'LION' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-aweber-checked"><strong><?php _e( 'Check AWeber box by default?', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-aweber-checked" type="checkbox" name="_tgm_exchange_aweber[aweber-checked]" value="<?php echo (bool) $this->get_setting( 'aweber-checked' ); ?>" <?php checked( $this->get_setting( 'aweber-checked' ), 1 ); ?> />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                        <input type="submit" class="button button-primary button-large" id="_tgm_exchange_aweber[save]" name="_tgm_exchange_aweber[save]" value="<?php esc_attr_e( 'Save Changes', 'LION' ); ?>" />
                        <input type="submit" class="button button-secondary button-large" id="_tgm_exchange_aweber[reset]" name="_tgm_exchange_aweber[reset]" value="<?php esc_attr_e( 'Reset AWeber Authorization Code', 'LION' ); ?>" />
                    </p>
                </form>
            </div>

            <?php do_action( 'it_exchange_aweber_settings_page_bottom' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
        </div>
        <?php

    }

    /**
     * Saves form field settings for the addon.
     *
     * @since 1.0.0
     */
    public function save_form() {

        // If the nonce is not correct, return an error.
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'tgm-exchange-aweber-form' ) ) {
            $this->errors[] = __( 'Are you sure you want to do this? The form nonces do not match. Please try again.', 'LION' );
            return;
        }

        // Sanitize values before saving them to the database.
        $settings     = get_option( 'tgm_exchange_aweber' );
        $new_settings = stripslashes_deep( $_POST['_tgm_exchange_aweber'] );

        // If the reset button was hit, reset the auth code.
        if ( isset( $new_settings['reset'] ) ) {
            $settings['aweber-auth']          = '';
            $settings['aweber-auth-key']      = '';
            $settings['aweber-auth-token']    = '';
            $settings['aweber-access-token']  = '';
            $settings['aweber-access-secret'] = '';
        } else {
            $settings['aweber-list']    = isset( $new_settings['aweber-list'] ) ? esc_attr( $new_settings['aweber-list'] ) : $settings['aweber-list'];
            $settings['aweber-label']   = isset( $new_settings['aweber-label'] ) ? esc_html( $new_settings['aweber-label'] ) : $settings['aweber-label'];
            $settings['aweber-checked'] = isset( $new_settings['aweber-checked'] ) ? 1 : 0;
        }

        // Save the settings and set flags.
        update_option( 'tgm_exchange_aweber', $settings );

        if ( isset( $new_settings['reset'] ) )
            return $this->reset = true;
        else
            return $this->saved = true;

    }

    /**
     * Ajax callback to retrieve lists for the specific account.
     *
     * @since 1.0.0
     */
    public function lists() {

        // Prepare and sanitize variables.
        $auth = stripslashes( $_POST['auth'] );

        // Retrieve the lists and die.
        die( $this->get_aweber_lists( $auth ) );

    }

    /**
     * Helper flag function to determine if on the addon settings page.
     *
     * @since 1.0.0
     *
     * @return bool True if on the addon page, false otherwise.
     */
    public function is_settings_page() {

        return isset( $_GET['add-on-settings'] ) && 'aweber' == $_GET['add-on-settings'];

    }

    /**
     * Helper function for retrieving addon settings.
     *
     * @since 1.0.0
     *
     * @param string $setting The setting to look for.
     * @return mixed Addon setting if set, empty string otherwise.
     */
    public function get_setting( $setting = '' ) {

        $settings = get_option( 'tgm_exchange_aweber' );

        if ( 'aweber-label' == $setting )
            return isset( $settings[$setting] ) ? $settings[$setting] : __( 'Sign up to receive updates via email!', 'LION' );
        else
            return isset( $settings[$setting] ) ? $settings[$setting] : '';

    }

    /**
     * Helper function to retrieve all available AWeber lists for the account.
     *
     * @since 1.0.0
     *
     * @param string $auth The AWeber auth code.
     * @return string An HTML string with lists or empty dropdown.
     */
    public function get_aweber_lists( $auth = '' ) {

        // Prepare the HTML holder variable.
        $html = '';

        // If there is no username or API key, send back an empty placeholder list.
        if ( '' === trim( $auth ) ) {
            $html .= '<select id="tgm-exchange-aweber-lists" name="_tgm_exchange_aweber[aweber-list]" disabled="disabled">';
                $html .= '<option value="none">' . __( 'No lists to select from at this time.', 'LION' ) . '</option>';
            $html .= '</select>';
            $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
        } else {
            // Load the AWeber API.
            if ( ! class_exists( 'AweberAPI' ) )
		        require_once plugin_dir_path( TGM_EXCHANGE_AWEBER_FILE ) . 'lib/aweber/aweber_api.php';

            // Check if the access key and token are already set. If so, use that, otherwise grab the necessary info.
            if ( '' === trim( $this->get_setting( 'aweber-auth' ) ) || '' === trim( $this->get_setting( 'aweber-auth-key' ) ) || '' === trim( $this->get_setting( 'aweber-auth-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-secret' ) ) ) {
                // Store data individually to prevent errors when using list().
                $data       = (array) explode( '|', $auth );
                $auth_key   = isset( $data[0] ) ? $data[0] : false;
                $auth_token = isset( $data[1] ) ? $data[1] : false;
                $req_key    = isset( $data[2] ) ? $data[2] : false;
                $req_token  = isset( $data[3] ) ? $data[3] : false;
                $oauth      = isset( $data[4] ) ? $data[4] : false;

                // Initiate the API.
				$aweber = new AWeberAPI( $auth_key, $auth_token );
				$aweber->user->requestToken = $req_key;
				$aweber->user->tokenSecret  = $req_token;
				$aweber->user->verifier     = $oauth;

				// Attempt to grab an authorization token or produce an error.
				try {
					list( $access_token, $access_token_secret ) = $aweber->getAccessToken();
				} catch ( AWeberException $e ) {
				    $html .= '<select id="tgm-exchange-aweber-lists" class="tgm-exchange-error" name="_tgm_exchange_aweber[aweber-list]" disabled="disabled">';
                        $html .= '<option value="none">' . __( 'AWeber was unable to verify your authorization token. Please try again.', 'LION' ) . '</option>';
                    $html .= '</select>';
                    $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
					return $html;
				}

				// Now try to access the account. If this fails, we need more permissions.
				try {
					$account = $aweber->getAccount();
				} catch ( AWeberException $e ) {
				    $html .= '<select id="tgm-exchange-aweber-lists" class="tgm-exchange-error" name="_tgm_exchange_aweber[aweber-list]" disabled="disabled">';
                        $html .= '<option value="none">' . __( 'AWeber was unable to grant access to your account data. Please try again.', 'LION' ) . '</option>';
                    $html .= '</select>';
                    $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
					return $html;
				}

				// If we have reached this point, we have connected to the API successfully. Save the data.
				$settings                         = get_option( 'tgm_exchange_aweber' );
				$settings['aweber-auth']          = $auth;
				$settings['aweber-auth-key']      = $auth_key;
				$settings['aweber-auth-token']    = $auth_token;
				$settings['aweber-access-token']  = $access_token;
				$settings['aweber-access-secret'] = $access_token_secret;
				update_option( 'tgm_exchange_aweber', $settings );

				// Generate and send back list data.
				$html .= '<select id="tgm-exchange-aweber-lists" name="_tgm_exchange_aweber[aweber-list]">';
                    foreach ( $account->lists as $offset => $list )
                        $html .= '<option value="' . $list->id . '"' . selected( $list->id, $this->get_setting( 'aweber-list' ), false ) . '>' . $list->name . '</option>';
                $html .= '</select>';
                $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
            } else {
                $aweber  = new AweberAPI( $this->get_setting( 'aweber-auth-key' ), $this->get_setting( 'aweber-auth-token' ) );
				$account = $aweber->getAccount( $this->get_setting( 'aweber-access-token' ), $this->get_setting( 'aweber-access-secret' ) );

				// Generate and send back list data.
				$html .= '<select id="tgm-exchange-aweber-lists" name="_tgm_exchange_aweber[aweber-list]">';
                    foreach ( $account->lists as $offset => $list )
                        $html .= '<option value="' . $list->id . '"' . selected( $list->id, $this->get_setting( 'aweber-list' ), false ) . '>' . $list->name . '</option>';
                $html .= '</select>';
                $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
            }
        }

        // Return the HTML string.
        return $html;

    }

    /**
     * Adds custom action links to the plugin page.
     *
     * @since 1.0.0
     *
     * @param array $links Default action links.
     * @return array $links Amended action links.
     */
    public function plugin_links( $links ) {

        $links['setup_addon'] = '<a href="' . get_admin_url( null, 'admin.php?page=it-exchange-addons&add-on-settings=aweber' ) . '" title="' . esc_attr__( 'Setup Add-on', 'LION' ) . '">' . __( 'Setup Add-on', 'LION' ) . '</a>';
        return $links;

    }

    /**
     * Outputs the optin checkbox on the appropriate checkout screens.
     *
     * @since 1.0.0
     *
     * @param string $res The password2 field.
     * @return string $res Password2 field with optin code appended.
     */
    public function output_optin( $res ) {

        // Return early if the appropriate settings are not filled out.
        if (  '' === trim( $this->get_setting( 'aweber-auth' ) ) || '' === trim( $this->get_setting( 'aweber-auth-key' ) ) || '' === trim( $this->get_setting( 'aweber-auth-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-secret' ) ) )
            return $res;

        // Build the HTML output of the optin.
        $output = $this->get_optin_output();

        // Append the optin output to the password2 field.
        return $res . $output;

    }

    /**
     * Outputs the optin checkbox on the appropriate guest checkout screens.
     *
     * @since 1.0.0
     */
    public function output_optin_guest() {

        // Return early if the appropriate settings are not filled out.
        if (  '' === trim( $this->get_setting( 'aweber-auth' ) ) || '' === trim( $this->get_setting( 'aweber-auth-key' ) ) || '' === trim( $this->get_setting( 'aweber-auth-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-secret' ) ) )
            return;

        // Build and echo the HTML output of the optin.
        echo $this->get_optin_output();

    }

    /**
     * Processes the optin to the email service.
     *
     * @since 1.0.0
     */
    public function do_optin() {

        // Return early if the appropriate settings are not filled out.
        if (  '' === trim( $this->get_setting( 'aweber-auth' ) ) || '' === trim( $this->get_setting( 'aweber-auth-key' ) ) || '' === trim( $this->get_setting( 'aweber-auth-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-secret' ) ) )
            return;

        // Return early if our $_POST key is not set, no email address is set or the email address is not valid.
        if ( ! isset( $_POST['tgm-exchange-aweber-signup-field'] ) || empty( $_POST['email'] ) || ! is_email( $_POST['email'] ) )
            return;

        // Load the AWeber API.
		if ( ! class_exists( 'AweberAPI' ) )
		    require_once plugin_dir_path( TGM_EXCHANGE_AWEBER_FILE ) . 'lib/aweber/aweber_api.php';

        $aweber = new AweberAPI( $this->get_setting( 'aweber-auth-key' ), $this->get_setting( 'aweber-auth-token' ) );
		try {
		    $account = $aweber->getAccount( $this->get_setting( 'aweber-access-token' ), $this->get_setting( 'aweber-access-secret' ) );
		    foreach ( $account->lists as $offset => $list ) {
			    if ( $list->id == $this->get_setting( 'aweber-list' ) ) {
				    $list   = $account->loadFromUrl( '/accounts/' . $account->id . '/lists/' . $list->id );
				    // Prepare optin variables.
                    $email          = trim( $_POST['email'] );
                    $first_name     = ! empty( $_POST['first_name'] ) ? trim( $_POST['first_name'] ) : '';
                    $last_name      = ! empty( $_POST['last_name'] )  ? trim( $_POST['last_name'] )  : '';
				    $data           = array( 'email' => $_POST['email'], 'name' => $first_name . ' ' . $last_name );
				    $data           = apply_filters( 'tgm_exchange_aweber_optin_data', $data );

				    // Process the optin.
				    $subscribers    = $list->subscribers;
				    $new_subscriber = $subscribers->create( $data );
				    break;
                }
		    }
		} catch( AWeberAPIException $e ) {}

    }

    /**
     * Processes the optin to the email service in a guest checkout.
     *
     * @since 1.0.0
     *
     * @param string $email The guest checkout email address.
     */
    public function do_optin_guest( $email ) {

        // Return early if the appropriate settings are not filled out.
        if (  '' === trim( $this->get_setting( 'aweber-auth' ) ) || '' === trim( $this->get_setting( 'aweber-auth-key' ) ) || '' === trim( $this->get_setting( 'aweber-auth-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-token' ) ) || '' === trim( $this->get_setting( 'aweber-access-secret' ) ) )
            return;

        // Load the AWeber API.
		if ( ! class_exists( 'AweberAPI' ) )
		    require_once plugin_dir_path( TGM_EXCHANGE_AWEBER_FILE ) . 'lib/aweber/aweber_api.php';

        $aweber = new AweberAPI( $this->get_setting( 'aweber-auth-key' ), $this->get_setting( 'aweber-auth-token' ) );
		try {
		    $account = $aweber->getAccount( $this->get_setting( 'aweber-access-token' ), $this->get_setting( 'aweber-access-secret' ) );
		    foreach ( $account->lists as $offset => $list ) {
			    if ( $list->id == $this->get_setting( 'aweber-list' ) ) {
				    $list   = $account->loadFromUrl( '/accounts/' . $account->id . '/lists/' . $list->id );
				    // Prepare optin variables.
				    $data = array( 'email' => $email );
				    $data = apply_filters( 'tgm_exchange_aweber_optin_data', $data );

				    // Process the optin.
				    $subscribers    = $list->subscribers;
				    $new_subscriber = $subscribers->create( $data );
				    break;
                }
		    }
		} catch( AWeberAPIException $e ) {}

    }

    /**
     * Generates and returns the optin output.
     *
     * @since 1.0.0
     *
     * @return string $output HTML string of optin output.
     */
    public function get_optin_output() {

        $output  = '<div class="tgm-exchange-aweber-signup" style="clear:both;">';
            $output .= '<label for="tgm-exchange-aweber-signup-field">';
                $output .= '<input type="checkbox" id="tgm-exchange-aweber-signup-field" name="tgm-exchange-aweber-signup-field" value="' . $this->get_setting( 'aweber-checked' ) . '"' . checked( $this->get_setting( 'aweber-checked' ), 1, false ) . ' />' . $this->get_setting( 'aweber-label' );
            $output .= '</label>';
        $output .= '</div>';
        $output  = apply_filters( 'tgm_exchange_aweber_output', $output );

        return $output;

    }

}

// Initialize the plugin.
global $tgm_exchange_aweber;
$tgm_exchange_aweber = TGM_Exchange_Aweber::get_instance();