<h6 id="invoice">Development Summary: (...resources/views/invoice/info/invoice.php)</h6>

<p><b>Aim: To develop a similar invoicing system to InvoicePlane integrating with the latest Jquery, and security features of Yii3 using wampserver
        as a test platform for WAMP and also Ubuntu 22.04 LTS for LAMP.</b>
</p>
<p><b>To Do's - Longer Term Goals</b></p>
<p>1. <s>Integration of Payment Gateways</s>PCI Compliant Stripe, Amazon Pay, and Braintree have been introduced.</p>
<p>2. Accountant Role with the ability of an accountant/bookkeeper to record payments against invoices.</p>
<p>3. Include a <b>Company Private Detail</b> specific logo on an invoice.</p>
<p>4. Acceptance Tests for Invoice</p>
<p>5. Further validation and exceptions to be included in testing of e-Invoicing using PeppolHelper.</p>
<p>6. Filters to be introduced on grids</p>
<p><s>7. Improve Generator index template using Yiisoft functions.</s></p>
<p><s>8. Include Product images</s></p>
<p>9. Integrate <code>https://github.com/php-finance/double-entry</code></p>
<p>10. Remove all $s variables passed to views since the variable is definded under config/params.php
<br>
<code>
'yiisoft/view' => [
    'basePath' => '@views',
    'parameters' => [
      'assetManager' => Reference::to(AssetManager::class),
      'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
      'currentRoute' => Reference::to(CurrentRoute::class),
      'translator' => Reference::to(TranslatorInterface::class),
      // yii-invoice - Below parameters are specifically used in views/layout/invoice
      's' => Reference::to(SettingRepository::class),
      'session' => Reference::to(SessionInterface::class),
      'datehelper' => Reference::to(DateHelper::class),
    ],
  ],      
</code>    
</p>    
<p><s>Pdf template construction upon emailing.</s></p>
<p><s>Work on info issues</s></p>
<p>Work In Progress - Shorter Term Goals</p>
<p><s>Psalm Level 2 - Testing</s></p>
<p><s>Dead Code Removal with Psalm 3 Testing</s></p>
<p><s>Language array generator using Google Translate</s></p>
<p>All invoices with dates falling within CompanyPrivate start and end dates will have the specific logo uploaded</p>
<p>for this CompanyPrivate record attached to these invoices.</p>
<p>CompanyPrivate logo will be automatically input on invoice/quotes depending on whether the date of the invoice falls between the start and end date.</p>
<p>Introducing Paypal.</p>
<p>Introducing India's PayTm payment gateway's QR code method of payment and comparing this with Stripe's method.</p>
<p>A General Sales Tax (GST) Tax System will have to be implemented first for this purpose.</p>
<p><b>25 September 2023</b></p>
<p>Bugfix $ucR variable undefined. Retain within controller rather than view.</p>
<p>Testing on yii3i.co.uk</p>
<p>.htaccess file located at root (yii3i.co.uk/) rebasing to /yii3i (main folder yii3-i-main) directing to public folder</p>
<p><a href = "https://stackoverflow.com/questions/23635746/htaccess-redirect-from-site-root-to-public-folder-hiding-public-in-url/23638209#23638209">Stackoverflow </a></p>
<p>
    <code>
        RewriteEngine On<br>
        RewriteBase /yii3i<br>
        RewriteCond %{THE_REQUEST} /public/([^\s?]*) [NC]<br>
        RewriteRule ^ %1 [L,NE,R=302]<br>
        RewriteRule ^((?!public/).*)$ public/$1 [L,NC]<br>
    </code>
</p>
<p><b>24 September 2023</b></p>
<p>Multiple Product Image Gallery can be created for each product under Product ... Index ... View ... View ... Product Images</p>
<p>Sales Report by Product/Task</p>
<p>Bug Fix InvItemAllowanceCharge Edit.</p>
<p><b>20 August 2023</b></p>
<p>Each Product has an additional field called Additional Item Property Name and Value. These product properties can be added from the product view.
<p>A number of Additional Properties can be added to a product now by means of the Product Property Entity.</p>
<p><img src="/site/options.png" height="300" width="500"></p>
<p><img src="/site/ecosio_openpeppol_ubl_invoice_3_15_0.png" height="300" width="600"></p>
<p>Introduce Peppol (src\Invoice\Helpers\Peppol\PeppolHelper)</p>
<p><a href="https://ecosio.com/en/peppol-and-xml-document-validator-button/?pk_abe=EN_Peppol_XML_Validator_Page&pk_abv=With_CTA"> An Ecosio validated 0 error xml e-invoice</a> can be generated. 
<p>Introduce StoreCove (src\Invoice\Helpers\StoreCove\StoreCoveHelper) </p>
<p><a href="https://www.storecove.com/docs#_json_object">A Storecove Json Encoded Invoice</a> can be generated. </p>
<p><b>Requirement 1:</b></p>
<p>With VAT enabled - VAT Invoices can now be created.</p>
<p><b>Requirement 2:</b></p>
<p>Store Cove Api connection functions have been created. (src\Invoice\InvoiceController.php store_cove_call_api)</p>
<p><b>Requirement 3:</b></p>
<p><b>Xml electronic invoices - Can be output</b> if the following sequence is followed:</p>
<p>a: A logged in Client sets up their Peppol details on their side via Client...View...Options...Edit Peppol Details for e-invoicing. </p> 
<p>b: A quote is created and sent by the Administrator to the Client.</p>
<p>c: A logged in Client creates a sales order from the quote with their purchase order number, <s>purchase order line number</s>, and their contact person in the modal.</p>
<p>d: A logged in Client, on each of the sales order line items, inputs their line item purchase order reference number, and their purchase order line number. (Mandatory or else exception will be raised). </p>
<p>e: A logged in Administrator, requests that terms and conditions be accepted.</p>
<p>f: A logged in Client accepts the terms and conditions. </p>
<p>g: A logged in Administrator, updates the status of the sales order from assembled, approved, confirmed, to generate. </p> 
<p>h: A logged in Administrator can generate an invoice if the sales order status is on 'generate'</p>
<p>i: A logged in Administrator can now generate a Peppol Xml Invoice using today's exchange rates setup on Settings...View...Peppol Electronic Invoicing...One of From Currency and One of To Currency</p>
<p>j: Peppol exceptions will be raised.</p>
<p><b>22 March 2023</b></p>
<p>Preparation for Peppol e-Invoicing: UBL classes created using num-num/ubl-invoice. See folder src/Invoice/Ubl. Psalm Level 1 tested.</p>
<p>Html of invoice can be created - modal_inv_to_html - under View...Options</p>
<p>Logo introduced on invoices</p>
<p>A common company logo and address template created under invoice/setting/views.</p>
<p>A sumex extension table of table Invoice created. - Generating a sumex pdf is in its infancy.</p>
<p>Removed extensions causing DOM errors. F12 not carrying any errors.</p>
<p>Simplified Introductory Slider</p>
<p>Created 3 additional 'Yes/No' settings for 1. Stream (G Icon) 2. Archive (folder icon) 3. Html code (code icon) under Pdf Settings</p>
<p>Archives are saving consistently under src/Invoice/Uploads/Archives</p>
<p>Bugfix: Delete inv item redirects to interim page properly.</p>
<p>Bugfix: Cannot delete item if Invoice has 'sent' status.</p>
<p>Zugferd Invoices are archived under Invoice/Uploads/Temp/Zugferd wiht a random string filename.</p>
<p><b>04 March 2023</b></p>
<p>Introduce ZugferdHelper and ZugferXml</p>
<p>LoginForm and SignUp form - Extend both forms with RulesProviderInterface<a href>https://github.com/yiisoft/form/pull/249</a></p>
<p>Psalm Level 1 Testing - 0 errors</p>
<p><b>23 February 2023</b></p>
<p><s>Psalm Level 1 Testing - 1 error - Framework related</s></p>
<code>ERROR: ImplementedReturnTypeMismatch - vendor/yiisoft/form/src/FormModel.php:218:33 - The inherited return type 'iterable<int|string, Yiisoft\Validator\RuleInterface|callable|iterable<int, Yiisoft\Validator\RuleInterface|callable>>' for Yiisoft\Validator\RulesProviderInterface::getRules is different to the implemented return type for Yiisoft\Form\FormModel::getrules 'array<array-key, mixed>' (see https://psalm.dev/123)
    public function getRules(): array</code>
<p><b>31 January 2023</b></p>
<p>Psalm Level 2 Testing - 0 errors</p>
<p>Bug Fix: Edit Task</p>
<p><b>28 January 2023</b></p>
<p>Helpers\CountryHelper\get_country_list</p>
<p>The following code is functional but results in UnresolvableInclude: Worth coming back to.</p>
<p>Psalm Level 2 Testing - Submission 1</p>
<code>    
/**<br>
 * Returns an array list of cldr => country, translated in the language $cldr.<br>
 * If there is no translated country list, return the english one.<br>
 *<br>
 * @param string $cldr<br>
 * @return mixed<br>
 */    <br>
public function get_country_list(string $cldr) : mixed<br>
{<br>
    $new_aliases = new Aliases(['@helpers' => __DIR__, '@country_list' => '@helpers/Country-list']);<br>
    $file = $new_aliases->get('@country_list') .DIRECTORY_SEPARATOR. $cldr .DIRECTORY_SEPARATOR.'country.php';<br>
    $default_english = $new_aliases->get('@country_list') .DIRECTORY_SEPARATOR.'en'.DIRECTORY_SEPARATOR.'country.php';<br>
    if (file_exists($file)) {<br>
        /**<br>
         * @psalm-suppress UnresolvableInclude<br>
         */<br>
        return (include $file);<br>
    } else {<br>
        /**<br>
         * @psalm-suppress UnresolvableInclude<br>
         */<br>
        return (include $default_english);<br>
    }<br>
}<br>
</code>
<p><b>27 January 2023</b></p>
<p>Psalm Level 3 Testing (0 errors)</p>
<p>Testing</p>
<p>Upgrade Amazon Pay: 2.4.0 => 2.5.1</p>
<p>Test Amazon Pay with a Client logon.</p> 
<p>1. Client has been registered as a User via Yii Demo.</p>
<p>2. Client's User id has been transferred to UserInv via Settings ... User Account...+ by means of an Admin logon</p>
<p>3. Client's client_id has been assigned to their user_id via Settings ... User Account ...Assigned Clients by means of an Admin logon.</p>  
<p>Remove unknown region 'gb' bug. Admin must select one of three amazon regions in dropdown under Settings ... Online Payment. </p>
<p>Include Amazon's 3 standard regions ie. North America, Europe, and Japan in regions dropdown on Amazon_pay Settings Payment Gateway.</p>
<p>If region is not set, default to 'eu' in PaymentInformation/amazon_get_region</p>
<p>Include default time/zone under InvoiceController</p>
<p><b>23 January 2023</b></p>
<p>Psalm Level 3 Testing (0 errors)</p>
<p>Improve security of client viewing their quotes/invoices online with following code in InvController/url_key function</p>
<p><code>
        // After signup the user was included in the userinv using Settings...User Account...+<br>
$user_inv = $uiR->repoUserInvUserIdquery($currentUser_getId);<br>
// The client has been assigned to the user id using Setting...User Account...Assigned Clients<br>
$user_client = $ucR->repoUserClientqueryCount($currentUser_getId, $inv->getClient_id()) === 1 ? true : false;<br>
if ($user_inv && $user_client) {  <br>
</code></p>
<p>If the currentUser getId returns a null value they are a guest. See documentation. Yiisoft/CurrentUser</p>
<p>Note: The Psalm PossiblyNullArgument has to be suppressed here to allow for a possible null value here for validation of Yiisoft guest status. </p>
<p>No user with Yiisoft guest status is allowed in.</p>
<p><b>3 January 2023</b></p>
<p><a href="https://github.com/yiisoft/demo/issues/559"><s>Issue 559</s></a></p>
<p>Uzbek Introduced</p>
<p>Psalm Level 7,6,5,4 Testing (0 errors)</p>
<p><b>2 January 2023</b></p>
<p>Psalm Level 7,6,5,4 Testing (0 errors)</p>
<p><b>30 December 2022</b></p>
<p><b>12 Post-setup Steps to Introducing Azerbaijani language</b></p>
<p>1. config/params {locales}</p>
<p>2. views/layout/invoice.php {az_Asset and menu construction}</p>
<p>3a. views/layout/main.php {menu construction}</p>
<p>3b. views/layout/guest.php {menu construction}</p>
<p>4. SettingRepository/locale_language_array</p>
<p>5. Settings...Views...Google Translate...select locale from dropdown</p>
<p>6. Generator... Translate src/Invoice/Language/English/ip_lang.php</p>
<p>7. Generator... Translate src/Invoice/Language/English/gateway_lang.php</p>
<p>8. Generator... Translate src/Invoice/Language/English/app_lang {copied from ...resources/messages/en/app.php</p>
<p>9. Retrieve from ...views/invoice/generator/output_overwrite</p>
<p>10. copy output_overwrite/_ip_lang to src/Invoice/Language/{new language}</p>
<p>11. copy output_overwrite/_gateway_lang to src/Invoice/Language/{new language}</p>
<p>12. copy output_overwrite/_app.php to ...resources/messages/{new locale}</p>
<p><a href="https://github.com/yiisoft/demo/issues/559">Issue 559</a>
<p><code>Psalm level 4 php ./vendor/bin/psalm --alter --issues=InvalidReturnType,MissingReturnType,LessSpecificReturnType,MissingParamType --dry-run</code>     
<p><b>29 December 2022</b></p>
<p>Psalm Level 4 Testing (0 errors)</p>
<p>Using Generator...Translate{language file} and Setting...View...Google Translate...locale,
<p>Afrikaans, Russian, German, Ukrainian, Vietnamese language folders generated using Google Translate</p>
<p><b>Locale related files adjusted:</b></p>
<p>...src/Invoice/Setting/SettingRepository/locale_language_array/{insert the locale}</p>
<p>...src/Invoice/Asset/i18nAsset/{create your file with class name the same as the file name}</p>
<p>...resources/views/layout/main.php/{insert the language menu item}</p>
<p>...resources/views/layout/invoice.php{insert the 'use' namespace, 'case', and menu item.</p>
<p>...adjust the config/params 'locales' setting.</p>
<p><b>28 December 2022</b></p>
<p></p>
<p>Google Translate included to generate language files ie. ip_lang.php, gateway_lang.php, app.php for a locale.</p>    
<p><a href="https://github.com/googleapis/google-cloud-php-translate">Examples here</a></p>
<p><a href="https://console.cloud.google.com/iam-admin/serviceaccounts/details/
        // {unique_project_id}/keys?project={your_project_name}"">Build your cloud project<a/>
<p><b>Steps to get the Json file that must be saved in src/Invoice/google_tranlate_unique_folder</b></p>
<p>1: Download https://curl.haxx.se/ca/cacert.pem into active c:\wamp64\bin\php\php8.1.12 folder</p>
<p>2: Select your project that you created under https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?supportedpurview=project'</p>
<p>3: Click on Actions icon and select Manage Keys</p>
<p>4: Add Key</p>
<p>5: Choose the Json File option and Download the file to src/Invoice/Google_translate_unique_folder</p>
<p>6: You will have to enable the Cloud Translation API and provide your billing details. You will be charged 0 currency</p>
<p>7: Move the file from views/generator/output_overwrite to eg. src/Invoice/Language/{your language}</p>
<p><b>Step to choose a locale for translation: Settings...View...Google Translate...{dropdown}...save</b></p>
<p><b>Steps to generate eg. ip_lang.php</b></p>
<p>1: Generator ...Translate English\ip_lang.php</p>
<p>2: Either copy the array from off the screen or move it from ..resources/views/generator/output_overwrite into the appropriate folder</p>
<P><s>3. The separator ie. 'YYYY' will enable you to do a search and replace ie search 'YYYY' and replace ', to provide a comma separating each array item from the screen outputted array with a comma.</s></p>
<p>4. Dont forget to restart Netbeans 16 after adjusting C:\Program Files\NetBeans-16\netbeans\etc\netbeans.conf and inserting <code>"-J-Dfile.encoding=UTF-8"</code>  to parameter "netbeans_default_options". when dealing with languages with special characters.</p>
<p><b>26th December 2022</b></p>
<p>Include additional demo languages</p>
<p><b>24th December 2022</b></p>
<p>1. Retesting Sending of invoices by email.</p>
<p>2. Remove 55 Psalm Errors mostly related to <a href="https://github.com/rossaddison/yii3-i/issues/5">Issue #5</a>
<p>3. Psalm Level 7,6,5,4 Testing using Psalm 5.4 instead of 4.3</p>    
<p>4. Auditing of Setting...View...Invoice...Default Public Template and Mark Invoices Sent Copy</p>.
<p>5. Moved rossaddison/yii-invoice to rossaddison/yii3-i.</p>
<p>6. Resynced rossaddison/yii-invoice fork.</p>
<p><b>20th December 2022</b></p>
<p>1. Moved repository into separate working folder under blog, and blog-api for separate github workflow purposes.</p>
<p>2. Invoice works separately from blog. There are no hyperlinks to blog.</p>
<p>3. The Entity User's relations to comment and post have been removed since these are no longer needed.<br>
However the user that is registered under the demo still has to be added to the userinv table using Setting...User Account.    
</p>
<p>4. New Setting: <b>Invoices marked as 'sent' when copying</b> so that a client can view them online immediately without having to be sent by email. See Settings...View...Invoices...Other Settings. This is also useful for testing a series of invoices against a payment gateway.</p>
<p>5. A checkbox 'sandbox' has been added to the SettingsRepository/payment_gateways array for amazon_pay, and braintree.
If you plan to use a language other than English, you will need to include <code>'online_payment_sandbox' => 'Sandbox',</code> in the corresponding src/Invoice/Language/{your_language}/gateway_lang.php file. 
ie. check that the gateway_lang.php in the English folder corresponds with your language folder.
<p>6. The omnipay payment gateway with stripe will give the following warning (which is not recommended to accept): </p>
<p>Payment failed. Please try again. Response: Sending credit card numbers directly to the Stripe API is generally unsafe. We suggest you use test tokens that map to the test card you are using, see https://stripe.com/docs/testing.</p>
<p>You will have to go into https://dashboard.stripe.com/settings/integration and toggle the following screen.</p>
<img src="/site/stripe.png" height="300" width="600">
<p>7. Github Static Analysis: Psalm Level 7,6,5,4 testing - errors 0</p>
<p>8. The function src\User\Console\AssignRoleCommand and CreateCommand is functional as the observer role can be assigned to a client with the result ending up as an assignment in ...invoices/resources/rbac</p>
<p>How to reach Psalm level 4 the following code is needed to suppress Psalm errors on level 4: </p>
<p>9. Braintree Payment Gateway has been introduced. </p>
<code>
    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement, UndefinedInterfaceMethod
     */
</code>
<p>This will need to be looked at later.</p>     
<p><b>10th December 2022</b></p>
<p>Github Static Analysis: Psalm Level 7,6,5,4 testing - errors 0</p>
<p>1. Payment and Merchant (Online log) views upgraded to GridView.</p>
<p>2. A subarray has been added to the gateway array under SettingRepository/payment_gateways function <code>'version' => array(
                    'type' => 'checkbox',
                    'label' => 'Omnipay Version'                    
                    )</code><br>This is to facilitate the introduction of PCI compliant gateways. The two pci compliant tested gateways introduced here are: Stripe version 10 and Amazon_Pay. </p>
<p>3. If the above 'Omnipay version' checkbox is left unchecked for the specific gateway, you are implying that it is PCI compliant.</p>
<p>4. Uncheck 'Omnipay version' for Stripe Version 10 and the latest Amazon_Pay which have been introduced here.  </p>
<p>5. The guest url has been removed from the guest view. The user/client when logged in with 'observer role', has a list of enabled gateways in a list under options under the guest's invoice view. </p> 
<p>6. Stripe version 10 is PCI compliant because their js.stripe.com/v3 cdn is dealt with directly and no credit card details are shared. </p>
<p>7. Amazon Pay is PCI compliant because the user/client has to be logged into amazon.co.uk before they can use the Amazon Button. No credit card details are shared. </p>
<p>8. The PaymentInformationForm includes place to enter credit card details but this form only presents itself with Omnipay version checked gateways.
<p>9. The PaymentInformationController handles Omnipay and PCI compliant gateways.</p>
<p>10. Amazon_Pay has been integrated with productType set to PayOnly (see function form) so clients can pay if they have an Amazon Account.  </p>
<p>11. Register as a developer under Stripe to test the Omnipay Integration.
<p>12. The following code under ...resources/views/invoice/paymentinformation/payment_information_stripe_pci is of importance.</p>    
<p><code>. 'async function initialize() {'<br>
        // To avoid Error 422 Unprocessible entity <br>
        // const { clientSecret } = await fetch("/create.php", {<br>
        // method: "POST",<br>
        // headers: { "Content-Type": "application/json" },<br>
        // body: JSON.stringify({ items }),<br>
        // }).then((r) => r.json());<br>    
        . 'const { clientSecret } = {"clientSecret": "'. $client_secret .'"};'<br>
        . 'elements = stripe.elements({ clientSecret });'<br>
        . 'const paymentElementOptions = {'<br>
            . 'layout: "tabs"'<br>
        . '};'<br>
        . 'const paymentElement = elements.create("payment", paymentElementOptions);'<br>
        . 'paymentElement.mount("#payment-element");'<br>
    . '}'<br>
    </code>
</p>
<p>13. Stripe version 10 is working for debit/credit cards and for bacs. Login as a user with observer role.</p>
<img src="/site/stripe_v_10.png" height="300" width="450"/>
<p>14. Amazon Pay v2.40 is working if you are signed in to Amazon concurrently. Login as a user with observer role.</p>
<img src="/site/amazon_pay_v_2_40.png" height="300" width="450"/>
<p>15. The PaymentController and MerchantController guest related functions adopt the same approach as the InvController:</p>
<p><code>
        // Get the current user and determine from (@see Settings...User Account) whether they have been given <br>
        // either guest or admin rights. These rights are unrelated to rbac and serve as a second <br>
        // 'line of defense' to support role based admin control. <br>
        <br> 
        // Retrieve the user from Yii-Demo's list of users in the User Table <br>
        $user = $this->user_service->getUser(); <br>         
        <br>
        // Use this user's id to see whether a user has been setup under UserInv ie. yii-invoice's list of users <br>
        $userinv = ($uiR->repoUserInvUserIdcount((string)$user->getId()) > 0 <br>
                 ? $uiR->repoUserInvUserIdquery((string)$user->getId()) <br>
                 : null); <br>
        <br>        
        // Determine what clients have been allocated to this user (@see Settings...User Account) <br>
        // by looking at UserClient table <br>       
        <br>                
        // eg. If the user is a guest-accountant, they will have been allocated certain clients <br>
        // A user-quest-accountant will be allocated a series of clients <br>
        // A user-guest-client will be allocated their client number by the administrator so that <br>
        // they can view their invoices and make payment <br>
        $user_clients = (null!== $userinv ? $ucR->get_assigned_to_user($user->getId()) : []);</code>
</p>
<h2><a href="https://pay.amazon.co.uk/help/202022560">Notes for Amazon Pay</a></h2>
<p>To configure Amazon Pay you will need your Merchant ID, Public Key, Public Key ID and Private Key.</p>
<p>To find your keys and IDs:</p>
<ol>
    <li>sign in to your Amazon Payments merchant account in
        <a href="https://sellercentral-europe.amazon.com" target="_blank">Seller Central</a>
    </li>
    <li>click 
        <strong>Integration</strong>, and then click
        <strong>Integration Central</strong>
    </li>
    <li>select your
        <strong>Integration channel</strong>
    </li>
    <li>choose your <strong>ecommerce solution provider</strong>, or choose <strong>self-developed</strong></li>
    <li>choose your payment type in the
        <strong>Payment type</strong> dropdown</li>
    <li>click
        <strong>Get instructions</strong>, and then click
        <strong>Create keys</strong> in the Instructions section

    </li>
</ol>
<p>To enable the Sign in with Amazon button on your website, you will need a Client ID/Store ID.</p>
<p>To create a Client ID/Store ID or to manage applications:</p>
<ol>
    <li>sign in to your Amazon Payments merchant account in
        <a href="https://sellercentral-europe.amazon.com" target="_blank">Seller Central</a>
    </li>
    <li>choose
        <strong>Amazon Pay (Production View)</strong> from the dropdown on top of the page</li>
    <li>click
        <strong>Integration</strong> within the navigation bar, and then click
        <strong>Integration Central</strong>
    </li>
    <li>scroll to the end the page and click
        <strong>View Client ID/Store ID(s)</strong>, or click
        <strong>Create new Client ID/Store ID</strong>
    </li>
</ol>
<h2><a href="https://stripe.com/docs/keys">Notes for Stripe</a></h2>
<p>Api Key - private key not used in the package and not to be shown to anybody. It can be stored in the database if you choose.</p>
<p>Publishable Key eg. pk_... - Goto Stripe DashBoard at https://dashboard.stripe.com and store in Setting...View...Online Payment...Stripe</p>
<p>Secret Key eg sk_... -  Goto Stripe DashBoard at https://dashboard.stripe.com and store in Setting...View...Online Payment...Stripe</p>
<p>All keys viewed by phpMyAdmin are encoded with Crypt and bear no resemblance to the original keys.</p>
<p>javascript <code>$client_secret</code> in views/invoice/paymentinformation/payment_information_stripe_pci.php - generated by Payment Intent in PaymentInformationController/get_stripe_pci_client_secret based on data passed to payment intent.</p>
<p>...resources/views/layout/invoice.php and quest.php incorporate the following settings which will load either the Stripe asset or the Amazon Pay Asset</p>
<p><code>// '0' => PCI Compliant version<br>
$s->get_setting('gateway_stripe_version') == '0' ? $assetManager->register(stripe_v10_Asset::class) : '';<br>
$s->get_setting('gateway_amazon_pay_version') == '0' ? $assetManager->register(amazon_pay_v2_4_Asset::class) : '';<br></code>
</p>
<p>Enable these Payment Gateways under Settings...Views...Online Payment</p>
<p><b>11th November 2022</b></p>
<p>Bug fix: Include Integration 18.066. Paginators working with external status by inserting status into urlArguments.</p>
<code>
     OffsetPagination::widget()<br>
         ->menuClass('pagination justify-content-center')<br>
         ->paginator($paginator)<br>         
         // No need to use page argument since built-in. Use status bar value passed from urlGenerator to quote/guest<br>
         ->urlArguments(['status'=>$status])<br>
         ->render(),<br>
    )
</code>    
<p><b>8th November 2022</b></p>
<p>Add tabs draft, sent, viewed, approved, canceled, rejected to client view quote</p>
<p>Add tabs draft, sent, viewed, paid to client view invoice</p>
<p><b>4th November 2022</b></p>
<p>Psalm level 7,6,5,4 testng - 0 errors</p>
<p>Bugfixing quotes and invoices</p>
<p>Moved views to resources.</p>
<p><b>25th October 2022</b></p>
<p>Invoice, Quote, Product, UserInv GridView adjusted for latest Gridview code adjustments. Product table can now be sorted.</p>
<p>All tables sort with descending ie. minus sign attached to Sort:: array's id ie. -id</p>
<code>$sort = Sort::only(['status_id','number','date_created','date_due','id','client_id'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                // Show the latest quotes first => -id
                ->withOrderString($query_params['sort'] ?? '-id'); </code>
<p>The email config params mentioned in 11th October, now have to be linked to the Email Sending Method on Setting...Email...Email Sending Method.</p>
<p>The following code added to the end of the latest yiisoft/demo composer.json ensures that rossaddison/yii-invoice</p> 
<p>will work with psr-3. The dev-psr-3 branch will be picked up. </p>
<code>"rossaddison/mpdf": "*"</code>    
<p>Psalm Level - 7,6,5,4 Testing - 0 errors</p>
<p>Include start date and end date on CompanyPrivate Entity</p>
<p>Archived pdf can now be sent automatically with singular email attachment using Setting...View...Email...Attach Quote/Invoice on email?</p>
<p>MailerHelper/yii_mailer_send function <br>
<code>
    $path_info = pathinfo($pdf_template_target_path); <br>
    $path_info_file_name = $path_info['filename']; <br>
    $email_attachments_with_pdf_template = $email->withAttached(File::fromPath(FileHelper::normalizePath($pdf_template_target_path), <br>
    $path_info_file_name, <br>
    'application/pdf') <br>
    ); <br>
</code>
</p>
<p><b>11h October 2022</b></p>
<p>Psalm Level - 7,6,5,4 Testing - 0 errors</p>
<p>Setting...View...Email shows some config/params.php settings using SettingRepository <code>config_params</code> function.</p>
<p>
<code>
    public function config_params() : array { <br>    
        $config = ConfigFactory::create(new ConfigPaths(dirname(dirname(dirname(__DIR__))), 'config/'), null); <br>
        $params = $config->get('params'); <br>
        $config_array = [ <br>
            'esmtp_scheme' =>$params['symfony/mailer']['esmtpTransport']['scheme'],<br>
            'esmtp_host'=>$params['symfony/mailer']['esmtpTransport']['host'],<br>
            'esmtp_port'=>$params['symfony/mailer']['esmtpTransport']['port'],<br>
            'use_send_mail'=>$params['yiisoft/mailer']['useSendmail'] == 1 ? $this->trans('true') : $this->trans('false'),<br>
        ];<br>
        return $config_array;<br>
    }     
</code>
</p>
<p>Continue with auditing of settings</p>
<p>Bug fix with test data</p>
<p><b>4th October 2022</b></p>
<p>Psalm Level 4 Testing Complete - 150 errors removed.</p>
<p><b>1st October 2022</b></p>
<p>Files can be attached to invoices. Security measure <code>is_uploaded_file php</code> function using 
    specifically a tmp file. See InvController/attachment_move_to function. </p>
<p>Add adjustments to Generator Templates</p>
<p><b>21st September 2022</b></p>
<p>Simplify accesschecker permissions to 4 basic permissions ie. editInv, and viewInv, and editPayment and viewPayment and editUser for all controllers.
<p>Inclusion of Yii's php form code replacing Html code on forms mailer_quote and mailer_invoice.</p>
<p>Psalm Level 6 - 155 errors removed. Remaining errors are yii-demo related.</p>
<p>Psalm Level 5 - 47 errors removed. Remaining errors are yii-demo related.</p>
<p>YiiMailer (adaptation of Symfonymailer) replaces phpmailer utilizing the code provided in yii-demo.</p>
<p>Files can be attached to emails using Send Email under Dropdown Options menu on the Quote/Invoice View Yii. Setting up the email templates is optional.</p>
<p>Users with observer role can view the quotes or invoices, and then access the 'approve or reject section' by using the guest url on the quote or invoice and pasting it into the browser.</p>
<p>Inclusion of below code in config/params and adjustments to MailerHelper and TemplateHelper to facilitate emailing.</p>
<p>Email Templates are working through javascript and parsing of data to build the template is functional.</p>
<p><s>Note: Yii's php textbox, rather than preferably a textarea, is currently used to display the email template.</s></p>
<p>Emailing of quotes/invoices with adjustment to config.params. 
<code>
    'yiisoft/mailer' => [<br>
        'messageBodyTemplate' => [<br>
            'viewPath' => '@src/Contact/mail',<br>
        ],<br>
        'fileMailer' => [<br>
            'fileMailerStorage' => '@runtime/mail',<br>
        ],<br>
        'useSendmail' => false,<br>
        'writeToFiles' => false,<br>
    ],<br>
    'symfony/mailer' => [<br>
        'esmtpTransport' => [<br>
            'scheme' => 'smtp', // "smtps": using TLS, "smtp": without using TLS.<br>
            'host' => 'mail.btinternet.com',<br>
            'port' => 25,<br>
            'username' => 'ross.addison@yourinternet.com',<br>
            'password' => 'yourpassword',<br>
            'options' => [], // See: https://symfony.com/doc/current/mailer.html#tls-peer-verification<br>
        ],<br>
    ],    </code>

<p>Navbar on invoice layout includes an offcanvas Menu that works with the 'burger icon' menu button if NOT a full screen and moves in from the left.</p>
<p>Role: Observer included so customers can view their quotes, invoices, and payments online.</p>
<p><b>To assign the observer role to a client so they can view their invoices online.</b></p>
<p><b>Step 1:</b> Sign up user using Yii-demo</p>
<p><b>Step 2:</b> Signed in as Administrator, make sure this new user is a client ie. Client...View...New</p>
<p><b>Step 3:</b> As administatrator, make sure this user has Guest (read/only) permissions under Settings...User Account</p>
<p><b>Step 4:</b> Add signed up client / user to Settings...User Account table and use Assigned Clients 'burger' to add/associate this client to their user id.</p>
<p><b>Step 5:</b> Use assignRole 'observer' and user id from User Account to assign observer permissions to this user/client.</p>
<p><code>C:\wamp64\www\yii-invoice>yii user/assignRole observer 3</code></p>
<p><b>Step 6:</b> Create some invoices for this user/client signed in as Administrator and add payments.</p>
<p><b>Step 7:</b> Login as user/client and view the invoices and associated payments that you as Administrator have created for them.</p>
<p>The yii logo is called up permanently in the navbar with the logo located in the public folder.</p>
<p>The login logo previously located under Settings ... General has been moved to Settings...Company Private Details.</p>
<p>Each Company Private Detail record can now have their own logo/icon</p>
<p>This is useful for a company logo that evolves with time, older invoices retaining their older logo.</p> 
<p>Use Yii's<code> Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyPrivateForm')
    ->open()</code> on forms instead of Html form tag</p>
<p><code>C:\wamp64\www\yii-invoice>php ./vendor/bin/psalm 
         --alter --issues=InvalidNullableReturnType,
                          InvalidReturnType,
                          MissingReturnType,
                          LessSpecificReturnType,
                          MismatchingDocblockParamType,
                          MismatchingDocblockReturnType,
                          MissingParamType --dry-run</code></p>
<p><b>20th August 2022</b></p>
<p>Menu font enlarged.</p>
<p>Psalm Level 7 testing using <code>php ./vendor/bin/psalm</code>at command prompt: 0 errors.</p>
<p>Upgraded index for Quote and Invoice - sortable headers</p>
<p>Include a three input box Start of tax year date under Settings..View..Taxes for Tax Reporting.</p>
<p>Include a Y-m-d H:i:s format for invoicing.</p>
<p><b>Reports</b>Sales by Client, Sales by Date, Payment History, and Invoice Aging completed.</p>
<p>Move invoice/index schema code to Generator...Schema</p>
<p>Setting...UserAccount functional. Payment...Enter functional.</p>
<p>Invoices cannot be edited once paid. The read only flag is used on the invoice and buttons are disabled.</p>
<p><b>5th August 2022</b> - The Dashboard has been put up. Once Sumex is further developed it will be added to the dashboard. Accessible by menu: Dashboard </p>
<p>To avoid issues concerning psr3 and unvoided return types in mpdf/mpdf I have forked the latest mpdf/mpdf development repo and made adjustments to the relevant files. </p>
<p>The fork rossaddison/mpdf has been included in the composer.json file. Although at this stage it will run using version mpdf/mpdf 8.016, using psr 2, I have the reassurance that these errors concerning void will not show up in Yii</p> 
<p><b>29th July 2022</b> - Psalm testing using 'php ./vendor/bin/psalm' at command prompt - static errors removed. Info issues reduced from 2800 to 2000.</p>
<p>The client view now includes client's details, quotes, invoices, notes, and custom fields tabs.</p>
<p>Relevant javascript has been added to client.js ... load_client_notes, and save_client_notes.</p>
<b>21st July 2022</b>
<p>Invoice/Layout/main.php has been moved to @views/layout/invoice.php. The cldr setting value is given the $session->get('_language') value by means of the locale dropdown.</p>
<p>The $s value is configured for the @views/layout/invoice.php. (different to the demo layout ie. views/layout/main.php) in config/params.php yii-soft/view Reference::to and NOT by means of the InvoiceController .
   The $s value is necessary in this layout to record the current locale in the cldr setting if it is selected BEFORE login. ie. 
<code>$s->save_session_locale_to_cldr($session->get('_language') ?? ($s->get_setting('cldr') ? $s->get_setting('cldr') : 'en'));</code>
<p></p>
<p>jquery 1.13.2 which has just been released is now the default. jquery 1.13.0 has been removed. </p>
<p>In demo mode, the menu now includes the ability to test data with pre-setup clients, products, groups, tax rates. A deinstall feature of demo clients(2), products(2) is available. </p><!-- comment -->
<p>Two additional settings under the Settings tab-index help with this eg. Install test data and Use test data. These work with Generator...Remove Test Data...Reset Test Data</p>
<p>Datepickers now change automatically according to the locale dropdown with eg. views/client/form.php javascript function at the bottom.</p>
<p>Individual datepickers have been removed, and only one datepicker is used on the @views/layout/invoice.php page.
    <code>
        ...php<br>
    
    $js11 = "$(function () {".
    '$(".form-control.input-sm.datepicker").datepicker({dateFormat:"'.$datehelper->datepicker_dateFormat()<br>
                                                        .'", firstDay:'.$datehelper->datepicker_firstDay()<br>
                                                        .', changeMonth: true'<br>
                                                        .', changeYear: true'<br>
                                                        .', yearRange: "-50:+10"'<br>
                                                        .', clickInput: true'<br>
                                                        .', constrainInput: false'<br>
                                                        .', highlightWeek: true'<br>
                                                        .' });'.<br>
    '});';<br>
    echo Html::script($js11)->type('module');<br>
    
    </code>
<p>Every setting under views/invoice/setting/views is being audited by a <code>$s->where('number_format')</code> which tooltips why and where the setting is used.</p>
<p>config/params.php : Uncommenting Synctables (Table structure relatively permanent now) and assigning READ_AND_WRITE parameter to below with significant performance improvement.</p>
<code>\Yiisoft\Yii\Cycle\Schema\Provider\PhpFileSchemaProvider::class => [
                'mode' => \Yiisoft\Yii\Cycle\Schema\Provider\PhpFileSchemaProvider::MODE_READ_AND_WRITE,
                'file' => 'runtime/schema.php',
],</code>
<p>Inclusion of all countries from https://en.wikipedia.org/wiki/ISO_4217</p>
<p><b>9th July 2022</b> - Psalm testing using ./vendor/bin/psalm at the command prompt is now complete. 155 static errors and redundant code removed.</p>
<p><s><a href="https://github.com/yiisoft/demo/issues/439" >Issue 439: BelongsTo relation not updating on edit of relation field eg. Product' relation field tax rate is not editing and updating.</a></s> 
<s>Absolute Clear Cache path is located under SettingsController clear function and it will have to be adjusted to your setup in debug mode. </s>   
<s>public function clear() $directory = "C:\wamp64\www\yii-invoice\public\assets";</s>
<p>The SettingsController clear cache function is functional without an absolute path now.</p>
<p>The LAMP administrator will have to add sufficient permission to the public assets folder using the <code>sudo chown -R username folderpath </code> command before assets can be deleted in debug mode.</p>
<p>Quote - The Quote is functional ie. can be pdf'd <s>but the emailing aspect has to be developed.</s>and the emailing aspect has been tested with sendmail. Testing with SMTP is in progress.</p>
<p>Invoice - The Invoice is functional ie. can be pdf'd and archived <s>but the emailing aspect has to be developed.</s> and sendmail is functional. </p> 
<p>Recurring invoices - Functional but not fully tested.</p>
<p>Payment - Can be recorded against an Invoice. The latest version in League/Omnipay v3.2 will be setup with a few of the major payment providers added to the composer.json</p>
<p>User Custom Fields - not started yet.</p>
<p>File Attachments - not started yet. </p>
<p>Settings...View(Debug mode ie. Red) - These are being used.</p>
<p>Settings...View(Non-Debug mode ie. Not Red) - Some are being used. Their functionality is currently being analysed in Invoiceplane.--</p>
<p>The userinv table is an extension of yii/demo's user table and contains all the critical user information.</p>
</p>
<b>Setting Up</b>
<p>The settings table builds up initially with a default array of settings via the InvoiceController if they do not exist ie. setting 'default_settings_exist' does not exist.</p>
<b>Generator</b>
<p>The code generator templates have been adapted according to the latest demo updates.</p>
<b>Annotations</b>
<p>The lengthy Entity annotations have been replaced with the more concise Attributes coding structure. eg. <code> * @ORM\Column(type="string")</code>
    replaced with <code>#[ORM\Column(type: "string")]</code>. <s>However issue 439 is currently relevant here.</s>  
</p>
<b>Demo Mode</b>
<p>A demo mode variable located in src\Invoice\Layout\main.php ie. <code>$demo_mode</code> can be set to false to remove performance settings and the clear cache tool.
All areas in red will be removed.</p>
<b>Jquery</b>
<p>Jquery  3.6.0 (March 2nd 2021) version is being used for custom fields, and smaller modals. Temporarily, Invoiceplane's dependencies.js file is being used in AppAsset.
The modals are dependent on it. </p>
<b>Html Tags on Views</b>
<p>Views can be improved with more Yii related tags ie. using <code>Html::tag</code>. Html::encode is mandatory or compulsory or always present.</p>
<b>Paginator</b>
<p>The length of the lists can be changed via setting/view: <code>default_list_limit</code></p>
<b>Locales</b>
<p>The SettingRepository <code>load_language_folder</code> function accepts the dropdown locale through yiisoft/demo's <code>$session->get('_language')</code> function: this setting takes precedence over the database 'default_language' setting when set.</p>
<b>Client's language different to locale_derived_language or fallback settings 'default_language'</b>
<p>When printing occurs, the client's language ensures the documentation is printed out in his/her language using <code>$session->get('print_language')</code>
<p>The session variable <code>print_language</code> is reset after printing.</p>    
<b>Languages</b>
<p>Any words used not in the Invoiceplane folders, will be translated using Yii's translation methodology.</p>
<p>The above menu's language can be created in ...resources/messages for a specific language.
<p>Language folders can be imported from Invoiceplane but the following code must be inserted in each file <code>declare(strict_types=1);</code>within that folder.</p>
<b>Steps to include a language</b>
<p>1. Include the language folder in src/Invoice/Language after including declare(strict_types) in each file.</p>
<p>2. Include the new language in SettingsRepository's locale_language_array</p>
<p>3. Adjust the config/params.php locales array.</p>
<p>4. Adjust the views/layout/main.php menu.</p>
<p>5. Adjust each of the resources/messages folders language array.</p>
<p>6. CJK (C(hinese) J(apanese) K(orean) Languages <a href="https://mpdf.github.io/fonts-languages/cjk-languages.html"></a>
In order to view a file with non-embedded CJK (chinese-japanese-korean) fonts, you - and other users - need to download the Asian font pack for Adobe Reader for the languages:

Chinese (Simplified),
Chinese (Traditional),
Korean,
and Japanese

<a href="https://helpx.adobe.com/acrobat/kb/windows-font-packs-32-bit-reader.html">For Windows</a>
<a href="https://helpx.adobe.com/acrobat/kb/macintosh-font-packs�acrobat�reader-.html">For Mac</a></p>
<p>If spaces appear where the language should appear whilst viewing using eg. Chrome default PDF reader, add the extension - Chrome PDF Viewer 2.3.164.</p>
<p>7. When copying, and pasting the Chinese Simplified folder make sure that you remove the space between the Chinese and Simplified. ie. ChineseSimplified. This is camelcase. </p>
<b>Netbeans: <a href="https://stackoverflow.com/questions/59800221/gradle-netbeans-howto-set-encoding-to-utf-8-in-editor-and-compiler">How to include UTF-8 in Netbeans</a></b>
<p>Set encoding used in Netbeans globally to UTF-8. Added in netbeans.conf "-J-Dfile.encoding=UTF-8" to parameter "netbeans_default_options". This unfortunately has
to be done everytime you edit a file with 'special letters'. So edit the file with the UTF-8 setting above, save it, and then remove the above setting from Netbeans.conf. </p> 
<p>File Location: C:\Program Files\NetBeans-16\netbeans\etc\netbeans.conf open with notepad cntrl+F netbeans_default_options </p>
<b>Improved Features</b>
<p>A start tax year date eg. 06/04/2022 can be setup under view so that reports will use this by default as their start date.</p>
<p>The invoice aging report includes the invoice number(s) of the outstanding balance.</p>
<p>The Sales by Client report includes more specifics including the Sales without Tax, Item Tax, and Invoice Tax, and Sales Inclusive amount.</p>
<p>The Sales by Date (aka Sales by Year) report breaks each Sales per Client into quarters over the selected years.</p>
<p></p>
<p>Multiple products of quantity of 1 can be selected from the products lookup with the 'burger' or '3 horizontal lines' icon whilst in quote.</p>
<p>Multiple items can be deleted under the options button with 'Delete Item'.</p>
<p>Company details are divided into public and private. The profile table is intended for multiple profiles especially when email addresses, and mobile numbers change.</p>
<p>If a Tax -Rate is set to default, it will be used on all new quotes and invoices. ie. A Quote Tax Rate will be created from this Tax Rate automatically and will be created and used on all new quotes. The same will apply to all invoices.</p>
<p>If you want to create a quote or an invoice group, specific to a client, use the Setting...Group feature to setup a Group identifier eg. JOE for the  JOE Ltd Company. The next id will be appended to JOE ie. JOE001.</p>
<p>Products and Tasks can be entered separately on an invoice and are mutually exclusive so if you enter a task you cannot enter a product at the same time.</p>
<b>Deprecated Original Features</b>
<p>Themes will be introduced at a later date.</p>
<p>It is not intended to deprecate any of the features currently in InvoicePlane.</p>
<b>Proposed Features</b>
<p>Upgrading the forms to include the demo's php or server based form structure.</p>
<p>An interlinked basic bookkeeping system to audit transactions and produce an audit trail of invoices and payments</p>
<b>Security</b>
<p>All Entity properties initialized before the construct should be private. The private property is accessed through a public getter method as built below the construct.</p>
<b>Reasons for using a simplified <code>id</code> as a primary key in all the tables</b>
<p>See <a href="https://cycle-orm.dev/docs/annotated-relations/1.x/en#belongsto">{relationName}_{outerKey}</a>, the outerKey being the primary key, structure. 
    Eg. the field <code>tax_rate_id</code> in the Product table is a relation or a foreign key in the Product table equal and pointing to its parent table's Tax Rate's <code>id</code>  
    so the relation name <b>variable</b> in Entity: Product must be <code>$tax_rate</code> and joined with the outerKey as <code>$id</code> you get <code>$tax_rate_id</code> which matches the foreign key <code>$tax_rate_id</code> in Entity: Product
    If the primary key in the Tax Rate table was named something like tax_rate_id and not id then the relation could not be given a name.
</p>
<b>Future Work</b>
<p>Psalm static testing and removing of INFO suggestions.</p>
<p>Yiisoft's Mailer function incorporated using adminEmail as default config param for userinv->email.</p>
<p>A new feature of product custom fields has to be developed.</p>
<p>Client Custom Fields dependency on client_custom_fields.js will be removed as has been done for Payment Custom Fields. </p>
<p>Redundant functions generated by the Generator have to be deleted.</p>
<b>Work in progress</b>
<p>The client/view has been developed but the index under the Client View has to be rebuilt using the code similar to the new Invoice index.</p>
<p>The dashboard is being developed with Sumex still to be incorporated.</p>
<p>All the settings in the setting view,  still have to be linked to their specific purpose by consulting with the Original Invoiceplane code. Progress on this has been made especially with the Email tab.</p>
<p>All forms have to be modified from Html to Yii3's php based form structure.
<p>Email Template</p>
<p>User custom fields have to be developed.</p>
<p>Accessing a quote using the url_key in the browser is possible now by a customer. The url_key is retrieved at the bottom of the  quote by admin and pasted into an email. Login as a user who has the observer role with user_id 7, (after the 6 dummy users in demo) to test the customer's login ability. The customer can be sent the url_key (instead of the actual quote) by email. The user can then accept or reject the quote online using this url_key. This quote can then be copied to an invoice by the administrator if accepted by the customer.</p>
