# Panth Checkout Extended for Magento 2

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![Luma Compatible](https://img.shields.io/badge/Luma-Compatible-green)]()

**Enhanced one-page checkout** for Magento 2 — configurable
multi-column layouts, sidebar order summary with place-order button,
newsletter subscription, quantity increment controls, coupon code in
sidebar, custom CSS/JS injection, and modern card-style UI. Fully
admin-configurable with zero code changes.

---

## Why this extension

| | Default Magento Checkout | **Panth Checkout Extended** |
|---|---|---|
| Layout | Fixed 2-step accordion | Configurable 1/2/3 column layout with all steps visible |
| Sidebar | Static summary, no actions | Sticky sidebar with qty controls, place-order button, coupon code, newsletter |
| Newsletter | Not available at checkout | Checkbox in sidebar, auto-subscribes on order placement (guest + customer) |
| Qty controls | Not available in summary | +/- increment buttons with stock-aware qty_increments |
| Coupon code | Inside payment step | Moved to sidebar summary for quick access |
| Place order | Bottom of payment step only | Sidebar place-order button always visible |
| Styling | Fixed Luma theme | Card styles (elevated/bordered/flat/glass), accent color, border radius, field modes |
| Custom code | Requires theme override | Admin textarea for custom CSS and JS, injected at checkout |

---

## Features

### Layout
- **1-column** (stacked), **2-column** (content + sidebar), or **3-column** (shipping | payment | summary)
- Sidebar position: left or right
- Sticky sidebar option

### Checkout UX
- **Auto-save shipping info** — shipping address and method are saved to the server automatically as the customer fills them in, so the payment step loads instantly
- **Billing address real-time sync** — when "same as shipping" is checked, billing address updates in real time as the customer types
- **Sidebar place-order button** — always visible in the order summary
- **Coupon code moved to sidebar** — accessible without scrolling to the payment step
- **Auto-expand cart items** — order summary items are expanded by default
- **Auto-expand discount code** — discount input is expanded by default

### Cart features
- **Qty increment/decrement** — +/- buttons in order summary with stock-aware qty_increments
- **Product SKU display** — show SKU in order summary items
- **Product link** — item names link back to the product page

### Newsletter subscription
- Checkbox in checkout sidebar
- Configurable label and default checked state
- Subscribes both guest and logged-in customers on order placement
- Uses payment extension attributes for clean API transport
- Skips if customer is already subscribed

### Styling
- Card styles: Elevated (shadow), Bordered, Flat, Glassmorphism
- Accent color picker in admin
- Border radius control
- Step indicators
- Field modes: Compact (multi-field rows) or Full Width
- Placeholder and tooltip toggles
- Billing title visibility

### Custom code injection
- Custom CSS textarea — injected as inline style at checkout
- Custom JS textarea — injected via RequireJS at checkout

### Admin configuration
- Stores -> Configuration -> Panth Extensions -> Checkout Extended
- All features togglable per store view
- ACL resource for granular admin permissions

---

## Installation

### Via Composer (recommended)

```bash
composer require mage2kishan/module-checkout-extended
bin/magento module:enable Panth_Core Panth_CheckoutExtended
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Via uploaded zip

1. Download the extension zip from the Marketplace
2. Extract to `app/code/Panth/CheckoutExtended`
3. Make sure `app/code/Panth/Core` is also installed
4. Run the same commands above starting from `module:enable`

### Verify

```bash
bin/magento module:status Panth_CheckoutExtended
# Module is enabled
```

---

## Requirements

| | Required |
|---|---|
| Magento | 2.4.4 - 2.4.8 (Open Source / Commerce / Cloud) |
| PHP | 8.1 / 8.2 / 8.3 / 8.4 |
| `mage2kishan/module-core` | ^1.0 (installed automatically as a composer dependency) |

---

## Configuration

Open **Stores -> Configuration -> Panth Extensions -> Checkout Extended**.

### General
- **Enable Checkout Extended** — master switch

### Layout
- **Columns** — 1 / 2 / 3 column layout
- **Sidebar Position** — left or right
- **Sticky Sidebar** — yes/no

### Style
- **Card Style** — Elevated / Bordered / Flat / Glassmorphism
- **Accent Color** — color picker
- **Border Radius** — pixel value
- **Step Indicators** — show/hide

### Cart
- **Qty Increment** — enable +/- buttons
- **Show SKU** — display product SKU
- **Product Link** — link item names to product pages

### Newsletter
- **Enable** — show/hide newsletter checkbox
- **Checkbox Label** — custom label text
- **Checked by Default** — yes/no

### Form Styles
- **Field Mode** — Compact / Full Width
- **Use Placeholders** — yes/no
- **Show Tooltips** — yes/no

### Shipping
- **Default Shipping Method** — pre-select a method
- **Hide Single Method** — hide radio when only one option
- **Sort by Price** — sort methods by price

### Payment
- **Default Payment Method** — pre-select a method

### Billing
- **Show Billing Title** — yes/no

### Custom Code
- **Custom CSS** — admin textarea
- **Custom JS** — admin textarea

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |

Response time: 1-2 business days for paid licenses.

---

## License

Commercial — see `LICENSE.txt`. One license per Magento production
installation. Includes 12 months of free updates and email support.

---

## About the developer

Built and maintained by **Kishan Savaliya** — https://kishansavaliya.com.
Builds high-quality Magento 2 extensions and themes for both Hyva and
Luma storefronts.
