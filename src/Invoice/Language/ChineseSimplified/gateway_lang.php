<?php
declare(strict_types=1); 
/**
 * Contains the language translations for the payment gateways
 */
$lang = array(
    // General strings
    'online_payment'                     => '在线支付',
    'online_payments'                    => '在线支付',
    'online_payment_for'                 => '在线支付',
    'online_payment_for_invoice'         => '发票的在线付款',
    'online_payment_method'              => '在线支付',
    'online_payment_creditcard_hint'     => '如果你想要通过信用卡支付请输入下面的信息。 <br/>信用卡信息不存储在我们的服务器上，并将转移到使用安全连接的在线支付网关。',
    'enable_online_payments'             => '启用延迟付款',
    'payment_provider'                   => '支付服務提供商',
    'add_payment_provider'               => '支付服務提供商',
    'transaction_reference'              => '交易记录引用',
    'payment_description'                => '付款的发票 %s',

    // Credit card strings
    'creditcard_cvv'                     => 'CVV / CVC',
    'creditcard_details'                 => '信用卡资料',
    'creditcard_expiry_month'            => '过期月份',
    'creditcard_expiry_year'             => '过期年份',
    'creditcard_number'                  => '信用卡号码：',
    'online_payment_card_invalid'        => '这张信用卡是无效的。请检查所提供的信息。',

    // Payment Gateway Fields
    'online_payment_apiLoginId'          => 'Api 登录 Id', // Field for AuthorizeNet_AIM
    'online_payment_transactionKey'      => '交易密钥', // Field for AuthorizeNet_AIM
    'online_payment_testMode'            => '测试模式', // Field for AuthorizeNet_AIM
    'online_payment_developerMode'       => '开发模式', // Field for AuthorizeNet_AIM
    'online_payment_websiteKey'          => '网站 Key', // Field for Buckaroo_Ideal
    'online_payment_secretKey'           => '密钥', // Field for Buckaroo_Ideal
    'online_payment_merchantId'          => '商户号', // Field for CardSave
    'online_payment_password'            => '密码', // Field for CardSave
    'online_payment_apiKey'              => 'Api 密钥：', // Field for Coinbase
    'online_payment_secret'              => 'Secret', // Field for Coinbase
    'online_payment_accountId'           => '帐号', // Field for Coinbase
    'online_payment_storeId'             => '已储存', // Field for FirstData_Connect
    'online_payment_sharedSecret'        => '共享秘密', // Field for FirstData_Connect
    'online_payment_appId'               => '应用', // Field for GoCardless
    'online_payment_appSecret'           => 'App口令', // Field for GoCardless
    'online_payment_accessToken'         => '访问口令', // Field for GoCardless
    'online_payment_merchantAccessCode'  => '选择访问码', // Field for Migs_ThreeParty
    'online_payment_secureHash'          => '安全哈希', // Field for Migs_ThreeParty
    'online_payment_siteId'              => '網站Id', // Field for MultiSafepay
    'online_payment_siteCode'            => '电话号码', // Field for MultiSafepay
    'online_payment_accountNumber'       => '帐号', // Field for NetBanx
    'online_payment_storePassword'       => '存储密码', // Field for NetBanx
    'online_payment_merchantKey'         => '商户号', // Field for PayFast
    'online_payment_pdtKey'              => 'Pdt 的关键', // Field for PayFast
    'online_payment_username'            => '用户名', // Field for Payflow_Pro
    'online_payment_vendor'              => '供应商', // Field for Payflow_Pro
    'online_payment_partner'             => '合作伙伴', // Field for Payflow_Pro
    'online_payment_pxPostUsername'      => 'Px 邮政用户名', // Field for PaymentExpress_PxPay
    'online_payment_pxPostPassword'      => 'Px 邮政密码', // Field for PaymentExpress_PxPay
    'online_payment_signature'           => '签名', // Field for PayPal_Express
    'online_payment_referrerId'          => '推荐人代码', // Field for SagePay_Direct
    'online_payment_transactionPassword' => '交易密码', // Field for SecurePay_DirectPost
    'online_payment_subAccountId'        => '帐号', // Field for TargetPay_Directebanking
    'online_payment_secretWord'          => 'Secret Word', // Field for TwoCheckout
    'online_payment_installationId'      => '安装', // Field for WorldPay
    'online_payment_callbackPassword'    => '回调密码', // Field for WorldPay

    // Status / Error Messages
    'online_payment_payment_cancelled'   => '已取消付款',
    'online_payment_payment_failed'      => '支付失败，请重试。',
    'online_payment_payment_successful'  => '付款的发票 %s 成功 ！',
    'online_payment_payment_redirect'    => '请我们将重定向到支付页面，请稍候...',
    'online_payment_3dauth_redirect'     => '请我们将重定向到您的发卡银行进行身份验证，请稍候...'
);