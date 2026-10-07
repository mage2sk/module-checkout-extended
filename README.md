# Magento 2 Checkout Extended

Checkout Extended replaces the accordion layout of the standard Magento 2 checkout with a one, two or three column page and moves the order summary into a sidebar that carries the cart items, totals, an optional newsletter checkbox, an optional order note, the discount code form and a second Place Order button. It also adds a checkout header with a "Back to Cart" link and the store logo, card, colour and radius styling controlled from the admin panel, quantity controls in the order summary, automatic saving of the shipping step, shipping and payment method pre-selection, a saved-address picker for logged-in customers, and admin textareas for extra CSS and JavaScript on the checkout page only. Every option is a store configuration setting.

The module targets the standard Magento (Knockout.js) checkout as shipped with the Luma theme: all frontend code consists of RequireJS mixins, Knockout components and templates that extend `Magento_Checkout`. The module contains no Hyva templates or Tailwind styles and does not apply to Hyva Checkout.

Product page: [kishansavaliya.com/magento-2-checkout-extended.html](https://kishansavaliya.com/magento-2-checkout-extended.html)

## Features

- 1, 2 or 3 column checkout layout with the order summary sidebar on the left or right, optionally sticky while scrolling.
- Checkout header with a "Back to Cart" link, the store logo and a "Secure checkout" label.
- Card styles "Elevated (Shadow)", "Bordered", "Flat (No Border)" and "Glassmorphism"; accent colour and accent hover colour pickers; border radius in pixels; optional numbered step indicators.
- Order summary sidebar reordered to: cart items, totals, newsletter checkbox, order note, discount code form (moved out of the payment step), Place Order button.
- Sidebar Place Order button that validates email, shipping address, shipping method, billing address and payment method, then triggers the Place Order button of the selected payment method.
- Newsletter checkbox for guests and logged-in customers, with configurable label and default state; the subscription is created when the order is placed.
- Optional order note textarea with a live character counter; the note is sanitised on the server, saved as the order's customer note and added to the order comment history.
- Plus and minus quantity buttons in the order summary that respect the stock item "qty_increments" value, update the cart through the REST cart API and refresh totals in place; decreasing below the step asks for confirmation and removes the item.
- Optional product SKU and product page link on each order summary item.
- Automatic saving of the shipping address and method while the customer fills in the form (debounced, deduplicated by a fingerprint of the address and method), so the payment section is ready without pressing Next.
- Billing address kept in sync with the shipping address while "same as shipping" is checked.
- Default shipping method by "carrier_method" code, hiding of the shipping method selector when only one rate is available, and sorting of rates by price.
- Default payment method by code, applied when no method has been chosen yet.
- "Address book" popup in the shipping step for logged-in customers with saved addresses.
- Compact or full-width form fields, optional placeholders copied from the field labels, optional tooltips, optional hiding of the "Billing Address" title.
- Item count in the page heading, "Signed in as ..." line with a "Sign out" link for logged-in customers, and a note under the Bank Transfer payment method title.
- Custom CSS and custom JavaScript injected on the checkout page from the admin configuration.
- All settings configurable at default, website and store view scope; ACL resource for the configuration section; translation files for en_US and en_GB.

## Compatibility

| Platform | Versions |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 (as published on the product page) |
| Adobe Commerce | 2.4.4 to 2.4.8 (as published on the product page) |
| PHP | 8.1, 8.2, 8.3, 8.4 (composer.json: `~8.1.0||~8.2.0||~8.3.0||~8.4.0`) |
| Themes | Luma and other themes that use the standard Magento Knockout checkout. No Hyva templates are shipped; Hyva Checkout is not supported. |

Composer constraints on Magento packages: `magento/framework` ^103.0, `magento/module-checkout` ^100.4, `magento/module-config` ^101.2, `magento/module-store` ^101.1, `magento/module-customer` ^103.0, `magento/module-newsletter` ^100.4, `magento/module-quote` ^101.2, `magento/module-payment` ^100.4, `magento/module-catalog-inventory` ^100.4, `magento/module-catalog` ^104.0, `magento/module-sales` ^103.0, `magento/module-backend` ^102.0.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8.
- PHP 8.1, 8.2, 8.3 or 8.4.
- `mage2kishan/module-core` ^1.0 (module `Panth_Core`), installed automatically by Composer. It provides the "Panth Extensions" configuration tab and admin menu that this module attaches to.
- The Magento modules listed under Compatibility. `Magento_Sales` is used by the order note observer.
- Optional: `Panth_AdvancedCart`. When it is installed with its own order notes feature enabled, this module hides its order note field and lets AdvancedCart handle the note.

## Installation

```bash
composer require mage2kishan/module-checkout-extended
bin/magento module:enable Panth_Core Panth_CheckoutExtended
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

`setup:di:compile` is only needed in production mode. `setup:static-content:deploy` is needed because the module ships CSS, JavaScript and Knockout templates under `view/frontend/web`.

Check that the module is enabled:

```bash
bin/magento module:status Panth_CheckoutExtended
```

## Configuration

Admin path: Stores > Configuration > Panth Extensions > Checkout Extended. The same section is reachable from the admin menu under Panth Extensions > Checkout Extended > Configuration. All settings can be set at default, website and store view scope. Configuration paths start with `panth_checkout_extended/`.

### General

| Setting | Default | What it does |
|---|---|---|
| Enable Checkout Extended | Yes | Master switch. When set to No, no layout handle, body class, CSS, JavaScript or plugin behaviour of this module is applied. Path: `general/enabled` |

### Layout

| Setting | Default | What it does |
|---|---|---|
| Columns | 3 Columns (Shipping \| Payment \| Summary) | Number of checkout columns: "1 Column (Stacked)", "2 Columns (Content + Sidebar)" or "3 Columns (Shipping \| Payment \| Summary)". Path: `layout/columns` |
| Sidebar Position | Right | Places the order summary sidebar on the "Left" or "Right". Path: `layout/sidebar_position` |
| Sticky Sidebar | No | Keeps the order summary visible while the customer scrolls. Path: `layout/sidebar_sticky` |

### Style

| Setting | Default | What it does |
|---|---|---|
| Card Style | Elevated (Shadow) | Visual treatment for the checkout cards: "Elevated (Shadow)", "Bordered", "Flat (No Border)" or "Glassmorphism". Path: `style/card_style` |
| Accent Color | (empty) | Colour picker for buttons, links and highlights. Written to the `--panth-checkout-accent` CSS variable. Empty, invalid or the previous default `#1a1a2e` follow the storefront theme primary colour (`--color-primary-fill`, fallback `#0F766E`). Path: `style/accent_color` |
| Accent Hover Color | (empty) | Colour picker for primary buttons on hover. When empty, a shade 15 percent darker than the accent colour is used. Path: `style/accent_hover_color` |
| Border Radius (px) | 12 | Corner radius for cards and form elements. Written to `--panth-checkout-radius`; `--panth-checkout-radius-sm` is set to the radius minus 2 (minimum 4). Path: `style/border_radius` |
| Step Indicators | No | Shows numbered badges above each checkout section. Path: `style/step_indicators` |

### Cart & Order Summary

| Setting | Default | What it does |
|---|---|---|
| Qty Increment Controls | No | Shows plus and minus quantity buttons on each order summary item. Path: `cart/qty_increment_enabled` |
| Show SKU | No | Shows the product SKU below each item name. Path: `cart/product_sku_enabled` |
| Product Link | No | Links each item name to its product page. Path: `cart/product_link_enabled` |

### Newsletter Subscription

| Setting | Default | What it does |
|---|---|---|
| Enable Newsletter Checkbox | Yes | Shows the newsletter checkbox in the order summary. Path: `newsletter/enabled` |
| Checkbox Label | Subscribe to our newsletter | Label next to the checkbox. Shown only while the checkbox is enabled. Path: `newsletter/field_label` |
| Checked by Default | No | Whether the checkbox starts checked. Shown only while the checkbox is enabled. Keep it off where marketing consent must be an active opt-in (for example under the GDPR). Path: `newsletter/default_checked` |

### Order Note

| Setting | Default | What it does |
|---|---|---|
| Enable Order Note | No | Shows an optional note textarea in the order summary. Path: `order_note/enabled` |
| Field Label | Order note | Label above the textarea. Shown only while the note is enabled. Path: `order_note/label` |
| Placeholder | Anything we should know about your order? | Hint text inside the empty textarea. Shown only while the note is enabled. Path: `order_note/placeholder` |
| Maximum Length | 500 | Maximum number of characters, enforced in the browser and on the server. Shown only while the note is enabled. Path: `order_note/max_length` |

### Form Styles

| Setting | Default | What it does |
|---|---|---|
| Field Mode | Compact (Multiple Fields Per Row) | "Compact (Multiple Fields Per Row)" or "Full Width (One Field Per Row)". Path: `form_styles/field_mode` |
| Use Placeholders | No | Copies each field label into the field placeholder for the shipping address, billing address and payment method forms. Path: `form_styles/use_placeholders` |
| Show Tooltips | No | Shows tooltip icons with help text next to fields. Path: `form_styles/show_tooltips` |

### Shipping

| Setting | Default | What it does |
|---|---|---|
| Default Shipping Method | (empty) | Pre-selects a shipping method by "carrier_method" code, for example `flatrate_flatrate`, when the customer has not chosen one yet. Path: `shipping/default_method` |
| Hide Single Method | No | Hides the shipping method selector and selects the only rate automatically when exactly one rate is available. Path: `shipping/hide_single_method` |
| Sort by Price | No | Sorts shipping rates from lowest to highest price (price including tax when available). Path: `shipping/sort_by_price` |

### Payment

| Setting | Default | What it does |
|---|---|---|
| Default Payment Method | (empty) | Pre-selects a payment method by code, for example `checkmo`, when no method has been chosen yet and the method is available. Path: `payment/default_method` |

### Billing

| Setting | Default | What it does |
|---|---|---|
| Show Billing Title | Yes | Shows or hides the "Billing Address" section title. Path: `billing/show_title` |

### Custom Code

| Setting | Default | What it does |
|---|---|---|
| Custom CSS | (empty) | CSS rules (without style tags) printed in a style element on the checkout page. Path: `custom_code/custom_css` |
| Custom JS | (empty) | JavaScript statements (without script tags) run inside a RequireJS callback wrapped in try/catch on the checkout page. Path: `custom_code/custom_js` |

Default behaviour after installation: the module is enabled with the three column layout, right sidebar, "Elevated (Shadow)" cards, accent colour following the theme primary colour, 12 px card radius (8 px form controls) and the newsletter checkbox enabled and checked. Quantity controls, SKU, product links, order note, step indicators, sticky sidebar, placeholders, tooltips and all shipping and payment pre-selection are off or empty.

## Usage

All behaviour below applies to the `checkout_index_index` page on the standard Magento checkout and only while "Enable Checkout Extended" is Yes.

### Layout and styling

- An observer on `layout_load_before` adds the `panth_checkout_extended_active` layout handle and a set of body classes: `panth-checkout-extended`, `panth-checkout-1col` / `panth-checkout-2col` / `panth-checkout-3col`, `panth-sidebar-left` / `panth-sidebar-right`, `panth-card-elevated` / `panth-card-bordered` / `panth-card-flat` / `panth-card-glass`, `panth-sidebar-sticky`, `panth-step-indicators`, `panth-form-compact` / `panth-form-full`, `panth-form-placeholders`, `panth-form-tooltips` and `panth-billing-title-hidden`. The stylesheet `Panth_CheckoutExtended::css/checkout-extended.css` is added by the same layout handle, so it is not loaded while the module is disabled, and uses these classes; every JavaScript mixin checks for `panth-checkout-extended` on the body and does nothing on other pages.
- The active handle moves the store logo into the module's checkout header, which shows a "Back to Cart" link, the logo and the text "Secure checkout".
- The `DynamicStyles` block prints the CSS variables `--panth-checkout-accent` and `--panth-checkout-accent-hover` (only when a colour is set), `--panth-checkout-radius` and `--panth-checkout-radius-sm` before the closing body tag, followed by the Custom CSS and Custom JS values.
- The checkout header logo comes from `ViewModel\CheckoutLogo`: an uploaded design logo is used as is; otherwise, when the checkout is rendered with a fallback theme (Hyva stores render checkout with Luma), the `images/logo.svg` of the store's configured theme is used. The alt text is the logo alt or the store name.
- All checkout steps are kept visible at the same time (step navigator mixin), hash navigation is disabled and "Next" scrolls to the target section.

### Order summary sidebar

The layout processor sets the order of the summary children to: cart items (0), totals (10), newsletter checkbox (20), order note (30), discount code form (40, moved from the payment step), sidebar Place Order button (50). The cart items block and the discount code block are expanded on page load.

### Sidebar Place Order button

The button is disabled until a shipping method (or a virtual quote) and a payment method are selected. On click it validates the email, the shipping address form and method, the billing address of the active payment method and the payment method, scrolls to the first visible error if any, and otherwise clicks the Place Order button of the active payment method once no AJAX requests are pending. An overlay with "Placing Your Order" is shown until the order is placed or an error message appears (with an 8 second safety reset). After a successful payment-information call the customer-data cart section is invalidated and reloaded.

### Newsletter subscription

- The checkbox is rendered in the summary while enabled. It is hidden for logged-in customers who are already subscribed (the module adds `panthNewsletterSubscribed` to `window.checkoutConfig`) and for guests who entered an email that belongs to an existing account (the checkout shows the password field).
- The checked state is sent as the `panth_subscribe_newsletter` extension attribute of the payment payload by the `place-order` and `set-payment-information-extended` mixins.
- After plugins on `Magento\Checkout\Api\PaymentInformationManagementInterface::savePaymentInformationAndPlaceOrder` (logged-in: `subscribeCustomer` by customer ID, otherwise `subscribe` by the quote email) and `Magento\Checkout\Api\GuestPaymentInformationManagementInterface::savePaymentInformationAndPlaceOrder` (guest: `subscribe` by the submitted email) create the subscription through `Magento\Newsletter\Model\SubscriptionManagerInterface` using the quote's store. Nothing is subscribed while the module is disabled under General. Exceptions are logged and do not block order placement.
- Admins see the resulting subscribers under Marketing > Newsletter Subscribers; Magento's own subscription confirmation emails apply.

### Order note

- While enabled, a textarea with the configured label and placeholder and a counter such as "12/500" appears in the summary. The `maxlength` attribute and a Knockout subscription cut the text at the maximum length in the browser.
- The note is sent as the `panth_order_note` extension attribute of the payment payload. Before plugins on `savePaymentInformationAndPlaceOrder` of both payment-information services strip HTML tags and control characters, trim the text, cut it to the configured maximum, and save it to the quote as `customer_note` with `customer_note_notify` set when the note is not empty. Magento copies both values to the order.
- An observer on `sales_order_place_after` adds the comment "Customer note: ..." to the order status history (not visible to the customer, no notification) unless the same text is already in the history.
- Admins see the note in the order view as the customer note and under Comments History. Magento's standard order confirmation email prints the customer note when `customer_note_notify` is set.
- When `Panth_AdvancedCart` is enabled and both `panth_advancedcart/general/enabled` and `panth_advancedcart/order_notes/enabled` are set, this module's note is disabled and nothing is rendered or stored.

### Quantity controls, SKU and product link

- A plugin on `Magento\Quote\Model\Quote\Item::toArray` adds `qty_increments` (from the stock item, or 1), `sku` and `product_url` to the quote item data available to the checkout.
- With "Qty Increment Controls" enabled, each item shows minus and plus buttons stepping by `qty_increments`. Changes are sent with PUT to `/V1/carts/mine/items/:itemId` (logged-in) or `/V1/guest-carts/:cartId/items/:itemId` (guest); totals and payment information are reloaded afterwards, and server error messages (for example insufficient stock) are shown under the item while the quantity is reverted. Decreasing to zero or below the step opens a "Remove item" confirmation and sends DELETE to the same endpoint; the page reloads when the last item is removed.
- With "Show SKU" or "Product Link" enabled, the SKU is printed below the name and the name links to the product page.
- Item thumbnails are refreshed from the customer-data cart section after changes and fall back to the catalog placeholder image.

### Shipping step

- The shipping form is watched; the address is read into the quote on input and, once a shipping method is selected, saved through `set-shipping-information` after a 1.2 second pause. A fingerprint of the address and method prevents repeated identical saves. Selecting a shipping method radio, a saved address or "Ship here" also triggers a save. While a save is in progress the `panth-shipping-saving` body class is set and the loader is scoped to the payment and summary sections instead of the full page.
- While the "same as shipping" checkbox is checked, the billing address is updated with each shipping address change.
- "Default Shipping Method" and "Hide Single Method" act when shipping rates arrive and no rate has been selected. "Hide Single Method" also toggles the `panth-hide-single-shipping` body class. "Sort by Price" reorders the rates before they reach the shipping method list.
- Logged-in customers with at least one saved address get an "Address book" button next to "New Address". It opens a popup listing the saved addresses with the current one selected; "Save Address" selects the chosen address and "Add New Address" opens the standard new address form.
- Logged-in customers see "Signed in as <email>" with a "Sign out" link at the top of the shipping step.

### Payment step

- "Default Payment Method" is applied through a `checkout-data-resolver` mixin when no method is stored in checkout data and the configured method is available.
- If `window.checkoutConfig.paymentMethods` is empty, a plugin on `Magento\Checkout\Model\DefaultConfigProvider` fills it from the active payment method list.
- Payment information is not requested from the server until a shipping address with country and first name exists and the shipping information has been saved once; until then only totals are loaded.
- The "Bank Transfer Payment" method title gets the note "Payment details are sent with your order confirmation."

## Developer Notes

- Module name: `Panth_CheckoutExtended`. Composer package: `mage2kishan/module-checkout-extended`, version 1.1.5, type `magento2-module`. PHP namespace: `Panth\CheckoutExtended`. Load sequence: after `Panth_Core` and `Magento_Checkout`.
- Configuration access: `Panth\CheckoutExtended\Helper\Data` (one getter per setting, plus `getCheckoutBodyClass()` and `isOrderNoteHandledByAdvancedCart()`).
- Frontend block: `Panth\CheckoutExtended\Block\DynamicStyles` (templates `checkout_header.phtml`, `dynamic_styles.phtml`, `checkout_init.phtml`), declared in `view/frontend/layout/checkout_index_index.xml` with `ifconfig="panth_checkout_extended/general/enabled"`.
- Layout processor: `Panth\CheckoutExtended\Plugin\CheckoutLayoutProcessor`, registered as a `layoutProcessors` argument of `Magento\Checkout\Block\Onepage` in `etc/frontend/di.xml`.
- Plugins (`etc/frontend/di.xml`): `Plugin\Cart\ConfigProvider` and `Plugin\Checkout\PaymentMethodsConfigProvider` (after `getConfig` on `Magento\Checkout\Model\DefaultConfigProvider`, adding `panthCheckout`, `panthNewsletterSubscribed` and `paymentMethods`), `Plugin\Cart\QuoteItemPlugin` (after `toArray` on `Magento\Quote\Model\Quote\Item`).
- Plugins (`etc/di.xml`): `Plugin\OrderNote\SaveOrderNote` and `Plugin\Newsletter\CustomerSubscriber` on `Magento\Checkout\Api\PaymentInformationManagementInterface`; `Plugin\OrderNote\SaveGuestOrderNote` and `Plugin\Newsletter\GuestSubscriber` on `Magento\Checkout\Api\GuestPaymentInformationManagementInterface`.
- Observers: `Observer\AddBodyClass` on `layout_load_before` (frontend area); `Observer\AddOrderNoteHistory` on `sales_order_place_after` (global area).
- Order note sanitiser: `Panth\CheckoutExtended\Model\OrderNote\Sanitizer` (`sanitize()`, `extract()`). Colour helper: `Panth\CheckoutExtended\Model\Color\Shade` (`normalize()`, `darken()`, `luminance()`).
- Admin form field: `Panth\CheckoutExtended\Block\Adminhtml\System\Config\ColorPicker` turns a text field into an HTML colour input.
- Extension attributes (`etc/extension_attributes.xml`) on `Magento\Quote\Api\Data\PaymentInterface`: `panth_subscribe_newsletter` (boolean), `panth_order_note` (string).
- Customer sections (`etc/frontend/sections.xml`): the `cart` and `last-ordered-items` sections are invalidated after `rest/*/V1/guest-carts/*/payment-information`.
- RequireJS (`view/frontend/requirejs-config.js`): mixins for `step-navigator`, `view/shipping`, `full-screen-loader`, `resource-url-manager`, `summary/item/details`, `summary/item/details/thumbnail`, `summary/cart-items`, `action/place-order`, `summary/abstract-total`, `summary/shipping` (core and Magento_Tax), `action/set-shipping-information`, `action/get-payment-information`, `action/set-payment-information-extended`, `model/shipping-service`, `Magento_SalesRule/js/view/payment/discount-messages` and `model/checkout-data-resolver`; while the module is enabled the `summary/item/details` mixin switches the item template to the module's own `Panth_CheckoutExtended/summary/item/details`, so the core template is used when the module is switched off.
- Knockout components: `js/view/newsletter`, `js/view/order-note`, `js/view/sidebar-place-order`; standalone modules `js/view/address-book`, `js/view/page-extras`, `js/view/modal-watchdog`; actions `js/action/update-cart-item` and `js/action/remove-cart-item`.
- Runtime data on the page: `window.checkoutConfig.panthCheckout` (cart, shipping, payment and orderNote settings, placeholder image, logout URL), `window.panthCheckoutNewsletter`, `window.panthShippingInfoSaved()`, `window.panthCheckoutLoader`.
- ACL resource: `Panth_CheckoutExtended::config` ("Panth Checkout Extended") under `Magento_Config::config`.
- Admin menu: `Panth_CheckoutExtended::group` ("Checkout Extended") and `Panth_CheckoutExtended::settings` ("Configuration") under `Panth_Core::panth_extensions`.
- Database: the module has no `db_schema.xml` and creates no tables or columns. It writes to the existing `quote.customer_note` and `quote.customer_note_notify` columns and to the order status history.
- Translations: `i18n/en_US.csv` and `i18n/en_GB.csv`. Unit tests under `Test/Unit`.

## Uninstallation

```bash
bin/magento module:disable Panth_CheckoutExtended
composer remove mage2kishan/module-checkout-extended
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

No database tables or columns are removed because the module creates none. Configuration values under `panth_checkout_extended/*` remain in `core_config_data`, customer notes and "Customer note: ..." comments on existing orders remain, and newsletter subscriptions created at checkout remain.

## Support

- Product page: [kishansavaliya.com/magento-2-checkout-extended.html](https://kishansavaliya.com/magento-2-checkout-extended.html)
- Contact: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- Issues: [GitHub issues](https://github.com/mage2sk/module-checkout-extended/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) walks a store administrator through installation, verifying that the extension is active, every configuration group (General, Layout, Style, Cart & Order Summary, Newsletter Subscription, Order Note, Form Styles, Shipping, Payment, Billing, Custom Code), a recommended starter setup and troubleshooting.

## License

Commercial software license. See [LICENSE.txt](LICENSE.txt) in this repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [kishansavaliya.com/magento-extensions.html](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [mage2sk/module-checkout-extended](https://github.com/mage2sk/module-checkout-extended)
- Packagist: [mage2kishan/module-checkout-extended](https://packagist.org/packages/mage2kishan/module-checkout-extended)
