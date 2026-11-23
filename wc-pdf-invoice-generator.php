<?php
/**
 * Plugin Name:       PDF Invoice Generator for WooCommerce
 * Plugin URI:        https://example.com/wc-pdf-invoice-generator
 * Description:       Automatically generate beautiful, professional PDF invoices for every WooCommerce order. Customers download from My Account, admins from Orders, and invoices attach to order emails.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wc-pdf-invoice-generator
 * Domain Path:       /languages
 * WC requires at least: 5.0
 * WC tested up to:   8.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Plugin constants.
 */
define( 'WCPIG_VERSION', '1.0.0' );
define( 'WCPIG_PLUGIN_FILE', __FILE__ );
define( 'WCPIG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCPIG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCPIG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active.
 */
function wcpig_check_woocommerce() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'wcpig_woocommerce_missing_notice' );
        return false;
    }
    return true;
}

/**
 * Admin notice when WooCommerce is not active.
 */
function wcpig_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p>
            <?php
            printf(
                /* translators: %s: WooCommerce plugin name */
                esc_html__( '%1$sPDF Invoice Generator for WooCommerce%2$s requires %1$sWooCommerce%2$s to be installed and active.', 'wc-pdf-invoice-generator' ),
                '<strong>',
                '</strong>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Initialize the plugin.
 */
function wcpig_init() {
    if ( ! wcpig_check_woocommerce() ) {
        return;
    }

    // Load text domain.
    load_plugin_textdomain( 'wc-pdf-invoice-generator', false, dirname( WCPIG_PLUGIN_BASENAME ) . '/languages' );

    // Include required files.
    require_once WCPIG_PLUGIN_DIR . 'includes/class-wcpig-settings.php';
    require_once WCPIG_PLUGIN_DIR . 'includes/class-wcpig-invoice.php';
    require_once WCPIG_PLUGIN_DIR . 'includes/class-wcpig-pdf-renderer.php';
    require_once WCPIG_PLUGIN_DIR . 'includes/class-wcpig-hooks.php';

    // Initialize classes.
    WCPIG_Settings::get_instance();
    WCPIG_Hooks::get_instance();
}
add_action( 'plugins_loaded', 'wcpig_init' );

/**
 * Plugin activation.
 */
function wcpig_activate() {
    // Set default options.
    $defaults = array(
        'wcpig_company_name'       => get_bloginfo( 'name' ),
        'wcpig_company_tagline'    => '',
        'wcpig_company_address'    => '',
        'wcpig_company_phone'      => '',
        'wcpig_company_email'      => get_option( 'admin_email' ),
        'wcpig_tax_registration'   => '',
        'wcpig_logo_url'           => '',
        'wcpig_invoice_prefix'     => 'INV-',
        'wcpig_starting_number'    => 1001,
        'wcpig_footer_note'        => __( 'Thank you for your business! This is a computer-generated invoice.', 'wc-pdf-invoice-generator' ),
        'wcpig_date_format'        => 'Y-m-d',
        'wcpig_email_attach'       => array( 'customer_completed_order', 'customer_processing_order' ),
        'wcpig_allow_customer'     => 'yes',
        'wcpig_template_style'     => 'classic',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            update_option( $key, $value );
        }
    }

    // Set the next invoice number if not already set.
    if ( false === get_option( 'wcpig_next_invoice_number' ) ) {
        update_option( 'wcpig_next_invoice_number', (int) get_option( 'wcpig_starting_number', 1001 ) );
    }

    // Flush rewrite rules for custom endpoint.
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wcpig_activate' );

/**
 * Plugin deactivation.
 */
function wcpig_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wcpig_deactivate' );

/**
 * Add settings link on plugins page.
 *
 * @param array $links Plugin action links.
 * @return array Modified plugin action links.
 */
function wcpig_plugin_action_links( $links ) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url( admin_url( 'admin.php?page=wc-settings&tab=wcpig_invoices' ) ),
        esc_html__( 'Settings', 'wc-pdf-invoice-generator' )
    );
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . WCPIG_PLUGIN_BASENAME, 'wcpig_plugin_action_links' );

/**
 * Declare HPOS compatibility.
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
