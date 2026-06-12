# Panth Checkout Extended — User Guide

This guide walks a Magento store administrator through every screen
and setting of the Panth Checkout Extended extension, step by step.
No coding required.

All settings live in one place:

> **Stores -> Configuration -> Panth Extensions -> Checkout Extended**

Every setting can be configured per website or per store view using the
**Store View** scope switcher in the top-left of the configuration page.
After changing any setting, click **Save Config** and flush the cache
(**System -> Cache Management -> Flush Magento Cache**) before checking
the storefront.

---

## Table of contents

1. [Installation](#1-installation)
2. [Verifying the extension is active](#2-verifying-the-extension-is-active)
3. [General](#3-general)
4. [Layout](#4-layout)
5. [Style](#5-style)
6. [Cart & Order Summary](#6-cart--order-summary)
7. [Newsletter Subscription](#7-newsletter-subscription)
8. [Form Styles](#8-form-styles)
9. [Shipping](#9-shipping)
10. [Payment](#10-payment)
11. [Billing](#11-billing)
12. [Custom Code](#12-custom-code)
13. [Recommended starter setup](#13-recommended-starter-setup)
14. [Troubleshooting](#14-troubleshooting)

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

1. **Configuration page exists** — Stores -> Configuration -> Panth
   Extensions -> Checkout Extended is reachable and shows ten groups:
   General, Layout, Style, Cart & Order Summary, Newsletter Subscription,
   Form Styles, Shipping, Payment, Billing, and Custom Code.
2. **Checkout page changed** — add any product to the cart on your
   storefront and go to checkout. With default settings you should see
   the modern multi-column layout with the order summary in a right-hand
   sidebar.

A quick technical check: open your browser's developer tools on the
checkout page and inspect the `<body>` tag. When the extension is
active it carries classes such as `panth-checkout-extended`,
`panth-checkout-3col`, `panth-sidebar-right`, and `panth-card-elevated`
that reflect your configuration.

If neither works, see [Troubleshooting](#14-troubleshooting).

---

## 3. General

Open the **General** group.

| Setting | Default | What it does |
|---|---|---|
| **Enable Checkout Extended** | Yes | Master switch. Set to **No** to disable all checkout enhancements and revert to the default Magento checkout. |

**Recommended:** Yes. Use the store-view scope switcher to disable it
on a single store view if you want to A/B compare against the stock
checkout.

**Verify on the storefront:** Set to No, save, flush cache, and reload
checkout — it should look like stock Magento again. Set back to Yes
and the extended layout returns. None of the `panth-*` body classes
appear while disabled.

---

## 4. Layout

Open the **Layout** group. These settings control the overall page
structure of the checkout.

| Setting | Default | What it does |
|---|---|---|
| **Columns** | 3 | Number of checkout columns: **1** (everything stacked), **2** (content + sidebar), or **3** (shipping \| payment \| summary side by side). |
| **Sidebar Position** | Right | Place the order summary sidebar on the **Left** or **Right** of the main content. |
| **Sticky Sidebar** | No | When enabled, the order summary stays pinned in view as the customer scrolls long forms. |

**Recommended:**

- **3 columns** for desktop-heavy stores with wide screens — shipping,
  payment, and summary are all visible at once, so customers never lose
  sight of the total.
- **2 columns** is the classic modern checkout and the safest default
  for most stores.
- **1 column** suits stores with very simple orders or strongly
  mobile-first audiences. (On small screens all layouts collapse to a
  single column automatically.)
- Turn **Sticky Sidebar** on if your shipping form is long — the
  place-order button stays reachable.

**Verify on the storefront:** Reload the checkout after saving. With 3
columns the shipping step, payment step, and summary sit side by side
on desktop. Switch Sidebar Position to Left and the summary jumps to
the left edge. With Sticky Sidebar on, scroll down — the summary
follows you. Resize the browser window to phone width and confirm the
layout stacks into one column.

---

## 5. Style

Open the **Style** group. These settings control the visual look of
the checkout cards.

| Setting | Default | What it does |
|---|---|---|
| **Card Style** | Elevated (Shadow) | Visual treatment for checkout section cards: **Elevated** (drop shadow), **Bordered** (1px outline), **Flat** (no shadow or border), or **Glassmorphism** (translucent frosted-glass effect). |
| **Accent Color** | #1a1a2e | Primary accent colour used for buttons, links, focus rings, and highlights. Edited with the built-in admin colour picker. |
| **Border Radius (px)** | 12 | Corner radius applied to cards and form elements, in pixels. `0` gives sharp corners; `16`+ gives a soft, rounded look. |
| **Step Indicators** | No | Show numbered step badges (1, 2, 3 …) above each checkout section so customers can see their progress. |

**Recommended:** Match your theme. Elevated works on light themes;
Bordered is the safest on busy backgrounds; Glassmorphism looks best
over a coloured or image page background. Pick an accent colour with
good contrast against white text (your primary brand colour is usually
right). Keep border radius between 8 and 16 unless your theme is
deliberately sharp-cornered. Enable Step Indicators if your checkout
has several visible sections — they reduce abandonment by signalling
progress.

**Notes on the accent colour:** the value is validated server-side.
Only valid hex values (`#rgb` or `#rrggbb`) are accepted; invalid
values fall back to the default `#1a1a2e`. The colour and radius are
exposed to the page as CSS custom properties
(`--panth-checkout-accent`, `--panth-checkout-radius`), so your theme
or Custom CSS can reuse them.

**Verify on the storefront:** Reload checkout. Buttons and links use
your accent colour, card corners match your radius, and the card
treatment changes when you switch styles (e.g. Bordered shows a thin
outline instead of a shadow). With Step Indicators on, numbered badges
appear above each section. In developer tools, the body class includes
`panth-card-<style>` and the `:root`/wrapper styles show your
`--panth-checkout-accent` value.

---

## 6. Cart & Order Summary

Open the **Cart & Order Summary** group. These settings enrich the
order summary sidebar.

| Setting | Default | What it does |
|---|---|---|
| **Qty Increment Controls** | No | Show +/- buttons next to each item in the order summary so customers can adjust quantities without going back to the cart. |
| **Show SKU** | No | Display the product SKU below each item name in the summary. |
| **Product Link** | No | Make item names in the summary clickable links back to the product page. |

**Recommended:** Enable **Qty Increment Controls** — letting customers
fix a quantity mistake at checkout (instead of navigating back to the
cart) is a proven friction reducer. Enable **Show SKU** for B2B or
parts stores where customers order by SKU. Leave **Product Link** off
for most B2C stores: a link out of checkout is an exit point. Turn it
on for B2B/wholesale buyers who want to double-check specs.

**Qty increment details:** each +/- click updates the cart via AJAX and
the totals refresh immediately. The step size respects the product's
inventory `qty_increments` setting — if a product sells in packs of 6,
clicking **+** adds 6.

**Verify on the storefront:** Add a product (e.g. *example-product*) to
the cart and open checkout. Expand the items in the order summary:
+/- buttons appear next to the quantity, the SKU shows under the name,
and clicking the name opens the product page (when each option is
enabled). Click **+** and watch the order total update without a page
reload.

---

## 7. Newsletter Subscription

Open the **Newsletter Subscription** group.

| Setting | Default | What it does |
|---|---|---|
| **Enable Newsletter Checkbox** | Yes | Show a newsletter subscription checkbox in the checkout sidebar. The two settings below only appear while this is **Yes**. |
| **Checkbox Label** | "Subscribe to our newsletter" | The label text shown next to the checkbox. Translatable per store view. |
| **Checked by Default** | Yes | Whether the checkbox is pre-ticked when the checkout loads. |

**Recommended:** Keep the checkbox enabled — checkout is the highest-
intent moment to grow your list. Write a label that states the benefit,
e.g. *"Email me order tips and exclusive offers"*. **Check your local
regulations before pre-ticking:** under GDPR/PECR (EU/UK) and similar
laws, consent generally must be an affirmative action, so set
**Checked by Default = No** for those regions (use store-view scope
for region-specific stores).

**How it works:**

- The checkbox appears in the order summary sidebar.
- On order placement the preference travels with the payment request
  via the `panth_subscribe_newsletter` extension attribute — it works
  for both guests and logged-in customers.
- If a logged-in customer is already subscribed, the box is pre-ticked
  and no duplicate subscription is created.
- When the checkbox is disabled (or unticked), no subscription is
  created at all.
- A newsletter failure never blocks order placement — errors are
  logged silently.

**Verify on the storefront:** Place a test order as a guest with the
box ticked, then check **Marketing -> Communications -> Newsletter
Subscribers** in the admin — the guest email appears as Subscribed.
Repeat with the box unticked and confirm no subscriber is created.
Change the label, flush cache, and confirm the new text shows in the
sidebar.

---

## 8. Form Styles

Open the **Form Styles** group. These settings control how the address
and contact forms render.

| Setting | Default | What it does |
|---|---|---|
| **Field Mode** | Compact | **Compact** places related fields side by side on one row (e.g. first/last name, city/postcode), making the form visually shorter. **Full Width** places one field per row. |
| **Use Placeholders** | No | Show example/placeholder text inside form fields. |
| **Show Tooltips** | No | Show tooltip icons next to form fields with extra help text. |

**Recommended:** **Compact** for most stores — a shorter-looking form
converts better. Choose **Full Width** if your audience skews older or
your theme uses large font sizes. Enable **Use Placeholders** to make
the form feel lighter, and **Show Tooltips** if customers frequently
ask what a field is for (e.g. VAT number on B2B stores).

**Verify on the storefront:** Reload checkout and look at the shipping
address form. In Compact mode, name fields sit on a single row; in
Full Width every field spans the form. With placeholders on, hint text
appears inside empty fields; with tooltips on, a small help icon
appears next to applicable fields. The body class reflects the mode
(`panth-form-compact` or `panth-form-fullwidth`).

---

## 9. Shipping

Open the **Shipping** group.

| Setting | Default | What it does |
|---|---|---|
| **Default Shipping Method** | (empty) | Pre-select a shipping method by its code, e.g. `flatrate_flatrate` or `tablerate_bestway`. Leave empty for no pre-selection. |
| **Hide Single Method** | No | When only one shipping method is available, hide the radio selector and just show the method name — one less click for the customer. |
| **Sort by Price** | No | Sort available shipping methods by price, lowest first. |

**Recommended:** Set **Default Shipping Method** to your most-used
method so most customers can skip the choice entirely. The code is
`carrier_method` — for built-in flat rate it is `flatrate_flatrate`;
for a custom carrier check the carrier's documentation or the value
attribute of the radio button in your browser's developer tools.
Enable **Hide Single Method** if you only offer one method. Enable
**Sort by Price** when you offer several methods, so the cheapest is
always first.

**Verify on the storefront:** Open checkout with a fresh cart and enter
a shipping address. The configured method arrives pre-selected. If
only one method is available and Hide Single Method is on, no radio
button is shown — just the method name and price. With Sort by Price
on and multiple methods enabled, they list cheapest-first regardless
of carrier sort order.

---

## 10. Payment

Open the **Payment** group.

| Setting | Default | What it does |
|---|---|---|
| **Default Payment Method** | (empty) | Pre-select a payment method by its code, e.g. `checkmo`, `banktransfer`, or `cashondelivery`. Leave empty for no pre-selection. |

**Recommended:** Pre-select your most popular payment method. Common
codes: `checkmo` (check/money order), `banktransfer`,
`cashondelivery`, `free` (zero-total orders). Third-party gateways use
their own codes — check the gateway's documentation.

**Verify on the storefront:** Proceed to the payment step. The
configured method's radio button is already selected and its form
(if any) is expanded. Customers can still pick a different method
freely.

---

## 11. Billing

Open the **Billing** group.

| Setting | Default | What it does |
|---|---|---|
| **Show Billing Title** | Yes | Show or hide the "Billing Address" section title in the payment step. |

**Recommended:** Keep it on. Hide it only if your theme's payment step
already labels the billing block and the extra heading looks
duplicated.

**Verify on the storefront:** On the payment step, untick "My billing
and shipping address are the same" so the billing form shows. With the
setting off, the "Billing Address" heading disappears (the body class
gains `panth-billing-title-hidden`); with it on, the heading is back.

---

## 12. Custom Code

Open the **Custom Code** group.

| Setting | Default | What it does |
|---|---|---|
| **Custom CSS** | (empty) | CSS injected as an inline `<style>` block on the checkout page. Enter raw CSS only — do **not** include `<style>` tags. |
| **Custom JS** | (empty) | JavaScript injected via `require([], function () { ... })` on the checkout page. Enter raw JS only — do **not** include `<script>` tags. |

**Use cases:**

- Override specific checkout styles without touching your theme
- Add tracking pixels or conversion scripts
- Inject A/B testing snippets
- Hide or restyle specific elements

**Example — reuse the extension's CSS variables:**

```css
.opc-block-summary .place-order-button {
    background: var(--panth-checkout-accent);
    border-radius: var(--panth-checkout-radius);
}
```

**Security note:** only administrators with the
`Panth_CheckoutExtended::config` ACL permission can edit these fields.
The CSS and JS are rendered unescaped because they are admin-authored
content — never paste code from an untrusted source.

**Verify on the storefront:** Add an obvious test rule such as
`body { outline: 4px solid red; }`, save, flush cache, and reload
checkout — the red outline appears. For JS, add
`console.log('panth custom js loaded');` and check the browser
console. Remove the test code afterwards.

---

## 13. Recommended starter setup

A sensible configuration for a typical store (e.g. *Acme Store*):

| Group | Setting | Value |
|---|---|---|
| General | Enable | Yes |
| Layout | Columns / Sidebar / Sticky | 2 / Right / Yes |
| Style | Card / Accent / Radius / Steps | Elevated / your brand colour / 12 / Yes |
| Cart & Order Summary | Qty / SKU / Link | Yes / No / No |
| Newsletter | Enable / Checked | Yes / No (EU/UK) or Yes (where lawful) |
| Form Styles | Mode / Placeholders / Tooltips | Compact / Yes / No |
| Shipping | Default / Hide Single / Sort | your top method / Yes / Yes |
| Payment | Default | your top method |
| Billing | Show Title | Yes |
| Custom Code | CSS / JS | empty |

Save, flush cache, then place one full test order as a guest and one as
a logged-in customer to confirm everything end to end.

---

## 14. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Checkout looks unchanged after enabling | Cache not flushed | Run `bin/magento cache:flush` and hard-refresh the browser |
| Layout handle not applied | Module not enabled | Verify with `bin/magento module:status Panth_CheckoutExtended` |
| Config saved but storefront unchanged | Wrong scope | Check you saved at the same website/store-view scope the storefront uses |
| Newsletter checkbox not visible | Checkbox disabled in config | Set Newsletter Subscription -> Enable Newsletter Checkbox to Yes |
| Qty +/- buttons missing | Cart feature disabled | Set Cart & Order Summary -> Qty Increment Controls to Yes |
| Shipping info not auto-saving | JavaScript error | Check the browser console. Redeploy static content: `bin/magento setup:static-content:deploy -f` |
| Accent colour not applying | Invalid hex value | Use the colour picker or enter a valid hex value like `#1a1a2e` |
| Custom CSS/JS not appearing | Cache | Flush full page cache and the browser cache |
| Coupon code not in sidebar | Extension disabled | Enable the extension in General. The discount code moves to the sidebar only while Checkout Extended is active. |
| Default shipping/payment not pre-selected | Wrong method code | Use the full `carrier_method` code (e.g. `flatrate_flatrate`), and confirm the method is enabled and available for the address |

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422

Response time: 1-2 business days for paid licenses.
