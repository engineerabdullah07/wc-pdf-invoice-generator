<?php
/**
 * Invoice data builder.
 *
 * @package WCPIG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WCPIG_Invoice
 *
 * Builds and manages invoice data for a WooCommerce order.
 */
class WCPIG_Invoice {

    /**
     * The WooCommerce order.
     *
     * @var WC_Order
     */
    private $order;

    /**
     * Constructor.
     *
     * @param WC_Order $order The WooCommerce order.
     */
    public function __construct( $order ) {
        $this->order = $order;
    }

    /**
     * Get the invoice number for the order (lazy generation).
     *
     * @return string The formatted invoice number.
     */
    public function get_invoice_number() {
        $invoice_number = $this->order->get_meta( '_wcpig_invoice_number', true );

        if ( empty( $invoice_number ) ) {
            $invoice_number = $this->generate_invoice_number();
        }

        return $invoice_number;
    }

    /**
     * Generate a new invoice number.
     *
     * @return string The generated invoice number.
     */
    private function generate_invoice_number() {
        global $wpdb;

        // Use a database lock to prevent race conditions.
        $wpdb->query( 'START TRANSACTION' );

        $next_number = (int) get_option( 'wcpig_next_invoice_number', 1001 );
        $prefix      = get_option( 'wcpig_invoice_prefix', 'INV-' );

        $invoice_number = $prefix . $next_number;

        // Increment the counter.
        update_option( 'wcpig_next_invoice_number', $next_number + 1 );

        // Store on the order.
        $this->order->update_meta_data( '_wcpig_invoice_number', $invoice_number );
        $this->order->update_meta_data( '_wcpig_invoice_date', current_time( 'mysql' ) );
        $this->order->save();

        $wpdb->query( 'COMMIT' );

        return $invoice_number;
    }

    /**
     * Get the invoice date.
     *
     * @return string Formatted invoice date.
     */
    public function get_invoice_date() {
        $date = $this->order->get_meta( '_wcpig_invoice_date', true );

        if ( empty( $date ) ) {
            $date = $this->order->get_date_created() ? $this->order->get_date_created()->date( 'Y-m-d H:i:s' ) : current_time( 'mysql' );
        }

        $format = get_option( 'wcpig_date_format', 'Y-m-d' );
        return date_i18n( $format, strtotime( $date ) );
    }

    /**
     * Get complete invoice data as an array.
     *
     * @return array Complete invoice data.
     */
    public function get_data() {
        $order = $this->order;

        return array(
            // Company information.
            'company_name'      => get_option( 'wcpig_company_name', get_bloginfo( 'name' ) ),
            'company_tagline'   => get_option( 'wcpig_company_tagline', '' ),
            'company_address'   => get_option( 'wcpig_company_address', '' ),
            'company_phone'     => get_option( 'wcpig_company_phone', '' ),
            'company_email'     => get_option( 'wcpig_company_email', '' ),
            'tax_registration'  => get_option( 'wcpig_tax_registration', '' ),
            'logo_url'          => get_option( 'wcpig_logo_url', '' ),
            'footer_note'       => get_option( 'wcpig_footer_note', '' ),
            'template_style'    => get_option( 'wcpig_template_style', 'classic' ),

            // Invoice details.
            'invoice_number'    => $this->get_invoice_number(),
            'invoice_date'      => $this->get_invoice_date(),
            'order_number'      => $order->get_order_number(),
            'order_date'        => $order->get_date_created() ? date_i18n( get_option( 'wcpig_date_format', 'Y-m-d' ), $order->get_date_created()->getTimestamp() ) : '',

            // Billing information.
            'billing'           => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
                'company'    => $order->get_billing_company(),
                'address_1'  => $order->get_billing_address_1(),
                'address_2'  => $order->get_billing_address_2(),
                'city'       => $order->get_billing_city(),
                'state'      => $order->get_billing_state(),
                'postcode'   => $order->get_billing_postcode(),
                'country'    => $order->get_billing_country(),
                'email'      => $order->get_billing_email(),
                'phone'      => $order->get_billing_phone(),
                'formatted'  => $order->get_formatted_billing_address(),
            ),

            // Shipping information.
            'shipping'          => array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name'  => $order->get_shipping_last_name(),
                'company'    => $order->get_shipping_company(),
                'address_1'  => $order->get_shipping_address_1(),
                'address_2'  => $order->get_shipping_address_2(),
                'city'       => $order->get_shipping_city(),
                'state'      => $order->get_shipping_state(),
                'postcode'   => $order->get_shipping_postcode(),
                'country'    => $order->get_shipping_country(),
                'formatted'  => $order->get_formatted_shipping_address(),
            ),

            // Order items.
            'items'             => $this->get_items_data(),

            // Totals.
            'subtotal'          => $order->get_subtotal(),
            'subtotal_display'  => wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ),
            'shipping_total'    => $order->get_shipping_total(),
            'shipping_display'  => wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ),
            'shipping_method'   => $order->get_shipping_method(),
            'discount_total'    => $order->get_discount_total(),
            'discount_display'  => wc_price( $order->get_discount_total(), array( 'currency' => $order->get_currency() ) ),
            'tax_total'         => $order->get_total_tax(),
            'tax_display'       => wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) ),
            'total'             => $order->get_total(),
            'total_display'     => $order->get_formatted_order_total(),
            'currency'          => $order->get_currency(),
            'currency_symbol'   => get_woocommerce_currency_symbol( $order->get_currency() ),

            // Payment.
            'payment_method'       => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),

            // Status.
            'status'            => $order->get_status(),
            'is_paid'           => $order->is_paid(),

            // Notes.
            'customer_note'     => $order->get_customer_note(),

            // Fees.
            'fees'              => $this->get_fees_data(),

            // Coupons.
            'coupons'           => $this->get_coupons_data(),

            // Tax breakdown.
            'tax_lines'         => $this->get_tax_lines_data(),
        );
    }

    /**
     * Get order items data.
     *
     * @return array Array of item data.
     */
    private function get_items_data() {
        $items_data = array();
        $order      = $this->order;

        foreach ( $order->get_items() as $item_id => $item ) {
            $product  = $item->get_product();
            $sku      = $product ? $product->get_sku() : '';
            $item_data = array(
                'name'          => $item->get_name(),
                'sku'           => $sku,
                'quantity'      => $item->get_quantity(),
                'subtotal'      => $item->get_subtotal(),
                'total'         => $item->get_total(),
                'tax'           => $item->get_total_tax(),
                'unit_price'    => $item->get_quantity() > 0 ? $item->get_subtotal() / $item->get_quantity() : 0,
                'unit_display'  => wc_price(
                    $item->get_quantity() > 0 ? $item->get_subtotal() / $item->get_quantity() : 0,
                    array( 'currency' => $order->get_currency() )
                ),
                'total_display' => wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ),
                'meta'          => array(),
            );

            // Get item meta (variations, custom fields).
            $meta_data = $item->get_formatted_meta_data( '_', true );
            foreach ( $meta_data as $meta ) {
                $item_data['meta'][] = array(
                    'key'   => $meta->display_key,
                    'value' => $meta->display_value,
                );
            }

            $items_data[] = $item_data;
        }

        return $items_data;
    }

    /**
     * Get order fees data.
     *
     * @return array Array of fee data.
     */
    private function get_fees_data() {
        $fees_data = array();
        $order     = $this->order;

        foreach ( $order->get_items( 'fee' ) as $fee ) {
            $fees_data[] = array(
                'name'          => $fee->get_name(),
                'total'         => $fee->get_total(),
                'total_display' => wc_price( $fee->get_total(), array( 'currency' => $order->get_currency() ) ),
            );
        }

        return $fees_data;
    }

    /**
     * Get coupons data.
     *
     * @return array Array of coupon data.
     */
    private function get_coupons_data() {
        $coupons_data = array();
        $order        = $this->order;

        foreach ( $order->get_items( 'coupon' ) as $coupon ) {
            $coupons_data[] = array(
                'code'           => $coupon->get_code(),
                'discount'       => $coupon->get_discount(),
                'discount_display' => wc_price( $coupon->get_discount(), array( 'currency' => $order->get_currency() ) ),
            );
        }

        return $coupons_data;
    }

    /**
     * Get tax lines data.
     *
     * @return array Array of tax line data.
     */
    private function get_tax_lines_data() {
        $tax_data = array();
        $order    = $this->order;

        foreach ( $order->get_items( 'tax' ) as $tax ) {
            $tax_data[] = array(
                'label'         => $tax->get_label(),
                'rate_percent'  => WC_Tax::get_rate_percent_value( $tax->get_rate_id() ),
                'amount'        => $tax->get_tax_total() + $tax->get_shipping_tax_total(),
                'amount_display' => wc_price(
                    $tax->get_tax_total() + $tax->get_shipping_tax_total(),
                    array( 'currency' => $order->get_currency() )
                ),
            );
        }

        return $tax_data;
    }
}
