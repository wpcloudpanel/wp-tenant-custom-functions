<?php
/*
Plugin Name: WP Tenant Custom Functions
Plugin URI: https://opensaas.io
Description: (Final Tutorial Files) A WordPress plugin that handles support functions needed for templates deployed by multi-tenant service providers such as opensaas.io & WPCloudDeploy.
Version: 1.0.0
Item Id:
Author: WPCloudDeploy & OpenSaaS.io
Author URI: https://opensaas.io
*/
require_once ABSPATH . 'wp-admin/includes/plugin.php';

class WP_Tenant_Custom_Functions_Init {

	/**
	 * Constructor function.
	 */
	public function __construct() {

		$plugin_data = get_plugin_data( __FILE__ );

		if ( ! defined( 'WPMT_CF_URL' ) ) {
			define( 'WPMT_CF_URL', plugin_dir_url( __FILE__ ) );
			define( 'WPMT_CF_PATH', plugin_dir_path( __FILE__ ) );
			define( 'WPMT_CF_PLUGIN', plugin_basename( __FILE__ ) );
			define( 'WPMT_CF_EXTENSION', $plugin_data['Name'] );
			define( 'WPMT_CF_VERSION', $plugin_data['Version'] );
			define( 'WPMT_CF_TEXTDOMAIN', 'wpcd' );
			define( 'WPMT_CF_REQUIRES', '5.2.3' );
		}

		/* Run things after WordPress is loaded */
		add_action( 'init', array( $this, 'required_files' ), -20 );

		/* Replace some text */
		add_filter( 'gettext', array( $this, 'translate_words_array' ) );
		add_filter( 'ngettext', array( $this, 'translate_words_array' ) );

		/* Enqueue CSS styles */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_styles' ) );  // For teant site PUBLIC pages.
		// add_action( 'login_enqueue_scripts', array( $this, 'login_admin_styles' ) );

		/* Remove metaboxes from the admin dashboard */
		add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widgets' ) );

		/* Add a couple of our own custom widgets to the admin dashboard */
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );

	}

	/**
	 * Include additional files as needed
	 */
	public function required_files() {
		// No files needed right now.
	}

	/**
	 * Translate words.
	 *
	 * @param array $translated Array of words.
	 *
	 * @return array.
	 */
	public function translate_words_array( $translated ) {

			$words = array(
				// 'word to translate' = > 'translation'
				'Enable automatic backups to run once per day for this site. You should set up your S3 credentials in SETTINGS or on the server page and create a bucket for these backups before turning this option on!' => 'Enable automatic backups to run once per day for this site. You should set up your S3 credentials on the server page and create a bucket for these backups before turning this option on!',
				'Kadence' => 'Acme',
			);

			$translated = str_ireplace( array_keys( $words ), $words, $translated );

			return $translated;
	}

	/**
	 * Load up CSS scripts into admin area.
	 *
	 * @param string $hook The current admin page. @see https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/.
	 */
	public function enqueue_admin_styles( $hook ) {

		/* Some CSS always gets loaded */
		$version = 99;
		if ( defined( 'WPCD_SCRIPTS_VERSION' ) ) {
			$version = WPCD_SCRIPTS_VERSION;
		}
		wp_enqueue_style( 'wp_tenant_custom_functions_admin_styles', plugins_url( 'wp_tenant_custom_functions_admin_styles.css', __FILE__ ), array(), $version );

	}

	/**
	 * Load up CSS scripts into he WPCD PUBLIC pages.
	 */
	public function enqueue_public_styles() {

		$version = 99;
		if ( defined( 'WPCD_SCRIPTS_VERSION' ) ) {
			$version = WPCD_SCRIPTS_VERSION;
		}
		if ( WPCD_WORDPRESS_APP_PUBLIC::is_public_page() ) {
			wp_enqueue_style( 'wp_tenant_custom_functions_public_styles', plugins_url( 'wp_tenant_custom_functions_public_styles.css', __FILE__ ), array(), $version );
		}

	}

	/**
	 * Remove some default dashboard widgets.
	 *
	 * Action Hook: wp_dashboard_setup
	 */
	public function remove_dashboard_widgets() {
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' ); // Quick Drafts (used to be known as Quick Press).
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' ); // WordPress Events and News.
		remove_meta_box( 'wc_admin_dashboard_setup', 'dashboard', 'normal' ); // WooCommerce setup.
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' ); // At a glance.

		if ( is_user_logged_in() ) { // check if there is a logged in user.
			$user  = wp_get_current_user(); // getting & setting the current user.
			$roles = (array) $user->roles; // obtaining the role - force to array because a user can technically have more than one role (standard wp only assigns a single role though).

			if ( in_array( 'opensaas_customer_admin', $roles, true ) ) {
				remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' ); // Site Health.
				remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' ); // Site Health.
			}
		}

	}

	/**
	 * Add our custom dashboard widgets.
	 *
	 * Action Hook: wp_dashboard_setup
	 */
	public function add_dashboard_widgets() {
		wp_add_dashboard_widget( 'wpmt_dashboard_welcome', 'Welcome', array( $this, 'wpmt_add_welcome_wpadmin_widget' ), null, null, 'normal', 'high' );
		wp_add_dashboard_widget( 'wpmt_checklist', 'Acme Setup Checklist', array( $this, 'wpmt_add_checklist_wpadmin_widget' ), null, null, 'column3', 'high' );
	}

	/**
	 * Add contents for welcome widget in the wp-admin dashboard.
	 */
	public function wpmt_add_welcome_wpadmin_widget() {

		?>

		Welcome to your WordPress Site.
		<br />
		<br />
		You can learn about how to get started from our <a href="#">Getting started document.</a>
		<br />

		<br />		
		<hr />
		<strong>Support & Help</strong>
		<hr />
		<ul>
			<li><a href="$">Open a support ticket.</a></li>
			<li><a href="mailto:support@mailanator.com">Send an email to support.</a></li>
		</ul>
		<?php

	}

	/**
	 * Add contents for checklist widget in the wp-admin dashboard.
	 */
	public function wpmt_add_checklist_wpadmin_widget() {

		?>

		Here are some tasks you might need to complete to get your site up and running.

		<strong>General WP Setup</strong>
		<hr />
		<ul>
			<li><strong>Timezone</strong> - Set your installation timezone.</li>
			<li><strong>Tagline</strong> - Set your site's tagline.</li>
			<li><strong>Admin Email</strong> - Set the site general admin email.</li>
			<li><strong>Terms of Service</strong> - Setup the site's terms of service.</li>
			<li><strong>Refund Policy</strong> - Setup refund and return policies.</li>
			<li><strong>Privacy</strong> - Setup privacy policy.</li>
		</ul>

		<br />
		<hr />
		<strong>Store & WooCommerce</strong>
		<hr />
		<ul>
			<li><strong>Store</strong> - Setup general WooCommerce data.</li>
			<li><strong>Payments</strong> - Connect Your WooCommerce Store To Stripe or Paypal.</li>
			<li><strong>Products</strong> - Configure products (start with the existing samples)..</li>
		</ul>

		<?php
	}

}

/**
 * Bootstrap
 */
$esm_throwaaway = new WP_Tenant_Custom_Functions_Init();
