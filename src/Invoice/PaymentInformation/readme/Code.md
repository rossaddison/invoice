# PaymentInformationController.php Overview

This file is a controller in a Yii-based PHP application responsible for managing the **online payment process for invoices**. It integrates with multiple payment gateways, allowing customers to pay their invoices securely.

---

## Core Purpose

The main responsibilities of `PaymentInformationController.php` are:

1. **Present a payment form** to the user based on the invoice and selected payment gateway.
2. **Process payments** by interacting with the chosen payment gateway's API.
3. **Handle gateway callbacks** to confirm payment success or failure.
4. **Update invoice status**, record the payment, and show confirmation to the user.

---

## Key Methods and Workflow

### 1. `inform(...)`
- **Primary entry point** when a user initiates payment for an invoice.
- Retrieves invoice details using a secure URL key.
- Prevents duplicate payments by checking payment status.
- Gathers necessary invoice data (balance, items, currency).
- Delegates to `pciCompliantGatewayInForms` to select the payment gateway.

### 2. `pciCompliantGatewayInForms(...)`
- **Router method** for payment gateways.
- Uses the gateway parameter in the URL to call the correct form-rendering function (e.g., Stripe, Braintree, Mollie).

### 3. Gateway-Specific `...InForm(...)` Methods
- Dedicated methods for each payment gateway:
  - `amazonInForm`
  - `braintreeInForm`
  - `mollieInForm`
  - `stripeInForm`
  - `openBankingInForm`
- Prepare data for the gateway’s payment form (API keys, session data, invoice details).
- Render a PCI-compliant payment form, ensuring sensitive data is sent directly to the gateway.

### 4. Gateway-Specific `..._complete(...)` Methods
- Handle callbacks after payment form submission (e.g., `stripe_complete`, `mollie_complete`).
- Verify payment status with the gateway.
- On success: update invoice, set balance to zero, record transaction via `record_online_payments_and_merchant`.
- On failure: update status and display error.

### 5. `record_online_payments_and_merchant(...)`
- Helper to record the online payment and the gateway’s response in the database.
- Sets a flash message to inform the user of payment outcome.

---

## Supported Payment Gateways

- **Amazon Pay**
- **Braintree** (a PayPal service)
- **Mollie**
- **Stripe**
- **Open Banking** (providers like Wonderful and Tink)

---

## Design and Architecture

- **Service-Oriented**: Uses service classes (e.g., `StripePaymentService`, `AmazonPayPaymentService`) for gateway logic, keeping the controller clean.
- **Repository Pattern**: Employs repositories (e.g., `InvRepository`, `ClientRepository`) for database access abstraction.
- **Security**:
  - Uses URL keys for public invoice links, not direct IDs.
  - Employs PCI-compliant gateway forms so sensitive data is never handled by the application server.

---

## Summary

`PaymentInformationController.php` provides a secure, extensible, and service-oriented approach to managing invoice payments. By integrating multiple gateways and adhering to best security practices, it enables customers to pay invoices easily while keeping financial data protected.