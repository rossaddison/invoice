<?php

declare(strict_types=1);
// as at 16th February 2025. Note this file has been built using copy paste from resources/messages/en/app.php
// Remember to adjust the php file with '$lang =' as seen below
$lang = [
    'g.online_payment' => 'Online Payment',
    'g.online_payments' => 'Online Payments',
    'g.online_payment_for' => 'Online Payment for',
    'g.online_payment_for_invoice' => 'Online Payment for Invoice',
    'g.online_payment_method' => 'Online Payment Method',
    'g.online_payment_creditcard_hint' => 'If you want to pay via credit card please enter the information below.<br/>The credit card information are not stored on our servers and will be transferred to the online payment gateway using a secure connection.',
    'g.online_payment_version' => 'Omnipay Version (checked) / PCI Compliant (No credit card details stored on this database) (unchecked)',
    'g.enable_online_payments' => 'Enable Online Payments',
    'g.payment_provider' => 'Payment Provider',
    'g.provider_response' => 'Provider Response',
    'g.add_payment_provider' => 'Add a Payment Provider',
    'g.transaction_reference' => 'Transaction Reference',
    'g.transaction_successful' => 'Transaction successful',
    'g.payment_description' => 'Payment for Invoice %s',

    // Credit card strings
    'g.creditcard_cvv' => 'CVV / CSC',
    'g.creditcard_details' => 'Credit Card details',
    'g.creditcard_expiry_month' => 'Expiry Month',
    'g.creditcard_expiry_year' => 'Expiry Year',
    'g.creditcard_number' => 'Credit Card Number',
    'g.online_payment_card_invalid' => 'This credit card is invalid. Please check the provided information.',

    // Payment Gateway Fields
    'g.online_payment_apiLoginId' => 'Api Login Id', // Field for AuthorizeNet_AIM
    'g.online_payment_transactionKey' => 'Transaction Key', // Field for AuthorizeNet_AIM
    'g.online_payment_testMode' => 'Test Mode', // Field for AuthorizeNet_AIM && Swipe
    'g.online_payment_developerMode' => 'Developer Mode', // Field for AuthorizeNet_AIM
    'g.online_payment_websiteKey' => 'Website Key', // Field for Buckaroo_Ideal
    'g.online_payment_secretKey' => 'Secret Key', // Field for Buckaroo_Ideal
    'g.online_payment_merchantId' => 'Merchant Id', // Field for CardSave
    'g.online_payment_password' => 'Password', // Field for CardSave
    'g.online_payment_apiKey' => 'Api Key', // Field for Coinbase
    'g.online_payment_secret' => 'Secret', // Field for Coinbase
    'g.online_payment_accountId' => 'Account Id', // Field for Coinbase
    'g.online_payment_storeId' => 'Store Id', // Field for FirstData_Connect
    'g.online_payment_sharedSecret' => 'Shared Secret', // Field for FirstData_Connect
    'g.online_payment_appId' => 'App Id', // Field for GoCardless
    'g.online_payment_appSecret' => 'App Secret', // Field for GoCardless
    'g.online_payment_accessToken' => 'Access Token', // Field for GoCardless
    'g.online_payment_merchantAccessCode' => 'Merchant Access Code', // Field for Migs_ThreeParty
    'g.online_payment_secureHash' => 'Secure Hash', // Field for Migs_ThreeParty
    'g.online_payment_siteId' => 'Site Id', // Field for MultiSafepay
    'g.online_payment_siteCode' => 'Site Code', // Field for MultiSafepay
    'g.online_payment_accountNumber' => 'Account Number', // Field for NetBanx
    'g.online_payment_storePassword' => 'Store Password', // Field for NetBanx
    'g.online_payment_merchantKey' => 'Merchant Key', // Field for PayFast
    'g.online_payment_pdtKey' => 'Pdt Key', // Field for PayFast
    'g.online_payment_username' => 'Username', // Field for Payflow_Pro
    'g.online_payment_vendor' => 'Vendor', // Field for Payflow_Pro
    'g.online_payment_partner' => 'Partner', // Field for Payflow_Pro
    'g.online_payment_pxPostUsername' => 'Px Post Username', // Field for PaymentExpress_PxPay
    'g.online_payment_pxPostPassword' => 'Px Post Password', // Field for PaymentExpress_PxPay
    'g.online_payment_signature' => 'Signature', // Field for PayPal_Express
    'g.online_payment_referrerId' => 'Referrer Id', // Field for SagePay_Direct
    'g.online_payment_transactionPassword' => 'Transaction Password', // Field for SecurePay_DirectPost
    'g.online_payment_subAccountId' => 'Sub Account Id', // Field for TargetPay_Directebanking
    'g.online_payment_secretWord' => 'Secret Word', // Field for TwoCheckout
    'g.online_payment_installationId' => 'Installation Id', // Field for WorldPay
    'g.online_payment_callbackPassword' => 'Callback Password', // Field for WorldPay
    'g.online_payment_privateKey' => 'Private Key', // Field for Braintree
    'g.online_payment_publicKey' => 'Public Key', // Field for Braintree
    'g.online_payment_profileId' => 'Profile Id', // Field for CYbersource
    'g.online_payment_accessKey' => 'Access Key', // Field for CYbersource
    'g.online_payment_publishableKey' => 'Publishable Key', // Field for Stripe 15-11-2022
    // Amazon Pay 01-12-2022 amzn/amazon-pay-api-sdk-phpmazon-pay-api-sdk-php v2.4.0
    'g.online_payment_clientId' => 'Client Id',
    'g.online_payment_clientSecret' => 'Client Secret',
    'g.online_payment_publicKeyId' => 'Public Key Id',
    'g.online_payment_returnUrl' => 'Return Url',
    'g.online_payment_sandboxId' => 'Sandbox Id',
    'g.online_payment_sandbox' => 'Sandbox',
    'g.online_payment_region' => 'Region',
    // Paypal Checkout v2 4th March 2023 - Notifications
    'g.online_payment_webhookId' => 'Webhook Id',
    // Mollie ClientAPI 15 March 2024
    'g.online_payment_testOrLiveApiKey' => 'Test or Live Api Key i.e starts with test_ or live_',
    'g.online_payment_partnerID' => 'Partner ID',
    'g.online_payment_profileID' => 'Profile ID',

    // Status / Error Messages
    'g.online_payment_payment_cancelled' => 'Payment cancelled.',
    'g.online_payment_payment_failed' => 'Payment failed. Please try again.',
    'g.online_payment_payment_successful' => 'Payment for Invoice %s successful!',
    'g.online_payment_payment_redirect' => 'Please wait while we redirect you to the payment page...',
    'g.online_payment_3dauth_redirect' => 'Please wait while we redirect you to your card issuer for authentication...',
];
