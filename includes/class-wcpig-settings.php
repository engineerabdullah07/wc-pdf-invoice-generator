<?php
/**
 * WooCommerce Settings Tab for PDF Invoices.
 *
 * @package WCPIG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WCPIG_Settings
 *
 * Adds a "PDF Invoices" tab to WooCommerce > Settings.
 */
class WCPIG_Settings {

    /**
     * Singleton instance.
     *
     * @var WCPIG_Settings|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return WCPIG_Settings
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
        add_action( 'woocommerce_settings_tabs_wcpig_invoices', array( $this, 'settings_tab_content' ) );
        add_action( 'woocommerce_update_options_wcpig_invoices', array( $this, 'update_settings' ) );
    }

    /**
     * Add the settings tab.
     *
     * @param array $tabs Existing WooCommerce settings tabs.
     * @return array Modified settings tabs.
     */
    public function add_settings_tab( $tabs ) {
        $tabs['wcpig_invoices'] = __( 'PDF Invoices', 'wc-pdf-invoice-generator' );
        return $tabs;
    }

    /**
     * Render the settings tab content.
     */
    public function settings_tab_content() {
        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Save the settings.
     */
    public function update_settings() {
        woocommerce_update_options( $this->get_settings() );
    }

    /**
     * Get the settings fields.
     *
     * @return array Array of WooCommerce settings fields.
     */
    public function get_settings() {
        $settings = array(
            // Section: Company Information.
            array(
                'title' => __( 'Company Information', 'wc-pdf-invoice-generator' ),
                'type'  => 'title',
                'desc'  => __( 'Enter your company details to display on invoices.', 'wc-pdf-invoice-generator' ),
                'id'    => 'wcpig_company_section',
            ),
            array(
                'title'    => __( 'Company / Agency Name', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Your business name as it should appear on invoices.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_company_name',
                'type'     => 'text',
                'default'  => get_bloginfo( 'name' ),
                'css'      => 'min-width: 400px;',
            ),
            array(
                'title'    => __( 'Company Tagline / Subtitle', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'E.g. "Govt. Approved IATA Travel Agency" – appears below company name.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_company_tagline',
                'type'     => 'text',
                'default'  => '',
                'css'      => 'min-width: 400px;',
            ),
            array(
                'title'    => __( 'Company Address', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Full address including city, state/province, and postal code.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_company_address',
                'type'     => 'textarea',
                'default'  => '',
                'css'      => 'min-width: 400px; height: 80px;',
            ),
            array(
                'title'    => __( 'Company Phone', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Business phone number.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_company_phone',
                'type'     => 'text',
                'default'  => '',
            ),
            array(
                'title'    => __( 'Company Email', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Business email address.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_company_email',
                'type'     => 'email',
                'default'  => get_option( 'admin_email' ),
                'css'      => 'min-width: 300px;',
            ),
            array(
                'title'    => __( 'Tax Registration (NTN/STRN/VAT)', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Your tax registration number. Leave blank to hide.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_tax_registration',
                'type'     => 'text',
                'default'  => '',
            ),
            array(
                'title'    => __( 'Company Logo URL', 'wc-pdf-invoice-generator' ),
                'desc'     => __( 'Enter the full URL to your company logo, or use the WordPress media uploader.', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Recommended size: 300×100 pixels. Supported formats: PNG, JPG, SVG.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_logo_url',
                'type'     => 'text',
                'default'  => '',
                'css'      => 'min-width: 400px;',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcpig_company_section',
            ),

            // Section: Invoice Settings.
            array(
                'title' => __( 'Invoice Settings', 'wc-pdf-invoice-generator' ),
                'type'  => 'title',
                'desc'  => __( 'Configure invoice numbering and formatting.', 'wc-pdf-invoice-generator' ),
                'id'    => 'wcpig_invoice_section',
            ),
            array(
                'title'    => __( 'Invoice Number Prefix', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Prefix for invoice numbers. E.g. "INV-" produces INV-1001.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_invoice_prefix',
                'type'     => 'text',
                'default'  => 'INV-',
                'css'      => 'max-width: 150px;',
            ),
            array(
                'title'    => __( 'Starting Invoice Number', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'The first invoice number. Only applies if no invoices have been generated yet.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_starting_number',
                'type'     => 'number',
                'default'  => 1001,
                'css'      => 'max-width: 150px;',
                'custom_attributes' => array(
                    'min'  => 1,
                    'step' => 1,
                ),
            ),
            array(
                'title'    => __( 'Date Format', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Select the date format for invoices.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_date_format',
                'type'     => 'select',
                'default'  => 'Y-m-d',
                'options'  => array(
                    'Y-m-d' => __( 'YYYY-MM-DD (e.g. 2024-01-15)', 'wc-pdf-invoice-generator' ),
                    'd/m/Y' => __( 'DD/MM/YYYY (e.g. 15/01/2024)', 'wc-pdf-invoice-generator' ),
                    'm/d/Y' => __( 'MM/DD/YYYY (e.g. 01/15/2024)', 'wc-pdf-invoice-generator' ),
                    'd-m-Y' => __( 'DD-MM-YYYY (e.g. 15-01-2024)', 'wc-pdf-invoice-generator' ),
                    'd.m.Y' => __( 'DD.MM.YYYY (e.g. 15.01.2024)', 'wc-pdf-invoice-generator' ),
                ),
            ),
            array(
                'title'    => __( 'Invoice Footer Note', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Custom note displayed at the bottom of every invoice.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_footer_note',
                'type'     => 'textarea',
                'default'  => __( 'Thank you for your business! This is a computer-generated invoice.', 'wc-pdf-invoice-generator' ),
                'css'      => 'min-width: 400px; height: 60px;',
            ),
            array(
                'title'    => __( 'Invoice Template Style', 'wc-pdf-invoice-generator' ),
                'desc_tip' => __( 'Choose the visual style for your invoices.', 'wc-pdf-invoice-generator' ),
                'id'       => 'wcpig_template_style',
                'type'     => 'select',
                'default'  => 'classic',
                'options'  => array(
                    'classic' => __( 'Classic – Traditional formal style', 'wc-pdf-invoice-generator' ),
                    'modern'  => __( 'Modern – Contemporary gradient design', 'wc-pdf-invoice-generator' ),
                    'minimal' => __( 'Minimal – Clean Swiss-inspired design', 'wc-pdf-invoice-generator' ),
                ),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcpig_invoice_section',
            ),

            // Section: Display & Access.
            array(
                'title' => __( 'Display & Access', 'wc-pdf-invoice-generator' ),
                'type'  => 'title',
                'desc'  => __( 'Control who can access invoices and where they appear.', 'wc-pdf-invoice-generator' ),
                'id'    => 'wcpig_display_section',
            ),
            array(
                'title'   => __( 'Allow Customer Download', 'wc-pdf-invoice-generator' ),
                'desc'    => __( 'Show "Download Invoice" button in My Account > Orders for customers.', 'wc-pdf-invoice-generator' ),
                'id'      => 'wcpig_allow_customer',
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            array(
                'title'   => __( 'Attach Invoice Link to Emails', 'wc-pdf-invoice-generator' ),
                'desc'    => __( 'Select which WooCommerce emails should include an invoice download link.', 'wc-pdf-invoice-generator' ),
                'id'      => 'wcpig_email_attach',
                'type'    => 'multiselect',
                'class'   => 'wc-enhanced-select',
                'default' => array( 'customer_completed_order', 'customer_processing_order' ),
                'options' => array(
                    'new_order'                 => __( 'New Order (Admin)', 'wc-pdf-invoice-generator' ),
                    'customer_processing_order' => __( 'Processing Order (Customer)', 'wc-pdf-invoice-generator' ),
                    'customer_completed_order'  => __( 'Completed Order (Customer)', 'wc-pdf-invoice-generator' ),
                    'customer_invoice'          => __( 'Customer Invoice / Order details', 'wc-pdf-invoice-generator' ),
                    'customer_on_hold_order'    => __( 'On-Hold Order (Customer)', 'wc-pdf-invoice-generator' ),
                ),
                'css'     => 'min-width: 400px;',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcpig_display_section',
            ),
        );

        return apply_filters( 'wcpig_settings', $settings );
    }

    /**
     * Get a single setting value.
     *
     * @param string $key     The option key.
     * @param mixed  $default Default value if option not found.
     * @return mixed The option value.
     */
    public static function get( $key, $default = '' ) {
        return get_option( $key, $default );
    }
}
