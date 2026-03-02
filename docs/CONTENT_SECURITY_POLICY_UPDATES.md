# Content Security Policy Updates

## Overview

This document tracks changes made to the Content Security Policy (CSP) configuration in the application to accommodate third-party payment gateway scripts and stylesheets.

## File Location

- **Configuration File**: `public/.htaccess`
- **CSP Header Section**: Lines 11-25

## Recent Updates

### Date: March 2, 2026

#### Changes Made

The following domains were added to the CSP directives to support payment gateway integrations:

##### 1. Stripe Payment Integration
- **Directive**: `script-src`
- **Domain Added**: `https://js.stripe.com`
- **Reason**: Allow loading of Stripe's JavaScript library (v3)
- **Error Resolved**: "Loading the script 'https://js.stripe.com/v3/' violates the following Content Security Policy directive"

##### 2. Braintree Payment Integration
- **Directive**: `style-src`
- **Domain Added**: `https://assets.braintreegateway.com`
- **Reason**: Allow loading of Braintree Drop-in UI CSS (v1.33.7)
- **Error Resolved**: "Loading the stylesheet 'https://assets.braintreegateway.com/web/dropin/1.33.7/css/dropin.css' violates the following Content Security Policy directive"

##### 3. Amazon Pay Integration
- **Directive**: `script-src`
- **Domain Added**: `https://*.payments-amazon.com`
- **Reason**: Allow loading of Amazon Pay checkout scripts from all regional domains (EU, NA, FE, etc.)
- **Error Resolved**: "Loading the script 'https://static-eu.payments-amazon.com/checkout.js' violates the following Content Security Policy directive"
- **Note**: Wildcard pattern used to cover all regional Amazon Pay endpoints

##### 4. Stripe iFrame Support
- **Directive**: `frame-src`
- **Domains Added**: `https://js.stripe.com`, `https://*.stripe.com`
- **Reason**: Allow Stripe to load secure iframes for payment input fields
- **Error Resolved**: "Framing 'https://js.stripe.com/' violates the following Content Security Policy directive"
- **Note**: Wildcard pattern covers all Stripe subdomains for payment elements

##### 5. Comprehensive Stripe CSP Configuration
- **Multiple Directives Updated**: `script-src`, `img-src`, `connect-src`, `frame-src`, `child-src`
- **Approach**: Explicitly define all Stripe domains across relevant directives while maintaining `default-src 'self'`
- **Reason**: Stripe Elements use iframes hosted on Stripe's domain for PCI-DSS compliance - credit card data never touches your server
- **Security Benefit**: `default-src 'self'` remains in place as a secure fallback for unspecified directives
- **Additional**: Added `object-src 'none'` to prevent legacy plugin content (Flash, Java applets)

## Current CSP Configuration

```apache
Header always set Content-Security-Policy "\
default-src 'self'; \
script-src 'self' 'unsafe-inline' 'unsafe-eval' https://apis.google.com https://cdn.jsdelivr.net https://js.stripe.com https://*.stripe.com https://*.payments-amazon.com https://assets.braintreegateway.com; \
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://assets.braintreegateway.com; \
font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; \
img-src 'self' data: blob: https: https://*.stripe.com; \
connect-src 'self' https://api.storecove.com https://api.stripe.com https://*.stripe.com; \
frame-src 'self' https://js.stripe.com https://*.stripe.com https://hooks.stripe.com https://assets.braintreegateway.com; \
child-src 'self' https://js.stripe.com https://*.stripe.com; \
form-action 'self'; \
base-uri 'self'; \
object-src 'none'; \
manifest-src 'self'; \
worker-src 'self'"
```

**Key Security Features:**
- `default-src 'self'` - Secure fallback for unspecified directives
- `object-src 'none'` - Blocks legacy plugins (Flash, Java)
- `base-uri 'self'` - Prevents base tag hijacking
- `form-action 'self'` - Forms can only submit to same origin

## Payment Gateway Domains Summary

| Payment Gateway | CSP Directive | Domain(s) | Purpose |
|----------------|---------------|-----------|---------|
| **Stripe** | `script-src` | `https://js.stripe.com`, `https://*.stripe.com` | JavaScript SDK and scripts |
| **Stripe** | `connect-src` | `https://api.stripe.com`, `https://*.stripe.com` | API connections and tokenization |
| **Stripe** | `frame-src` | `https://js.stripe.com`, `https://*.stripe.com`, `https://hooks.stripe.com` | Secure payment iframes & webhooks |
| **Stripe** | `child-src` | `https://js.stripe.com`, `https://*.stripe.com` | Child browsing contexts |
| **Stripe** | `img-src` | `https://*.stripe.com` | Payment method icons and images |
| **Braintree** | `style-src` | `https://assets.braintreegateway.com` | Drop-in UI CSS |
| **Braintree** | `script-src` | `https://assets.braintreegateway.com` | Drop-in UI scripts |
| **Braintree** | `frame-src` | `https://assets.braintreegateway.com` | Payment iframes |
| **Amazon Pay** | `script-src` | `https://*.payments-amazon.com` | Regional checkout scripts |
| **StoreCove** | `connect-src` | `https://api.storecove.com` | E-invoicing API |

## Security Considerations

### Maintaining default-src 'self' with Payment Gateways

**Why it's safe to keep `default-src 'self'`:**
- Payment gateways like Stripe use **iframe-based tokenization** for PCI-DSS compliance
- Credit card data is entered in iframes hosted on Stripe's domain (`https://js.stripe.com`)
- Your server never touches raw credit card data - only secure tokens
- By explicitly allowing Stripe domains in `frame-src`, `script-src`, and `connect-src`, the integration works securely
- `default-src 'self'` provides a secure fallback for any directives not explicitly defined

**Payment Gateway Security Model:**
1. User enters card details in Stripe-hosted iframe (on Stripe's domain)
2. Stripe tokenizes the data and returns a secure token
3. Your application sends only the token to your server
4. Your server uses the token to process payment via Stripe API

This architecture ensures PCI compliance without your server handling sensitive card data.

### Current Unsafe Directives
- `'unsafe-inline'`: Present in both `script-src` and `style-src`
- `'unsafe-eval'`: Present in `script-src`

### Recommendations for Future Hardening
1. Consider implementing CSP nonces for inline scripts instead of `'unsafe-inline'`
2. Evaluate if `'unsafe-eval'` is necessary for all payment gateways - gradually remove if possible
3. Regularly review Stripe, Braintree, and Amazon Pay documentation for any new required domains
4. Monitor browser console for CSP violations during testing
5. Consider adding Subresource Integrity (SRI) hashes for external scripts where possible
6. Set up CSP reporting endpoint to track violations in production
7. Test payment flows thoroughly after any CSP changes

## Related Files

- Payment gateway assets registered in:
  - `resources/views/layout/guest.php`
  - `resources/views/layout/invoice.php`
- Asset classes:
  - `App\Invoice\Asset\pciAsset\stripe_v10_Asset`
  - `App\Invoice\Asset\pciAsset\amazon_pay_v2_7_Asset`
  - `App\Invoice\Asset\pciAsset\braintree_dropin_1_33_7_Asset`

## Testing

After CSP updates:
1. Clear browser cache
2. Hard refresh the payment pages (Ctrl+F5)
3. Verify no CSP violations in browser console
4. Test payment gateway initialization on invoice payment pages

## Additional Resources

- [MDN: Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [Stripe.js Documentation](https://stripe.com/docs/js)
- [Braintree Drop-in UI](https://developers.braintreepayments.com/guides/drop-in/overview/javascript/v3)
- [Amazon Pay Integration Guide](https://developer.amazon.com/docs/amazon-pay-checkout/introduction.html)
