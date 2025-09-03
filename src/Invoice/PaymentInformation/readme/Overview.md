# PaymentInformationController.php Documentation

## 1. Overview

`PaymentInformationController.php` is the central component responsible for handling all online payment processing for invoices within the application.  
It acts as a sophisticated bridge between the application's invoicing system and various external, third-party payment gateways.

Its primary design goal is to facilitate secure, PCI-compliant transactions by ensuring that sensitive payment details (like credit card numbers) are handled directly by the payment providers and never touch the application's server.  
The controller orchestrates the entire payment lifecycle, from presenting the user with a payment form to verifying the transaction's success and updating invoice records accordingly.

---

## 2. Supported Payment Gateways

The controller provides a pluggable architecture to support multiple payment providers:

- **Stripe:** For credit/debit card payments.
- **Mollie:** For various European payment methods.
- **Braintree:** A PayPal service for card payments.
- **Amazon Pay:** For payments using an Amazon account.
- **Open Banking:** Integrates with multiple providers, including:
  - Wonderful
  - Tink
  - Other OAuth2-based providers.

---

## 3. Core Workflow

The payment process follows a consistent, multi-step workflow:

1. **Initiation:**  
   A user clicks a "Pay Now" link, which directs them to the `inform` action, specifying the invoice and chosen gateway.
2. **Routing:**  
   The controller validates the request and routes it to a gateway-specific method (e.g., `stripeInForm`).
3. **Form Presentation:**  
   The gateway-specific method communicates with the provider's API to get necessary tokens (like a Stripe `client_secret` or a Braintree `clientToken`).  
   It then renders a secure payment page, often using the provider's own JavaScript libraries to build the form fields.
4. **User Interaction:**  
   The user enters their payment details directly into the secure fields controlled by the payment gateway.
5. **Callback/Redirect:**  
   After submission, the gateway redirects the user back to a `..._complete` action in the controller (e.g., `stripe_complete`).
6. **Verification:**  
   The `..._complete` action makes a final, secure server-to-server API call to the gateway to confirm the transaction's status.
7. **Record Keeping:**  
   If successful, `record_online_payments_and_merchant` is called to update the invoice status to "Paid", create a formal Payment record, and log the gateway's transaction details.
8. **User Notification:**  
   The user is shown a final success or failure message.

---

## 4. Key Methods and Routes

### 4.1. Main Entry Point

- **`inform(string $gateway, string $url_key)`**
  - **Route:** `paymentinformation/inform/{gateway}/{url_key}`
  - **Description:** Primary entry point for all payment attempts. Validates the invoice and gateway, then delegates to the appropriate `...InForm` method via the `pciCompliantGatewayInForms` router.

### 4.2. Gateway-Specific Form Handlers

Responsible for preparing and displaying the payment form for a specific gateway:

- `openBankingInForm(...)`
- `amazonInForm(...)`
- `stripeInForm(...)`
- `braintreeInForm(...)`
- `mollieInForm(...)`

### 4.3. Completion Callback Handlers

Redirect targets after a payment attempt on an external site; they verify the transaction and finalize the payment process:

- `amazon_complete(Request $request, CurrentRoute $currentRoute)`  
  Route: `paymentinformation/amazon_complete/{url_key}`
- `openbanking_oauth_complete(Request $request, CurrentRoute $currentRoute)`  
  Route: `paymentinformation/openbanking_oauth_complete/{url_key}`
- `tink_complete(CurrentRoute $currentRoute)`  
  Route: `paymentinformation/tink_complete/{url_key}/{ref}`
- `wonderful_complete(CurrentRoute $currentRoute)`  
  Route: `paymentinformation/wonderful_complete/{url_key}/{ref}`
- `braintree_complete(Request $request, CurrentRoute $currentRoute)`  
  Route: `paymentinformation/braintree_complete/{url_key}`
- `mollie_complete(CurrentRoute $currentRoute)`  
  Route: `paymentinformation/mollie_complete/{url_key}`
- `stripe_complete(Request $request, CurrentRoute $currentRoute)`  
  Route: `paymentinformation/stripe_complete/{url_key}`

### 4.4. Internal Helper Methods

- **`record_online_payments_and_merchant(...)`:**  
  Creates the Payment and Merchant records after a successful transaction.  
  This is the single source of truth for recording a successful online payment in the database.
- **`pciCompliantGatewayInForms(...)`:**  
  Private router method that selects the correct `...InForm` handler based on the chosen gateway.
- **Logo Rendering Methods:**  
  `renderPartialAsStringCompanyLogo()`, `renderPartialAsStringBraintreeLogo()`, etc., are view helpers used to display branding on payment pages.

---

## 5. Services and Dependencies

The controller relies heavily on dependency injection and dedicated service classes to abstract the logic for each payment gateway, keeping the controller code clean and focused on orchestration.

- `AmazonPayPaymentService`
- `BraintreePaymentService`
- `StripePaymentService`
- `OpenBankingPaymentService`
- `PaymentService` (for creating Payment records)
- `MerchantService` (for creating Merchant log records)
- Various repositories for database access (`iR`, `iaR`, `cR`, etc.)
- SDKs and API clients for each gateway (e.g., `MollieClient`, `Stripe\Stripe`)

---