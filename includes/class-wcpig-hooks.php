<?php
/**
 * Hook integrations for WooCommerce and WordPress.
 *
 * @package WCPIG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WCPIG_Hooks
 *
 * Registers all WordPress/WooCommerce hooks for the invoice system.
 */
class WCPIG_Hooks {

    /**
     * Singleton instance.
     *
     * @var WCPIG_Hooks|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return WCPIG_Hooks
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
        // Register custom query var and endpoint.
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
        add_action( 'init', array( $this, 'add_rewrite_endpoint' ) );
        add_action( 'template_redirect', array( $this, 'handle_invoice_endpoint' ) );

        // Admin: order list action buttons.
        add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_order_list_action' ), 10, 2 );
        add_action( 'admin_head', array( $this, 'order_list_action_icon_css' ) );

        // Admin: order edit page meta box.
        add_action( 'add_meta_boxes', array( $this, 'add_invoice_meta_box' ) );

        // Admin: bulk actions.
        add_filter( 'bulk_actions-edit-shop_order', array( $this, 'register_bulk_actions' ) );
        add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'register_bulk_actions' ) );
        add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_action' ), 10, 3 );
        add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_action' ), 10, 3 );

        // Customer: My Account orders table.
        add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'add_my_account_action' ), 10, 2 );

        // Email: add invoice link to order emails.
        add_action( 'woocommerce_email_after_order_table', array( $this, 'add_invoice_link_to_email' ), 10, 4 );

        // Admin styles.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

        // AJAX handler for admin invoice download.
        add_action( 'wp_ajax_wcpig_download_invoice', array( $this, 'ajax_download_invoice' ) );
    }

    /**
     * Add custom query var.
     *
     * @param array $vars Existing query vars.
     * @return array Modified query vars.
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'wcpig_invoice';
        $vars[] = 'key';
        return $vars;
    }

    /**
     * Add rewrite endpoint for invoices. (Optional, can work with query vars alone.)
     */
    public function add_rewrite_endpoint() {
        add_rewrite_rule(
            '^wcpig-invoice/([0-9]+)/?$',
            'index.php?wcpig_invoice=$matches[1]',
            'top'
        );
    }

    /**
     * Handle the invoice endpoint on the front-end.
     */
    public function handle_invoice_endpoint() {
        $order_id = absint( get_query_var( 'wcpig_invoice', 0 ) );
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_die(
                esc_html__( 'Invalid order.', 'wc-pdf-invoice-generator' ),
                esc_html__( 'Invoice Error', 'wc-pdf-invoice-generator' ),
                array( 'response' => 404 )
            );
        }

        // Security check.
        $order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

        if ( current_user_can( 'manage_woocommerce' ) ) {
            // Admin can view any invoice.
        } elseif ( 'yes' !== get_option( 'wcpig_allow_customer', 'yes' ) ) {
            wp_die(
                esc_html__( 'Invoice download is not enabled.', 'wc-pdf-invoice-generator' ),
                esc_html__( 'Invoice Error', 'wc-pdf-invoice-generator' ),
                array( 'response' => 403 )
            );
        } elseif ( ! $order_key || $order->get_order_key() !== $order_key ) {
            wp_die(
                esc_html__( 'You do not have permission to view this invoice.', 'wc-pdf-invoice-generator' ),
                esc_html__( 'Invoice Error', 'wc-pdf-invoice-generator' ),
                array( 'response' => 403 )
            );
        } elseif ( is_user_logged_in() && (int) $order->get_customer_id() !== get_current_user_id() ) {
            wp_die(
                esc_html__( 'You do not have permission to view this invoice.', 'wc-pdf-invoice-generator' ),
                esc_html__( 'Invoice Error', 'wc-pdf-invoice-generator' ),
                array( 'response' => 403 )
            );
        } elseif ( ! is_user_logged_in() && empty( $order_key ) ) {
            wp_die(
                esc_html__( 'You do not have permission to view this invoice.', 'wc-pdf-invoice-generator' ),
                esc_html__( 'Invoice Error', 'wc-pdf-invoice-generator' ),
                array( 'response' => 403 )
            );
        }

        // Render the invoice.
        $renderer = new WCPIG_PDF_Renderer();
        $renderer->render_invoice( $order );
        exit;
    }

    /**
     * Add "Download Invoice" action button on the Orders list in admin.
     *
     * @param array    $actions Existing actions.
     * @param WC_Order $order   The order object.
     * @return array Modified actions.
     */
    public function add_order_list_action( $actions, $order ) {
        $invoice_url = $this->get_admin_invoice_url( $order );
        $actions['wcpig_invoice'] = array(
            'url'    => $invoice_url,
            'name'   => __( 'Invoice', 'wc-pdf-invoice-generator' ),
            'action' => 'wcpig_invoice',
        );
        return $actions;
    }

    /**
     * CSS for the invoice action icon in the orders list.
     */
    public function order_list_action_icon_css() {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }
        $screen_id = $screen->id;
        if ( 'edit-shop_order' !== $screen_id && 'woocommerce_page_wc-orders' !== $screen_id ) {
            return;
        }
        ?>
        <style>
            .wc-action-button-wcpig_invoice::after {
                font-family: woocommerce !important;
                content: "\e028" !important;
            }
            .wc-action-button-wcpig_invoice {
                color: #2271b1 !important;
            }
            .wc-action-button-wcpig_invoice:hover {
                color: #135e96 !important;
                border-color: #2271b1 !important;
            }
        </style>
        <?php
    }

    /**
     * Add invoice meta box to the order edit page.
     */
    public function add_invoice_meta_box() {
        $screen = wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';

        add_meta_box(
            'wcpig-invoice-actions',
            __( 'PDF Invoice', 'wc-pdf-invoice-generator' ),
            array( $this, 'render_invoice_meta_box' ),
            $screen,
            'side',
            'default'
        );
    }

    /**
     * Render the invoice meta box content.
     *
     * @param WP_Post|WC_Order $post_or_order The post or order object.
     */
    public function render_invoice_meta_box( $post_or_order ) {
        $order = ( $post_or_order instanceof WC_Order ) ? $post_or_order : wc_get_order( $post_or_order->ID );
        if ( ! $order ) {
            echo '<p>' . esc_html__( 'Order not found.', 'wc-pdf-invoice-generator' ) . '</p>';
            return;
        }

        $invoice      = new WCPIG_Invoice( $order );
        $invoice_data = $invoice->get_data();
        $invoice_url  = $this->get_admin_invoice_url( $order );
        ?>
        <div class="wcpig-meta-box">
            <p>
                <strong><?php esc_html_e( 'Invoice Number:', 'wc-pdf-invoice-generator' ); ?></strong><br>
                <?php echo esc_html( $invoice_data['invoice_number'] ); ?>
            </p>
            <p>
                <strong><?php esc_html_e( 'Invoice Date:', 'wc-pdf-invoice-generator' ); ?></strong><br>
                <?php echo esc_html( $invoice_data['invoice_date'] ); ?>
            </p>
            <p class="wcpig-meta-box-buttons">
                <a href="<?php echo esc_url( $invoice_url ); ?>" class="button button-primary" target="_blank">
                    <?php esc_html_e( 'View Invoice', 'wc-pdf-invoice-generator' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Register bulk action for orders.
     *
     * @param array $actions Existing bulk actions.
     * @return array Modified bulk actions.
     */
    public function register_bulk_actions( $actions ) {
        $actions['wcpig_download_invoices'] = __( 'Download Invoices', 'wc-pdf-invoice-generator' );
        return $actions;
    }

    /**
     * Handle the bulk action for downloading invoices.
     *
     * @param string $redirect_to Redirect URL.
     * @param string $action      The action being performed.
     * @param array  $post_ids    Array of selected order IDs.
     * @return string Redirect URL.
     */
    public function handle_bulk_action( $redirect_to, $action, $post_ids ) {
        if ( 'wcpig_download_invoices' !== $action ) {
            return $redirect_to;
        }

        // For bulk action, redirect to a page that opens each invoice in new tabs via JS.
        $invoice_urls = array();
        foreach ( $post_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $invoice_urls[] = $this->get_admin_invoice_url( $order );
            }
        }

        // Store URLs temporarily in a transient.
        $transient_key = 'wcpig_bulk_' . get_current_user_id() . '_' . time();
        set_transient( $transient_key, $invoice_urls, 60 );

        return add_query_arg(
            array(
                'wcpig_bulk_invoices' => $transient_key,
                'wcpig_count'         => count( $invoice_urls ),
            ),
            $redirect_to
        );
    }

    /**
     * Add "Download Invoice" button in My Account > Orders.
     *
     * @param array    $actions Existing actions.
     * @param WC_Order $order   The order object.
     * @return array Modified actions.
     */
    public function add_my_account_action( $actions, $order ) {
        if ( 'yes' !== get_option( 'wcpig_allow_customer', 'yes' ) ) {
            return $actions;
        }

        $actions['wcpig_invoice'] = array(
            'url'  => $this->get_customer_invoice_url( $order ),
            'name' => __( 'Invoice', 'wc-pdf-invoice-generator' ),
        );
        return $actions;
    }

    /**
     * Add invoice link to WooCommerce order emails.
     *
     * @param WC_Order $order         The order object.
     * @param bool     $sent_to_admin Whether the email is sent to admin.
     * @param bool     $plain_text    Whether it's a plain text email.
     * @param WC_Email $email         The email object.
     */
    public function add_invoice_link_to_email( $order, $sent_to_admin, $plain_text, $email = null ) {
        if ( ! $email ) {
            return;
        }

        $allowed_emails = get_option( 'wcpig_email_attach', array() );
        if ( ! is_array( $allowed_emails ) ) {
            $allowed_emails = array();
        }

        if ( ! in_array( $email->id, $allowed_emails, true ) ) {
            return;
        }

        $invoice_url = $this->get_customer_invoice_url( $order );

        if ( $plain_text ) {
            /* translators: %s: Invoice URL */
            echo "\n\n" . sprintf( esc_html__( 'View your invoice: %s', 'wc-pdf-invoice-generator' ), esc_url( $invoice_url ) ) . "\n";
        } else {
            ?>
            <div style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #2271b1; text-align: center;">
                <p style="margin: 0 0 10px 0; font-size: 14px; color: #333;">
                    <?php esc_html_e( 'Your invoice is ready for download.', 'wc-pdf-invoice-generator' ); ?>
                </p>
                <a href="<?php echo esc_url( $invoice_url ); ?>"
                   style="display: inline-block; padding: 10px 24px; background-color: #2271b1; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: 600; font-size: 14px;"
                   target="_blank">
                    <?php esc_html_e( '📄 View Your Invoice', 'wc-pdf-invoice-generator' ); ?>
                </a>
            </div>
            <?php
        }
    }

    /**
     * Enqueue admin styles.
     *
     * @param string $hook_suffix The current admin page hook suffix.
     */
    public function enqueue_admin_styles( $hook_suffix ) {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $valid_screens = array( 'edit-shop_order', 'shop_order', 'woocommerce_page_wc-orders' );
        if ( in_array( $screen->id, $valid_screens, true ) ) {
            wp_enqueue_style(
                'wcpig-admin',
                WCPIG_PLUGIN_URL . 'assets/css/wcpig-admin.css',
                array(),
                WCPIG_VERSION
            );
        }

        // Handle bulk invoice downloads with JS.
        if ( isset( $_GET['wcpig_bulk_invoices'] ) ) {
            $transient_key = sanitize_text_field( wp_unslash( $_GET['wcpig_bulk_invoices'] ) );
            $invoice_urls  = get_transient( $transient_key );
            if ( $invoice_urls && is_array( $invoice_urls ) ) {
                delete_transient( $transient_key );
                $escaped_urls = array_map( 'esc_url', $invoice_urls );
                ?>
                <script type="text/javascript">
                    document.addEventListener('DOMContentLoaded', function() {
                        var urls = <?php echo wp_json_encode( $escaped_urls ); ?>;
                        urls.forEach(function(url, index) {
                            setTimeout(function() {
                                window.open(url, '_blank');
                            }, index * 500);
                        });
                    });
                </script>
                <?php
            }
        }
    }

    /**
     * AJAX handler for admin invoice download.
     */
    public function ajax_download_invoice() {
        check_ajax_referer( 'wcpig_admin_invoice', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( __( 'Unauthorized.', 'wc-pdf-invoice-generator' ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order    = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( __( 'Invalid order.', 'wc-pdf-invoice-generator' ) );
        }

        wp_send_json_success( array(
            'url' => $this->get_admin_invoice_url( $order ),
        ) );
    }

    /**
     * Get the invoice URL for admin use.
     *
     * @param WC_Order $order The order object.
     * @return string The invoice URL.
     */
    private function get_admin_invoice_url( $order ) {
        return add_query_arg(
            array(
                'wcpig_invoice' => $order->get_id(),
                'key'           => $order->get_order_key(),
            ),
            home_url( '/' )
        );
    }

    /**
     * Get the invoice URL for customer use.
     *
     * @param WC_Order $order The order object.
     * @return string The invoice URL.
     */
    private function get_customer_invoice_url( $order ) {
        return add_query_arg(
            array(
                'wcpig_invoice' => $order->get_id(),
                'key'           => $order->get_order_key(),
            ),
            home_url( '/' )
        );
    }
}
