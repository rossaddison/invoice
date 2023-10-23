<?php
/**
 * Contains the language translations for the payment gateways
 */
declare(strict_types=1);
$lang = array(
    // General strings
    'online_payment'                     => 'オンライン決済',
    'online_payments'                    => 'オンライン決済',
    'online_payment_for'                 => 'Online Payment for',
    'online_payment_for_invoice'         => 'Online Payment for Invoice',
    'online_payment_method'              => 'オンライン決済方法',
    'online_payment_creditcard_hint'     => 'If you want to pay via credit card please enter the information below.<br/>The credit card information are not stored on our servers and will be transferred to the online payment gateway using a secure connection.',
    'enable_online_payments'             => 'Enable Online Payments',
    'payment_provider'                   => 'Payment Provider',
    'add_payment_provider'               => '支払い方法の追加',
    'transaction_reference'              => 'Transaction Reference',
    'payment_description'                => 'Payment for Invoice %s',

    // Credit card strings
    'creditcard_cvv'                     => 'CVV/CSC',
    'creditcard_details'                 => 'クレジット カードの詳細',
    'creditcard_expiry_month'            => '有効期限-月',
    'creditcard_expiry_year'             => '有効期限-年',
    'creditcard_number'                  => 'クレジットカード番号',
    'online_payment_card_invalid'        => 'このクレジット カードは無効です。提供された情報を確認してください。',

    // Payment Gateway Fields
    'online_payment_apiLoginId'          => 'API ログイン ID', // Field for AuthorizeNet_AIM
    'online_payment_transactionKey'      => '取引用キー', // Field for AuthorizeNet_AIM
    'online_payment_testMode'            => 'テストモード', // Field for AuthorizeNet_AIM
    'online_payment_developerMode'       => '開発者モード', // Field for AuthorizeNet_AIM
    'online_payment_websiteKey'          => 'ウェブサイトキー', // Field for Buckaroo_Ideal
    'online_payment_secretKey'           => '秘密鍵', // Field for Buckaroo_Ideal
    'online_payment_merchantId'          => 'マーチャント Id', // Field for CardSave
    'online_payment_password'            => 'パスワード', // Field for CardSave
    'online_payment_apiKey'              => 'Api キー', // Field for Coinbase
    'online_payment_secret'              => '秘密', // Field for Coinbase
    'online_payment_accountId'           => 'アカウント Id', // Field for Coinbase
    'online_payment_storeId'             => 'ストア Id', // Field for FirstData_Connect
    'online_payment_sharedSecret'        => 'Shared Secret', // Field for FirstData_Connect
    'online_payment_appId'               => 'アプリID', // Field for GoCardless
    'online_payment_appSecret'           => 'App Secret', // Field for GoCardless
    'online_payment_accessToken'         => 'アクセストークン', // Field for GoCardless
    'online_payment_merchantAccessCode'  => 'Merchant Access Code', // Field for Migs_ThreeParty
    'online_payment_secureHash'          => 'Secure Hash', // Field for Migs_ThreeParty
    'online_payment_siteId'              => 'サイト ID', // Field for MultiSafepay
    'online_payment_siteCode'            => 'サイト コード', // Field for MultiSafepay
    'online_payment_accountNumber'       => '口座番号', // Field for NetBanx
    'online_payment_storePassword'       => 'Store Password', // Field for NetBanx
    'online_payment_merchantKey'         => 'マーチャント キー', // Field for PayFast
    'online_payment_pdtKey'              => 'Pdt Key', // Field for PayFast
    'online_payment_username'            => 'ユーザー名', // Field for Payflow_Pro
    'online_payment_vendor'              => 'ベンダー', // Field for Payflow_Pro
    'online_payment_partner'             => 'パートナー', // Field for Payflow_Pro
    'online_payment_pxPostUsername'      => 'Px Post Username', // Field for PaymentExpress_PxPay
    'online_payment_pxPostPassword'      => 'Px Post Password', // Field for PaymentExpress_PxPay
    'online_payment_signature'           => '署名', // Field for PayPal_Express
    'online_payment_referrerId'          => 'Referrer Id', // Field for SagePay_Direct
    'online_payment_transactionPassword' => '取引パスワード', // Field for SecurePay_DirectPost
    'online_payment_subAccountId'        => 'Sub Account Id', // Field for TargetPay_Directebanking
    'online_payment_secretWord'          => '秘密の単語', // Field for TwoCheckout
    'online_payment_installationId'      => 'インストール Id', // Field for WorldPay
    'online_payment_callbackPassword'    => 'コールバックのパスワード', // Field for WorldPay

    // Status / Error Messages
    'online_payment_payment_cancelled'   => 'Payment cancelled.',
    'online_payment_payment_failed'      => 'Payment failed. Please try again.',
    'online_payment_payment_successful'  => 'Payment for Invoice %s successful!',
    'online_payment_payment_redirect'    => 'Please wait while we redirect you to the payment page...',
    'online_payment_3dauth_redirect'     => 'Please wait while we redirect you to your card issuer for authentication...'
);