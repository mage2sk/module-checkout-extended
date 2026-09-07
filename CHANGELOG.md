# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.1] - 2026-09-07

### Fixed
- **Order note class collision with Panth_AdvancedCart.** The module's order note used the same `panth-order-note-*` class names as the AdvancedCart order note, so a store running both with the module note enabled got two textareas sharing one stylesheet. The module's own component now uses `panth-co-order-note-wrap`, `-label`, `-textarea` and `-counter` (ids `panth-co-order-note` and `panth-co-order-note-counter`). The `panth-order-note-*` rules stay in the stylesheet on purpose: they style the AdvancedCart note when that module renders it inside this checkout.
- **Only one order note renders.** When `Panth_AdvancedCart` is installed and its Order Notes feature is on, `Helper\Data::isOrderNoteEnabled()` returns false, so the module's note is not added to the summary and `checkoutConfig.panthCheckout.orderNote.enabled` is false, even if the module's own Order Note setting is Yes. The AdvancedCart note keeps handling the value. New helper method `isOrderNoteHandledByAdvancedCart()`; unit tests cover both states.
- **Sidebar Place Order waits for pending requests on physical carts too.** When the button commits an unsaved billing form, the billing-address save and the order placement used to leave at the same moment, so the billing save could reach a quote that was already converted and log a 404 in the browser console. The sidebar button now waits until the checkout has no request in flight before it triggers the core Place Order button, for physical carts as it already did for virtual carts.

### Changed
- **Loading indicator while the shipping information saves.** Saving the shipping address and method (the automatic save after the address is complete, a method change, or Ship Here) and the payment information refresh that follows no longer use Magento's full-page loading mask. The mask is scoped to the payment step and the order summary (`.panth-section-loading`), so the address form stays usable while a slow rate provider or a large cart is recalculated. Placing the order still uses the full-page mask. New RequireJS mixin on `Magento_Checkout/js/action/set-shipping-information`; the page-level state lives in `window.panthCheckoutLoader`.
- Documentation: the admin Border Radius mapping is spelled out (cards take the admin value, controls take the value minus 2 with a floor of 4, so the design's 12px cards and 10px controls need the admin value 12, which is the default; a store still on 8 gets 6px controls).

## [1.1.0] - 2026-09-07

### Added
- **Order note (optional, off by default).** New admin group **Order Note**: `panth_checkout_extended/order_note/enabled` (default No), `/label` (default "Order note"), `/placeholder` (default "Anything we should know about your order?") and `/max_length` (default 500). When enabled, a textarea with a live "n/max" counter (`.panth-order-note-wrap`) renders in the order summary between the newsletter checkbox and the discount code. The value travels with the payment request as the new `panth_order_note` extension attribute on `Magento\Quote\Api\Data\PaymentInterface`. Before-plugins on `Magento\Checkout\Api\PaymentInformationManagementInterface` and `GuestPaymentInformationManagementInterface` (`savePaymentInformation` and `savePaymentInformationAndPlaceOrder`) strip tags and control characters, trim, clamp to the maximum length and store the note on the quote as `customer_note` with `customer_note_notify`, so Magento copies it to the order and prints it in the order confirmation email. An observer on `sales_order_place_after` adds "Customer note: ..." to the order status history (not visible to the customer) unless the note is already there. Unit tests cover the sanitiser, both plugins and the observer.
- **Address book picker for logged-in customers.** When at least one saved address exists, an "Address book" button (`.panth-ab-trigger`) is added next to "New Address" in the shipping step. It opens a centred 560px `Magento_Ui` popup (`.panth-ab-modal`, title "Shipping Address Book") over a scrim, with focus trapping, Escape and scrim click to close, and the page scroll locked (`body.panth-ab-open`). The saved addresses are listed as radio cards with the current selection pre-checked; **Save Address** selects the chosen card (no request when it is already the selected one) and **Add New Address** hands off to the standard new-address form. Every string is translatable.
- **Item count in the page heading.** "N Items in Cart" (`.panth-co-count`) is rendered next to the page title and follows the live totals, so it updates with the qty stepper and disappears when the cart is empty.
- **Signed-in line.** Logged-in customers see "Signed in as <email>" with a "Sign out" link (`.panth-co-signedin`) at the top of the shipping step; the logout URL is exposed as `checkoutConfig.panthCheckout.logoutUrl`.
- **Bank transfer note.** The Bank Transfer Payment method title carries the subtitle "Payment details are sent with your order confirmation." (`.panth-co-method-note`).
- **Thumbnail fallback.** Summary thumbnails read their image data from `checkoutConfig.imageData`, refresh it from the customer-data cart section whenever that section changes, and fall back to the catalog placeholder image (`checkoutConfig.panthCheckout.placeholderImage`) when an item has no image.
- **`i18n/en_GB.csv`**, identical to `en_US.csv`, so en_GB store views pick up the module strings.

### Fixed
- **Order summary no longer scrolls the page on load.** On phones and tablets the module expands the "Items in Cart" list when the checkout loads; the core collapsible widget then scrolled the page down to the summary heading. The programmatic open now suppresses that scroll (a manual tap on the heading keeps the core behaviour).
- **Sidebar Place Order button shows a keyboard focus ring.** The button's flat style reset its shadow at a higher specificity than the shared focus rule; a dedicated `:focus-visible` rule restores the 3px accent ring.
- **"Selected shipping method is not available" no longer overflows the summary.** Totals amounts stay on one line, but that message now wraps inside its cell instead of forcing a horizontal scrollbar on narrow screens while rates reload.
- **Enter and Space toggle the summary collapsibles under the Hyva Luma-checkout fallback.** The core collapsible widget reads `jQuery.ui.keyCode`, which that page never loaded; the module now requires the jQuery UI keycode module so the keyboard handler works and no longer throws.
- **Sidebar Place Order now validates an open billing form on physical carts.** With "My billing and shipping address are the same" unticked and the billing form empty or incomplete, the sidebar button used to show the "Placing Your Order" overlay and then silently reset; it now shows the inline field errors inside the payment card and scrolls to the first one, and a completed but not yet saved billing form is committed before the order is placed (same behaviour as the virtual-cart flow).
- **Qty stepper display and stale-quantity compounding.** The +/- controls in the order summary now update the displayed quantity immediately (optimistic), send one request per item at a time, and reconcile from the server totals after every response. Previously a fast series of clicks could compound a stale quantity (each click adding to the value the server had last confirmed rather than the one on screen), the full-screen loader flashed on every click, and the item count in the summary title did not update after a change or removal.
- **Rejected quantities show an inline message.** When the server refuses a quantity (out of stock, below the minimum, not a multiple of the increment) the row reverts to the last confirmed quantity and shows the server message next to the stepper (`role="alert"`) instead of a silent no-op.
- **Native `confirm()` replaced by the styled modal.** Removing the last unit of an item now opens the standard Magento confirm modal (`.panth-confirm-modal`, Cancel / Remove) and returns focus to the button that opened it.
- **Non-ASCII stepper glyphs replaced by inline SVG.** The "-" (U+2212) and "+" characters that could render as a missing-glyph box in some font stacks are now `currentColor` SVG icons with `aria-hidden`.
- **Logo could be painted over by a themed header.** The logo was absolutely positioned inside `.header.content`; a theme that styled the header could cover or misplace it. The logo block is now moved into `.panth-checkout-header` and laid out with a three-column grid (back link | logo | secure note), so it sits in normal flow.
- **Shipping method cells now use `box-sizing: border-box`**, so the row height and the radio column width are predictable when a theme changes the padding.
- **Discount toggle no longer forces uppercase** and letter-spacing; it renders as a plain 13px action link.
- **Summary thumbnails rendered `src="null"` after cart changes.** After a qty change or a removal the thumbnail could point at the literal string "null". The image map is now refreshed from the customer-data cart section and the placeholder image is used for items without an image (`img.panth-co-thumb-missing`); "null" is never emitted.
- **Tax row expander without details.** The tax summary row no longer shows an expand chevron when there are no tax detail rows to expand (`tr.totals-tax-summary.panth-no-details`).
- **Untitled and duplicate total segments.** Total segments with an empty title are skipped instead of rendering an empty row, and two segments with the same value produce one `console.warn` so the cause can be traced.

### Changed
- **CSS custom-property layer.** Every colour, radius, size and font size the stylesheet uses is now a `--panth-co-*` token declared with its default on `.panth-checkout-extended` (listed in the README "Theming with CSS Variables" section); every rule reads those tokens only. The admin accent colour and border radius still win through the dynamic styles block (`--panth-checkout-accent`, `-accent-hover`, `-radius`, `-radius-sm`), and the 1.0.10 inputs `--panth-checkout-row-hover`, `-row-selected` and `--panth-checkout-fs-*` keep working as the defaults of the matching tokens.
- **Control radius derived from the admin Border Radius is now `max(4, radius - 2)`** instead of `max(4, radius - 4)`, so the default radius 12 renders inputs, buttons and rows at 10px (previously 8px). Stores that want the old value set `--panth-co-ctl-radius: 8px` on `body.panth-checkout-extended` or lower the admin radius.
- **`!important` reduced from 721 declarations to 26.** Each remaining one beats either an inline style written by a Magento widget (the collapsible's `display: none`, the core thumbnail template's inline size) or a third-party stylesheet layer that itself uses `!important`; none of them is needed to beat a plain theme rule.
- **Selector specificity kept low.** Module selectors are `body.panth-checkout-extended` plus a few further parts; the address form rules are repeated once under `.modal-popup` because Luma renders that form in a modal outside `#checkout`. A theme rule of the same shape declared after the module stylesheet wins without `!important`.
- **One design geometry.** Page width 1440px (`--panth-co-page-max`) with 40px side insets (`--panth-co-page-pad`: 16px at 1100px and below, 12px at 480px and below), a 48px header (`--panth-co-header-h`), 46px inputs, selects, discount field and Apply button (`--panth-co-ctl-h`), 46px shipping method rows, a 380px summary column (`--panth-co-sidebar-w`), 32px column gap (`--panth-co-col-gap`), 12px cards and 10px controls at the default admin radius. The old fixed values (1400px container, 44px inputs, 1280px header cap) are gone.
- **Header rebuilt as a grid**: `.panth-checkout-header` with `__back`, `__logo` and `__secure` cells, 48px tall (`--panth-co-header-h`), page-wide (`--panth-co-page-max`, `--panth-co-page-pad`) with a 30px logo (26px at 480px and below). The hidden progress nav was removed.
- **Shipping rows and payment methods.** Shipping method rows are plain 46px rows separated by hairlines; the selected row gets an accent border and the `--panth-co-row-selected` tint (the left accent bar and the inset shadows of 1.0.10 are gone). Each payment method is its own card; the active one carries the accent border, a tinted title and a hairline under the title.
- **Custom radios and checkboxes.** Radio buttons and checkboxes in the checkout are drawn by the stylesheet (`appearance: none`, `--panth-co-ring` border, accent fill when checked) instead of relying on `accent-color`, so they look the same in every browser and follow the admin accent colour.
- **Totals order.** The grand total excluding tax is rendered above the grand total including tax; the totals rows share one gap, and only the grand total carries a hairline above it.
- **Centred address popups.** The new-address form, the remove-item confirm and the address book open as centred 560px popups over a scrim (`--panth-co-scrim`) on every viewport instead of Luma's side slide-in; on phones they span the width minus 16px on each side.
- **"Apply Discount" shortened to "Apply".** The discount button reads "Apply" through `i18n` (`"Apply Discount","Apply"`), and the discount field and the button share the 46px control height.
- Qty stepper and cart-item requests no longer use the full-screen loader; `body.panth-qty-saving` and `.panth-item-qty-stepper.panth-qty-busy` are set while a request is in flight.
- "N Items in Cart" in the summary title now follows the live totals.
- i18n: `i18n/en_US.csv` gained `Apply Discount` (translated to `Apply`), `Cancel`, `Remove item`, `The requested quantity is not available.`, `each`, and the order note, address book, heading count, signed-in line and bank transfer strings; `i18n/en_GB.csv` added as an identical copy.
- Documentation: README compatibility range extended to Magento 2.4.9, `--panth-co-*` theming section with the full token table, order note and address book documented; USER_GUIDE covers the stepper behaviour, the header layout, the order note settings and the address book.

### Upgrade notes
Themes that previously had to out-specify this module can simplify their overrides. Rules that used `!important` to beat the module can drop it: a `body.panth-checkout-extended ...` selector of the same specificity placed after the module stylesheet now wins, and for colours, radii, spacing, type scale and control sizes a token override on `body.panth-checkout-extended` is preferred over restyling individual elements. Overrides written against the 1.0.10 names (`--panth-checkout-accent`, `-accent-hover`, `-radius`, `-radius-sm`, `-row-hover`, `-row-selected` and `--panth-checkout-fs-*`) keep working because they are the defaults of the matching `--panth-co-*` tokens. The logo now lives inside `.panth-checkout-header`; theme selectors that targeted the absolutely positioned logo under `.header.content` (or reset `.header.content` positioning for the checkout) can be removed. The qty stepper markup changed from text glyphs to inline SVG inside `.panth-qty-btn`; CSS that set `content` or `font-size` on the old glyphs can be dropped, the button `color` now drives the icon. The selected shipping row no longer has a left accent bar or inset shadows, so theme rules that hid or restyled them can go too.

The order note adds a configuration group (`panth_checkout_extended/order_note/*`, disabled by default), a payment extension attribute (`panth_order_note`), two before-plugins on the payment-information services and an observer on `sales_order_place_after`. All of it is additive and stays inactive until the group is enabled; there are no database schema changes. Run `bin/magento setup:upgrade`, `setup:di:compile` and `setup:static-content:deploy` as for any module update. A store that prefers the long "Apply Discount" label adds `"Apply Discount","Apply Discount"` to its theme or `app/i18n` translation file, which takes precedence over the module CSV. The module version is 1.1.0.

---

## [1.0.10] - 2026-08-19

### Fixed
- **Order Summary shipping amount now updates immediately when the customer picks a different shipping method.** Previously the shipping label updated but the amount could stay at the old method's value (e.g. stuck at 0.00 after choosing a paid rate). Two causes: the save request completed by rebuilding its dedup fingerprint from the live quote observables, so a method chosen while a save was in flight was recorded as already saved and every later save was skipped; and the radio click ran through a 500 ms delay plus a 1200 ms debounce. A method click now bypasses the fingerprint dedup, fires the save on the next tick, and a save requested while one is in flight is queued and re-run instead of dropped.
- Every early exit in the shipping auto-save now logs a `console.debug` reason (`[panth-checkout] save skipped/queued: ...`) so silent no-saves are diagnosable from the browser console.

### Changed
- **Selected shipping-method row is clearly highlighted**: stronger background wash, accent top/bottom edges, 3px left accent bar, and semi-bold text. Themes can rebrand via new CSS custom properties `--panth-checkout-row-hover` and `--panth-checkout-row-selected` (both default to tints of `--panth-checkout-accent`).
- The Order Summary shipping and grand-total amounts dim while a shipping save is in flight (`body.panth-shipping-saving`), so the pending update is visible.
- **Checkout typography normalised to a single scale** (26 title / 18 section / 16 total / 15 body+buttons / 14 label / 13 action / 12 hint), rebindable via `--panth-checkout-fs-*` custom properties on `body.panth-checkout-extended`. Nothing renders below 12px any more (WCAG 1.4.4): field notes, tooltips, error text, item options and qty labels were raised from 9-11px to 12px.

---

## [1.0.9]

### Changed
- Replaced typographic characters (em dashes, curly quotes, ellipsis) with plain ASCII punctuation. No functional changes.

---

## [1.0.8] - 2026-07-07

### Changed
- Code cleanup: removed redundant inline comments and docblocks from the PHP source. No functional changes.

---

## [1.0.7] - 2026-06-18

### Changed
- README rewritten to match the standard Panth Infotech template: gold-template section order, Quick Answer block, gold-template hire/agency promo, full configuration table sourced from system.xml, and FAQ updated with direct answers.
- Canonical and product page links updated to kishansavaliya.com/magento-2-checkout-extended.html.
- Removed link to commercemarketplace.adobe.com; marketplace reference now links to the live product page.

---

## [1.0.6] - 2026-06-12

### Added
- Previously missing admin configuration groups so every documented
  option is now actually configurable: **Layout** (columns, sidebar
  position, sticky sidebar), **Style** (card style, accent colour,
  border radius, step indicators), **Cart & Order Summary** (qty
  increment controls, show SKU, product link), **Form Styles** (field
  mode, placeholders, tooltips), **Shipping** (default method, hide
  single method, sort by price), **Payment** (default method),
  **Billing** (show title), and **Custom Code** (custom CSS/JS)

### Changed
- Every configuration option is now wired through to the frontend:
  - Multi-column checkout layout (1/2/3 columns), sidebar position
    (left/right), and sticky sidebar
  - Card styles (Elevated, Bordered, Flat, Glassmorphism) plus accent
    colour and border radius exposed as CSS variables
    (`--panth-checkout-accent`, `--panth-checkout-radius`)
  - Step indicator badges on checkout sections
  - Qty +/- controls, SKU display, and product links in the order
    summary
  - Form field mode (Compact/Full Width), placeholders, and tooltips
  - Shipping method pre-selection, sort-by-price, and hide-single-method
  - Payment method pre-selection
  - Billing address title visibility toggle
  - Admin-defined custom CSS and JS injection at checkout
- Modern responsive redesign of the checkout - multi-column on desktop,
  collapsing cleanly to a single column on mobile

### Fixed
- Order summary item template override now loads correctly (corrected
  RequireJS map path)
- Load error for the relocated discount/coupon template in the sidebar
- Newsletter double-subscribe: no subscription is created when the
  checkbox is disabled, and logged-in customers' existing subscriptions
  are linked instead of duplicated

---

## [1.0.0] - Initial release

### Added - layout
- Configurable 1/2/3 column checkout layout
- Sidebar position (left/right)
- Sticky sidebar option
- Body class injection via layout handle

### Added - checkout UX
- Auto-save shipping information (address + method) as the customer
  fills in the form, with debounced AJAX and fingerprint deduplication
- Real-time billing address sync when "same as shipping" is checked
- Sidebar place-order button always visible in the order summary
- Coupon/discount code moved from payment step to sidebar summary
- Auto-expand cart items in order summary
- Auto-expand discount code input

### Added - cart features
- Qty increment/decrement buttons in order summary with stock-aware
  qty_increments from CatalogInventory
- Product SKU display in order summary
- Product name links to product page

### Added - newsletter subscription
- Checkbox in checkout sidebar with configurable label and default state
- Guest subscriber plugin on GuestPaymentInformationManagement
- Customer subscriber plugin on PaymentInformationManagement
- Payment extension attribute `panth_subscribe_newsletter` for clean
  API transport
- Pre-checks the box if logged-in customer is already subscribed

### Added - styling
- Card styles: Elevated (Shadow), Bordered, Flat, Glassmorphism
- Admin colour picker for accent colour
- Border radius control
- Step indicators toggle
- Field modes: Compact (multi-field rows) / Full Width
- Placeholder and tooltip toggles
- Billing title visibility toggle
- CSS custom properties for theming (--panth-checkout-accent,
  --panth-checkout-radius)

### Added - custom code
- Custom CSS textarea injected as inline style at checkout
- Custom JS textarea injected via RequireJS at checkout

### Added - admin
- Full admin configuration under Stores -> Configuration -> Panth
  Extensions -> Checkout Extended
- ACL resource Panth_CheckoutExtended::config for granular permissions
- Colour picker field renderer for accent colour

### Quality
- Constructor injection only - zero ObjectManager usage
- All PHP files lint clean
- MEQP (Magento2 coding standard) passes with zero errors at
  severity 10
- Composer validate passes

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 - 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
