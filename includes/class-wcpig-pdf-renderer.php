<?php
/**
 * HTML invoice renderer (print-to-PDF approach).
 *
 * @package WCPIG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WCPIG_PDF_Renderer
 *
 * Renders the HTML invoice page with print-optimized CSS.
 */
class WCPIG_PDF_Renderer {

    /**
     * Render a full-page HTML invoice for the given order.
     *
     * @param WC_Order $order The WooCommerce order.
     */
    public function render_invoice( $order ) {
        $invoice = new WCPIG_Invoice( $order );
        $data    = $invoice->get_data();

        // Set nocache headers.
        nocache_headers();

        // Load the template.
        $template_path = $this->locate_template();

        // Output the HTML page.
        include $template_path;
    }

    /**
     * Locate the invoice template, allowing theme override.
     *
     * @return string Full path to the template file.
     */
    private function locate_template() {
        // Allow themes to override: wp-content/themes/your-theme/wc-pdf-invoice-generator/invoice-template.php
        $template = locate_template( 'wc-pdf-invoice-generator/invoice-template.php' );

        if ( ! $template ) {
            $template = WCPIG_PLUGIN_DIR . 'templates/invoice-template.php';
        }

        return apply_filters( 'wcpig_invoice_template_path', $template );
    }

    /**
     * Get the invoice HTML as a string (for future PDF library integration).
     *
     * @param WC_Order $order The WooCommerce order.
     * @return string The rendered HTML.
     */
    public function get_invoice_html( $order ) {
        $invoice = new WCPIG_Invoice( $order );
        $data    = $invoice->get_data();

        $template_path = $this->locate_template();

        ob_start();
        include $template_path;
        return ob_get_clean();
    }
}
