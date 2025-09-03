# PaymentInformationController.php Overview

`PaymentInformationController.php` is the central hub for handling all online payments for invoices in the application.  
It serves as a complex bridge between the application and various third-party payment gateways, ensuring payments are processed securely and in a PCI-compliant manner.

---

## Core Purpose

The main goal of this controller is to present customers with a payment form for a specific invoice, tailored to their chosen payment gateway.  
It manages the entire payment lifecycle: from initiating transactions with gateways, handling callbacks, and updating invoice statuses.

---

## Supported Gateways

This controller integrates with a wide range of payment providers:
- **Stripe** (credit/debit cards)
- **Mollie** (various European payment methods)
- **Braintree** (a PayPal service)
- **Amazon Pay**
- **Open Banking** (providers like Wonderful, Tink, and other OAuth2-based providers)

---

## Key Routes and Methods

### 1. `inform(gateway, url_key)`
- The main entry point when a user clicks a "Pay Now" link for an invoice.
- Accepts the gateway name (e.g., 'Stripe') and the invoice's unique `url_key`.
- Loads invoice data, checks payment status, and routes the request to the correct payment gateway handler.

### 2. `pciCompliantGatewayInForms(...)`
- Acts as a router, delegating requests to the appropriate gateway-specific form method (e.g., `stripeInForm`, `mollieInForm`).

### 3. **Gateway-Specific Form Methods (`...InForm`)**
- **`stripeInForm(...)`**:  
  Communicates with Stripe API to create a PaymentIntent, passing the client_secret and publishableKey to the view. Stripe.js handles card input, ensuring PCI compliance.
- **`mollieInForm(...)`**:  
  Creates a payment request via Mollie API, obtains a checkout URL, and renders a page directing the user to Mollie's secure site.
- **`braintreeInForm(...)`**:  
  Generates a client token, renders a Drop-in UI, and processes the payment nonce directly from the Braintree UI.
- **`amazonInForm(...)`**:  
  Prepares data and signatures for the Amazon Pay button and renders the view.
- **`openBankingInForm(...)`**:  
  Determines the specific provider (e.g., Wonderful, Tink), and prepares the authentication URL/token for bank transfer initiation.

### 4. **Completion Callback Methods (`..._complete`)**
- Redirect targets after the user completes payment on the third-party site.
- Each method (e.g., `stripe_complete`, `mollie_complete`) verifies payment status with its gateway and, on success, calls `record_online_payments_and_merchant`.

---

## Helper Methods

- **`record_online_payments_and_merchant(...)`**:  
  - Creates a new Payment record, linking it to the invoice and updating the balance.
  - Logs the transaction in a Merchant record, including reference numbers and status messages.

- **Logo Rendering Methods**:  
  Render logos for the company and payment providers to improve the payment experience.

---

## Summary

`PaymentInformationController.php` orchestrates the entire online payment process.  
It securely initiates transactions with multiple gateways, renders the appropriate payment forms, and handles callbacks to confirm payment and update application records—maintaining PCI compliance throughout by delegating sensitive financial data handling to the payment providers.