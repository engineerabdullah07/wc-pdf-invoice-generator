# 🧾 PDF Invoice Generator for WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-indigo.svg)](https://php.net/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)

**PDF Invoice Generator for WooCommerce** is an open-source, CodeCanyon-quality plugin that automates invoice creation and delivery for your WooCommerce e-commerce store.

Generate clean, tax-compliant, professional invoices with custom company branding, auto-incrementing invoice numbers, and 1-click PDF download capabilities.

---

## ✨ Features

- 🔢 **Sequential Invoice Numbers**: Customizable prefix and starting number (e.g. `INV-1001`).
- 🎨 **3 Built-In Design Styles**: Classic, Modern gradient, and Swiss Minimalist layouts.
- 🏢 **Branded Headers**: Add your logo, trade name, address, contact info, and Tax/NTN/VAT identifiers.
- 🖨️ **Pixel-Perfect A4 Print Layout**: Dedicated print stylesheets for flawless PDF generation via browser.
- 📧 **Automated Email Links**: Seamlessly inserts "View Your Invoice" links into WooCommerce order notification emails.
- 👥 **Customer Account Access**: Customers can download invoices on demand from their account order history.
- ⚡ **Zero Bloat & Blazing Fast**: Pure WooCommerce API with no resource-heavy PHP PDF binaries required.
- 🔒 **Secure Authorization**: Tokenized access verification ensures customers only access their own invoices.

---

## 🚀 Installation

1. Clone or download this repository:
   ```bash
   git clone https://github.com/nexusworks421-boop/wc-pdf-invoice-generator.git
   ```
2. Place the folder into your WordPress plugin directory:
   ```
   wp-content/plugins/wc-pdf-invoice-generator
   ```
3. Activate the plugin via **WordPress Admin > Plugins**.
4. Configure your company name, tax details, and logo in **WooCommerce > Settings > PDF Invoices**.

---

## ⚙️ Configuration Settings

Under **WooCommerce > Settings > PDF Invoices**:

| Option | Description |
|:---|:---|
| **Company Name** | Displayed prominently at the top of the invoice |
| **Tax ID / NTN / VAT** | Your official tax registration number |
| **Invoice Number Prefix** | Prefix applied to sequential numbers (e.g. `INV-`) |
| **Next Invoice Number** | Starting sequence counter |
| **Template Style** | Choose between Classic, Modern, or Minimal |
| **Email Notifications** | Choose which order status emails include invoice links |

---

## 📄 License

This project is licensed under the [GNU General Public License v2.0](LICENSE).
