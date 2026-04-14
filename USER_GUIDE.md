# Panth Checkout Extended — User Guide

This guide walks a Magento store administrator through every screen
and setting of the Panth Checkout Extended extension. No coding required.

---

## Table of contents

1. [Installation](#1-installation)
2. [Verifying the extension is active](#2-verifying-the-extension-is-active)
3. [General settings](#3-general-settings)
4. [Layout settings](#4-layout-settings)
5. [Style settings](#5-style-settings)
6. [Cart features](#6-cart-features)
7. [Newsletter subscription](#7-newsletter-subscription)
8. [Form style settings](#8-form-style-settings)
9. [Shipping settings](#9-shipping-settings)
10. [Payment settings](#10-payment-settings)
11. [Billing settings](#11-billing-settings)
12. [Custom code injection](#12-custom-code-injection)
13. [Troubleshooting](#13-troubleshooting)

---

## 1. Installation

### Composer (recommended)

```bash
composer require mage2kishan/module-checkout-extended
bin/magento module:enable Panth_Core Panth_CheckoutExtended
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual zip

1. Download the extension package zip
2. Extract to `app/code/Panth/CheckoutExtended`
3. Make sure `app/code/Panth/Core` is also present
4. Run the same `module:enable ... cache:flush` commands above

### Confirm

```bash
bin/magento module:status Panth_CheckoutExtended
# Module is enabled
```

---

## 2. Verifying the extension is active

After installation, two things should be true:

1. **Configuration page exists** — Stores -> Configuration -> Panth Extensions -> Checkout Extended is reachable
2. **Checkout page changed** — visit your store's checkout page and observe the new multi-column layout (if columns are set to 2 or 3)

If neither works, see [Troubleshooting](#13-troubleshooting).

---

## 3. General settings

Navigate to **Stores -> Configuration -> Panth Extensions -> Checkout Extended -> General**.

| Setting | Default | What it does |
|---|---|---|
| **Enable Checkout Extended** | Yes | Master switch. Set to No to disable all checkout enhancements and revert to the default Magento checkout layout. |

---

## 4. Layout settings

Navigate to **Layout** group.

| Setting | Default | What it does |
|---|---|---|
| **Columns** | 3 | Choose 1 (stacked), 2 (content + sidebar), or 3 (shipping - payment - summary) column layout |
| **Sidebar Position** | Right | Place the order summary sidebar on the left or right |
| **Sticky Sidebar** | No | When enabled, the sidebar stays visible as the customer scrolls |

### Layout tips

- **1 column** works best for stores with very few products per order
- **2 columns** is the classic modern checkout layout
- **3 columns** shows shipping, payment, and summary side by side — best for desktop with large screens

---

## 5. Style settings

Navigate to **Style** group.

| Setting | Default | What it does |
|---|---|---|
| **Card Style** | Elevated (Shadow) | Visual treatment for checkout section cards. Options: Elevated, Bordered, Flat, Glassmorphism |
| **Accent Color** | #1a1a2e | Primary accent colour used for buttons, links, and highlights. Uses the admin colour picker. |
| **Border Radius** | 12px | Corner radius for cards and form elements |
| **Step Indicators** | No | Show step number indicators on each checkout section |

### Accent colour notes

The accent colour is validated server-side. Only valid hex values (#rgb or #rrggbb format) are accepted. Invalid values fall back to the default #1a1a2e.

---

## 6. Cart features

Navigate to **Cart** group.

| Setting | Default | What it does |
|---|---|---|
| **Qty Increment** | No | Show +/- buttons next to each item in the order summary. Respects the product's stock qty_increments setting. |
| **Show SKU** | No | Display the product SKU below each item name in the summary |
| **Product Link** | No | Make item names in the summary clickable links to the product page |

### Qty increment details

When enabled, the +/- buttons appear next to the quantity in the order summary sidebar. Each click updates the cart via AJAX. The increment step respects the product's inventory qty_increments setting (e.g., if qty_increments is 0.5, clicking + adds 0.5).

---

## 7. Newsletter subscription

Navigate to **Newsletter** group.

| Setting | Default | What it does |
|---|---|---|
| **Enable Newsletter Checkbox** | Yes | Show a newsletter subscription checkbox in the checkout sidebar |
| **Checkbox Label** | "Subscribe to our newsletter" | The label text shown next to the checkbox |
| **Checked by Default** | Yes | Whether the checkbox is pre-checked |

### How it works

- The checkbox appears in the order summary sidebar
- On order placement, the subscription preference is sent via payment extension attributes
- Separate plugins handle guest and logged-in customer subscription
- If the customer is already subscribed, the checkbox state is pre-set and a subscription is not duplicated
- Newsletter subscription failure never blocks order placement — errors are logged silently

---

## 8. Form style settings

Navigate to **Form Styles** group.

| Setting | Default | What it does |
|---|---|---|
| **Field Mode** | Compact | Compact places multiple fields per row. Full Width places one field per row. |
| **Use Placeholders** | No | Show placeholder text inside form fields |
| **Show Tooltips** | No | Show tooltip icons next to form fields with help text |

---

## 9. Shipping settings

Navigate to **Shipping** group.

| Setting | Default | What it does |
|---|---|---|
| **Default Shipping Method** | (none) | Pre-select a shipping method by code (e.g., "flatrate_flatrate") |
| **Hide Single Method** | No | When only one shipping method is available, hide the radio button and just show the method name |
| **Sort by Price** | No | Sort available shipping methods by price (lowest first) |

---

## 10. Payment settings

Navigate to **Payment** group.

| Setting | Default | What it does |
|---|---|---|
| **Default Payment Method** | (none) | Pre-select a payment method by code (e.g., "checkmo") |

---

## 11. Billing settings

Navigate to **Billing** group.

| Setting | Default | What it does |
|---|---|---|
| **Show Billing Title** | Yes | Show/hide the "Billing Address" section title |

---

## 12. Custom code injection

Navigate to **Custom Code** group.

| Setting | Default | What it does |
|---|---|---|
| **Custom CSS** | (empty) | CSS code injected as an inline `<style>` block on the checkout page |
| **Custom JS** | (empty) | JavaScript code injected via `require([], function() { ... })` on the checkout page |

### Use cases

- Override specific checkout styles without creating a theme
- Add tracking pixels or conversion scripts
- Inject A/B testing code
- Hide/show specific elements with CSS

**Security note:** Only store administrators with the Panth_CheckoutExtended::config ACL permission can edit these fields. The CSS and JS are injected with `@noEscape` because they are admin-authored content.

---

## 13. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Checkout looks unchanged after enabling | Cache not flushed | Run `bin/magento cache:flush` and hard-refresh the browser |
| Layout handle not applied | Observer not firing | Verify `Panth_CheckoutExtended` is enabled: `bin/magento module:status Panth_CheckoutExtended` |
| Newsletter checkbox not visible | Newsletter disabled in config | Check Configuration -> Newsletter -> Enable Newsletter Checkbox is set to Yes |
| Qty +/- buttons missing | Cart feature disabled | Check Configuration -> Cart -> Qty Increment is set to Yes |
| Shipping info not auto-saving | JavaScript error | Check browser console for JS errors. Ensure RequireJS config is deployed: `bin/magento setup:static-content:deploy -f` |
| Accent colour not applying | Invalid hex value | Use the colour picker or enter a valid hex value like #1a1a2e |
| Custom CSS/JS not appearing | Cache | Flush full page cache and browser cache |
| Coupon code not in sidebar | Extension disabled | Enable the extension in General settings. The discount code is moved to the sidebar only when CheckoutExtended is active. |

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422

Response time: 1-2 business days for paid licenses.
