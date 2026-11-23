<?php
/**
 * Invoice HTML Template.
 *
 * @package WCPIG
 * @var array    $data  The prepared invoice data array.
 * @var WC_Order $order The WooCommerce order object.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$company = $data['company'];
$billing = $data['billing'];
$shipping = $data['shipping'];
$items   = $data['items'];
$template_style = get_option( 'wcpig_template_style', 'classic' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( sprintf( __( 'Invoice %s', 'wc-pdf-invoice-generator' ), $data['invoice_number'] ) ); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url( WCPIG_PLUGIN_URL . 'assets/css/wcpig-invoice.css' ); ?>?ver=<?php echo esc_attr( WCPIG_VERSION ); ?>">
    <style>
        /* Style variation overrides */
        <?php if ( 'modern' === $template_style ) : ?>
            .wcpig-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 30px; border-radius: 10px; margin-bottom: 25px; }
            .wcpig-header h1, .wcpig-header .wcpig-company-name { color: #f8fafc; }
            .wcpig-invoice-meta { color: #cbd5e1; }
        <?php elseif ( 'minimal' === $template_style ) : ?>
            .wcpig-invoice-wrap { border: none; box-shadow: none; padding: 20px 0; }
            .wcpig-items-table th { border-bottom: 1px solid #111; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; font-size: 11px; }
            .wcpig-items-table td { border-bottom: 1px solid #eee; }
        <?php endif; ?>
    </style>
</head>
<body class="wcpig-invoice-body style-<?php echo esc_attr( $template_style ); ?>">

    <!-- Top Action Bar (Hidden in Print) -->
    <div class="wcpig-action-bar">
        <div class="wcpig-action-inner">
            <span class="wcpig-action-title">
                <?php echo esc_html( sprintf( __( 'Invoice #%s', 'wc-pdf-invoice-generator' ), $data['invoice_number'] ) ); ?>
            </span>
            <div class="wcpig-action-buttons">
                <button onclick="window.print()" class="wcpig-btn wcpig-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    <?php esc_html_e( 'Print / Save as PDF', 'wc-pdf-invoice-generator' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Printable Invoice Container -->
    <div class="wcpig-invoice-wrap">
        
        <?php if ( ! empty( $data['is_paid'] ) ) : ?>
            <div class="wcpig-paid-watermark"><?php esc_html_e( 'PAID', 'wc-pdf-invoice-generator' ); ?></div>
        <?php endif; ?>

        <!-- Header -->
        <header class="wcpig-header">
            <div class="wcpig-company-info">
                <?php if ( ! empty( $company['logo_url'] ) ) : ?>
                    <img src="<?php echo esc_url( $company['logo_url'] ); ?>" alt="<?php echo esc_attr( $company['name'] ); ?>" class="wcpig-company-logo">
                <?php endif; ?>
                <h1 class="wcpig-company-name"><?php echo esc_html( $company['name'] ); ?></h1>
                <?php if ( ! empty( $company['tagline'] ) ) : ?>
                    <p class="wcpig-company-tagline"><?php echo esc_html( $company['tagline'] ); ?></p>
                <?php endif; ?>
                <div class="wcpig-company-details">
                    <?php if ( ! empty( $company['address'] ) ) : ?>
                        <p><?php echo nl2br( esc_html( $company['address'] ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $company['phone'] ) ) : ?>
                        <p><strong><?php esc_html_e( 'Tel:', 'wc-pdf-invoice-generator' ); ?></strong> <?php echo esc_html( $company['phone'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $company['email'] ) ) : ?>
                        <p><strong><?php esc_html_e( 'Email:', 'wc-pdf-invoice-generator' ); ?></strong> <?php echo esc_html( $company['email'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $company['tax_id'] ) ) : ?>
                        <p><strong><?php esc_html_e( 'Tax/NTN/VAT ID:', 'wc-pdf-invoice-generator' ); ?></strong> <?php echo esc_html( $company['tax_id'] ); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="wcpig-invoice-meta">
                <h2 class="wcpig-invoice-title"><?php esc_html_e( 'TAX INVOICE', 'wc-pdf-invoice-generator' ); ?></h2>
                <table class="wcpig-meta-table">
                    <tr>
                        <th><?php esc_html_e( 'Invoice Number:', 'wc-pdf-invoice-generator' ); ?></th>
                        <td><strong><?php echo esc_html( $data['invoice_number'] ); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Order Number:', 'wc-pdf-invoice-generator' ); ?></th>
                        <td>#<?php echo esc_html( $data['order_number'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Invoice Date:', 'wc-pdf-invoice-generator' ); ?></th>
                        <td><?php echo esc_html( $data['invoice_date'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Payment Method:', 'wc-pdf-invoice-generator' ); ?></th>
                        <td><?php echo esc_html( $data['payment_method_title'] ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Order Status:', 'wc-pdf-invoice-generator' ); ?></th>
                        <td><span class="wcpig-status-badge status-<?php echo esc_attr( $data['status'] ); ?>"><?php echo esc_html( ucfirst( $data['status'] ) ); ?></span></td>
                    </tr>
                </table>
            </div>
        </header>

        <!-- Addresses Row -->
        <section class="wcpig-addresses">
            <div class="wcpig-col">
                <h3><?php esc_html_e( 'Billed To (Customer):', 'wc-pdf-invoice-generator' ); ?></h3>
                <div class="wcpig-address-box">
                    <strong><?php echo esc_html( $billing['first_name'] . ' ' . $billing['last_name'] ); ?></strong>
                    <?php if ( ! empty( $billing['company'] ) ) : ?>
                        <p><?php echo esc_html( $billing['company'] ); ?></p>
                    <?php endif; ?>
                    <p><?php echo esc_html( $billing['address_1'] ); ?></p>
                    <?php if ( ! empty( $billing['address_2'] ) ) : ?>
                        <p><?php echo esc_html( $billing['address_2'] ); ?></p>
                    <?php endif; ?>
                    <p><?php echo esc_html( $billing['city'] . ', ' . $billing['state'] . ' ' . $billing['postcode'] ); ?></p>
                    <p><?php echo esc_html( $billing['country'] ); ?></p>
                    <?php if ( ! empty( $billing['phone'] ) ) : ?>
                        <p><strong><?php esc_html_e( 'Phone:', 'wc-pdf-invoice-generator' ); ?></strong> <?php echo esc_html( $billing['phone'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $billing['email'] ) ) : ?>
                        <p><strong><?php esc_html_e( 'Email:', 'wc-pdf-invoice-generator' ); ?></strong> <?php echo esc_html( $billing['email'] ); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( ! empty( $shipping['address_1'] ) ) : ?>
                <div class="wcpig-col">
                    <h3><?php esc_html_e( 'Shipped To:', 'wc-pdf-invoice-generator' ); ?></h3>
                    <div class="wcpig-address-box">
                        <strong><?php echo esc_html( $shipping['first_name'] . ' ' . $shipping['last_name'] ); ?></strong>
                        <?php if ( ! empty( $shipping['company'] ) ) : ?>
                            <p><?php echo esc_html( $shipping['company'] ); ?></p>
                        <?php endif; ?>
                        <p><?php echo esc_html( $shipping['address_1'] ); ?></p>
                        <?php if ( ! empty( $shipping['address_2'] ) ) : ?>
                            <p><?php echo esc_html( $shipping['address_2'] ); ?></p>
                        <?php endif; ?>
                        <p><?php echo esc_html( $shipping['city'] . ', ' . $shipping['state'] . ' ' . $shipping['postcode'] ); ?></p>
                        <p><?php echo esc_html( $shipping['country'] ); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Line Items Table -->
        <table class="wcpig-items-table">
            <thead>
                <tr>
                    <th class="col-item"><?php esc_html_e( 'Item / Description', 'wc-pdf-invoice-generator' ); ?></th>
                    <th class="col-sku"><?php esc_html_e( 'SKU', 'wc-pdf-invoice-generator' ); ?></th>
                    <th class="col-qty"><?php esc_html_e( 'Qty', 'wc-pdf-invoice-generator' ); ?></th>
                    <th class="col-price"><?php esc_html_e( 'Price', 'wc-pdf-invoice-generator' ); ?></th>
                    <th class="col-total"><?php esc_html_e( 'Total', 'wc-pdf-invoice-generator' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $items as $item ) : ?>
                    <tr>
                        <td class="col-item">
                            <span class="wcpig-item-name"><?php echo esc_html( $item['name'] ); ?></span>
                            <?php if ( ! empty( $item['meta'] ) ) : ?>
                                <ul class="wcpig-item-meta">
                                    <?php foreach ( $item['meta'] as $meta ) : ?>
                                        <li><strong><?php echo esc_html( $meta->display_key ); ?>:</strong> <?php echo wp_kses_post( $meta->display_value ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </td>
                        <td class="col-sku"><?php echo esc_html( $item['sku'] ?: '-' ); ?></td>
                        <td class="col-qty"><?php echo esc_html( $item['quantity'] ); ?></td>
                        <td class="col-price"><?php echo wp_kses_post( $item['unit_display'] ); ?></td>
                        <td class="col-total"><?php echo wp_kses_post( $item['total_display'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals & Summary -->
        <div class="wcpig-totals-section">
            <div class="wcpig-notes-wrap">
                <?php if ( ! empty( $data['customer_note'] ) ) : ?>
                    <div class="wcpig-customer-note">
                        <h4><?php esc_html_e( 'Customer Note:', 'wc-pdf-invoice-generator' ); ?></h4>
                        <p><?php echo nl2br( esc_html( $data['customer_note'] ) ); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <table class="wcpig-totals-table">
                <tr>
                    <th><?php esc_html_e( 'Subtotal:', 'wc-pdf-invoice-generator' ); ?></th>
                    <td><?php echo wp_kses_post( $data['subtotal_display'] ); ?></td>
                </tr>

                <?php if ( $data['discount_total'] > 0 ) : ?>
                    <tr class="discount-row">
                        <th><?php esc_html_e( 'Discount:', 'wc-pdf-invoice-generator' ); ?></th>
                        <td>-<?php echo wp_kses_post( $data['discount_display'] ); ?></td>
                    </tr>
                <?php endif; ?>

                <?php if ( $data['shipping_total'] > 0 || ! empty( $data['shipping_method'] ) ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Shipping:', 'wc-pdf-invoice-generator' ); ?> (<?php echo esc_html( $data['shipping_method'] ); ?>)</th>
                        <td><?php echo wp_kses_post( $data['shipping_display'] ); ?></td>
                    </tr>
                <?php endif; ?>

                <?php foreach ( $data['tax_lines'] as $tax ) : ?>
                    <tr>
                        <th><?php echo esc_html( $tax['label'] ); ?>:</th>
                        <td><?php echo wp_kses_post( $tax['amount_display'] ); ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr class="grand-total-row">
                    <th><?php esc_html_e( 'Grand Total:', 'wc-pdf-invoice-generator' ); ?></th>
                    <td><?php echo wp_kses_post( $data['total_display'] ); ?></td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <?php if ( ! empty( $company['footer_note'] ) ) : ?>
            <footer class="wcpig-footer">
                <p><?php echo nl2br( esc_html( $company['footer_note'] ) ); ?></p>
            </footer>
        <?php endif; ?>

    </div>

</body>
</html>
