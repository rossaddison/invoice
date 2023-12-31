<?php
declare(strict_types=1); 
$lang = array(
	// payment gateways
	'merchant_2checkout'					=> '2Checkout',
	'merchant_authorize_net'				=> 'Authorize.Net AIM',
	'merchant_authorize_net_sim'			=> 'Authorize.Net SIM',
	'merchant_buckaroo'						=> 'Buckaroo',
	'merchant_cardsave'						=> 'Cardsave',
	'merchant_dps_pxpay'					=> 'DPS PaymentExpress PxPay',
	'merchant_dps_pxpost'					=> 'DPS PaymentExpress PxPost',
	'merchant_dummy'						=> 'Dummy',
	'merchant_eway'							=> 'eWay Hosted',
	'merchant_eway_shared'					=> 'eWay Shared',
	'merchant_eway_shared_uk'				=> 'eWay Shared (UK)',
	'merchant_ideal'						=> 'iDEAL',
	'merchant_inipay'						=> 'INIpay',
	'merchant_gocardless'					=> 'GoCardless',
	'merchant_manual'						=> '手册',
	'merchant_mollie'						=> 'Mollie',
	'merchant_netaxept'						=> 'Nets Netaxept',
	'merchant_ogone_directlink'				=> 'Ogone DirectLink',
	'merchant_payflow_pro'					=> 'Payflow Pro',
	'merchant_paymate'						=> 'Paymate',
	'merchant_paypal_express'				=> 'PayPal Express',
	'merchant_paypal_pro'					=> 'PayPal Pro',
	'merchant_rabo_omnikassa'				=> 'Rabo OmniKassa',
	'merchant_sagepay_direct'				=> 'Sagepay Direct',
	'merchant_sagepay_server'				=> 'Sagepay Server',
	'merchant_stripe'						=> 'Stripe',
	'merchant_webteh_direct'				=> 'Webteh Direct',
	'merchant_worldpay'						=> 'WorldPay',

	// payment gateway settings
	'merchant_api_login_id'					=> 'Api 登录 Id',
	'merchant_transaction_key'				=> '交易密钥',
	'merchant_test_mode'					=> '测试模式',
	'merchant_developer_mode'				=> '开发模式',
	'merchant_simulator_mode'				=> '模拟器模式',
	'merchant_user_id'						=> '用户ID',
	'merchant_app_id'						=> '应用 ID',
	'merchant_psp_id'						=> 'PSP ID',
	'merchant_api_key'						=> 'API 密钥',
	'merchant_key'							=> 'Key',
	'merchant_key_version'					=> 'Key Version',
	'merchant_username'						=> '用户名',
	'merchant_vendor'						=> '供应商',
	'merchant_partner_id'					=> '合作伙伴 ID',
	'merchant_password'						=> '密码',
	'merchant_signature'					=> '签名',
	'merchant_customer_id'					=> '客户 ID',
	'merchant_merchant_id'					=> '货物 ID',
	'merchant_account_no'					=> '帐号',
	'merchant_installation_id'				=> '安装 ID',
	'merchant_website_key'					=> '网站 Key',
	'merchant_secret_word'					=> 'Secret Word',
	'merchant_secret'						=> 'Secret',
	'merchant_app_secret'					=> 'App口令',
	'merchant_secret_key'					=> '密钥',
	'merchant_token'						=> 'Token',
	'merchant_access_token'					=> '访问口令',
	'merchant_payment_response_password'	=> '支付响应密码',
	'merchant_company_name'					=> '公司名称',
	'merchant_company_logo'					=> '公司Logo',
	'merchant_page_title'					=> '标题',
	'merchant_page_banner'					=> '横幅',
	'merchant_page_description'				=> '说明',
	'merchant_page_footer'					=> '页脚',
	'merchant_enable_token_billing'			=> '保存银行卡信息用于之前的账单',
	'merchant_paypal_email'					=> 'PayPal Email帐户',
	'merchant_acquirer_url'					=> 'Acquirer URL',
	'merchant_public_key_path'				=> '公共密钥服务器路径',
	'merchant_private_key_path'				=> '私人密钥服务器路径',
	'merchant_private_key_password'			=> '私钥密码',
	'merchant_solution_type'				=> '要求PayPal 帐户',
	'merchant_landing_page'					=> '所选的付款选项',
	'merchant_solution_type_mark'			=> '要求PayPal 帐户',
	'merchant_solution_type_sole'			=> '可选的 PayPal 帐户',
	'merchant_landing_page_billing'			=> '游客结账/创建账号',
	'merchant_landing_page_login'			=> 'PayPal 帐户登录',

	// payment gateway fields
	'merchant_card_type'					=> '卡类型',
	'merchant_card_no'						=> '卡号',
	'merchant_name'							=> '名称',
	'merchant_first_name'					=> '名字',
	'merchant_last_name'					=> '姓氏',
	'merchant_card_issue'					=> '卡号',
	'merchant_exp_month'					=> '过期月份',
	'merchant_exp_year'						=> '过期年份',
	'merchant_start_month'					=> '开始月份',
	'merchant_start_year'					=> '开始年份',
	'merchant_csc'							=> '安全码',
	'merchant_issuer'						=> '发行银行',

	// status/error messages
	'merchant_insecure_connection'			=> '银行卡信息必须通过安全连接进行提交',
	'merchant_required'						=> ' 需填写%s',
	'merchant_invalid_card_no'				=> '无效卡号',
	'merchant_card_expired'					=> '卡已过期。',
	'merchant_invalid_status'				=> '无效付款状态',
	'merchant_invalid_method'				=> '此网管不支持此支付方式',
	'merchant_invalid_response'				=> '支付网关响应无效',
	'merchant_payment_failed'				=> '支付失败，请重试。',
	'merchant_payment_redirect'				=> '请我们将重定向到支付页面，请稍候...',
	'merchant_3dauth_redirect'				=> '请我们将重定向到您的发卡银行进行身份验证，请稍候...'
);

/* End of file ./language/english/merchant_lang.php */