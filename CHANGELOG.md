# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] — Initial release

### Added — layout
- Configurable 1/2/3 column checkout layout
- Sidebar position (left/right)
- Sticky sidebar option
- Body class injection via layout handle

### Added — checkout UX
- Auto-save shipping information (address + method) as the customer
  fills in the form, with debounced AJAX and fingerprint deduplication
- Real-time billing address sync when "same as shipping" is checked
- Sidebar place-order button always visible in the order summary
- Coupon/discount code moved from payment step to sidebar summary
- Auto-expand cart items in order summary
- Auto-expand discount code input

### Added — cart features
- Qty increment/decrement buttons in order summary with stock-aware
  qty_increments from CatalogInventory
- Product SKU display in order summary
- Product name links to product page

### Added — newsletter subscription
- Checkbox in checkout sidebar with configurable label and default state
- Guest subscriber plugin on GuestPaymentInformationManagement
- Customer subscriber plugin on PaymentInformationManagement
- Payment extension attribute `panth_subscribe_newsletter` for clean
  API transport
- Pre-checks the box if logged-in customer is already subscribed

### Added — styling
- Card styles: Elevated (Shadow), Bordered, Flat, Glassmorphism
- Admin colour picker for accent colour
- Border radius control
- Step indicators toggle
- Field modes: Compact (multi-field rows) / Full Width
- Placeholder and tooltip toggles
- Billing title visibility toggle
- CSS custom properties for theming (--panth-checkout-accent,
  --panth-checkout-radius)

### Added — custom code
- Custom CSS textarea injected as inline style at checkout
- Custom JS textarea injected via RequireJS at checkout

### Added — admin
- Full admin configuration under Stores -> Configuration -> Panth
  Extensions -> Checkout Extended
- ACL resource Panth_CheckoutExtended::config for granular permissions
- Colour picker field renderer for accent colour

### Quality
- Constructor injection only — zero ObjectManager usage
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
