<?php

declare(strict_types=1);

use App\Contact\ContactController;
use App\Auth\Controller\AuthController;
use App\Auth\Controller\SignupController;
use App\Auth\Controller\ResetController;
use App\Controller\SiteController;
use App\Controller\Actions\ApiInfo;
use App\Middleware\AccessChecker;
use App\Middleware\ApiDataWrapper;
use App\User\Controller\ApiUserController;
use App\User\Controller\UserController;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsXml;
use Yiisoft\Http\Method;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Yii\RateLimiter\Counter;
use Yiisoft\Yii\RateLimiter\LimitRequestsMiddleware;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;
use App\Invoice\AllowanceCharge\AllowanceChargeController;
use App\Invoice\Client\ClientController;
use App\Invoice\ClientCustom\ClientCustomController;
use App\Invoice\ClientNote\ClientNoteController;
use App\Invoice\ClientPeppol\ClientPeppolController;
use App\Invoice\Company\CompanyController;
use App\Invoice\CompanyPrivate\CompanyPrivateController;
use App\Invoice\Contract\ContractController;
use App\Invoice\CustomField\CustomFieldController;
use App\Invoice\CustomValue\CustomValueController;
use App\Invoice\Delivery\DeliveryController;
use App\Invoice\DeliveryLocation\DeliveryLocationController;
use App\Invoice\EmailTemplate\EmailTemplateController;
use App\Invoice\Family\FamilyController;
use App\Invoice\FromDropDown\FromDropDownController;
use App\Invoice\Generator\GeneratorController;
use App\Invoice\GeneratorRelation\GeneratorRelationController;
use App\Invoice\Group\GroupController;
use App\Invoice\Import\ImportController;
use App\Invoice\InvoiceController;
use App\Invoice\Inv\InvController;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeController;
use App\Invoice\InvAmount\InvAmountController;
use App\Invoice\InvCustom\InvCustomController;
use App\Invoice\InvItem\InvItemController;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeController;
use App\Invoice\InvRecurring\InvRecurringController;
use App\Invoice\ItemLookup\ItemLookupController;
use App\Invoice\Merchant\MerchantController;
use App\Invoice\Payment\PaymentController;
use App\Invoice\PaymentCustom\PaymentCustomController;
use App\Invoice\PaymentInformation\PaymentInformationController;
use App\Invoice\PaymentPeppol\PaymentPeppolController;
use App\Invoice\PaymentMethod\PaymentMethodController;
use App\Invoice\PaymentTerm\PaymentTermController;
use App\Invoice\PostalAddress\PostalAddressController;
use App\Invoice\Product\ProductController;
use App\Invoice\ProductImage\ProductImageController;
use App\Invoice\ProductProperty\ProductPropertyController;
use App\Invoice\Project\ProjectController;
use App\Invoice\Profile\ProfileController;
// Quote
use App\Invoice\Quote\QuoteController;
use App\Invoice\QuoteAmount\QuoteAmountController;
use App\Invoice\QuoteCustom\QuoteCustomController;
use App\Invoice\QuoteItem\QuoteItemController;
use App\Invoice\QuoteItemAmount\QuoteItemAmountController;
use App\Invoice\Report\ReportController;
use App\Invoice\SalesOrder\SalesOrderController;
use App\Invoice\SalesOrderItem\SalesOrderItemController;
use App\Invoice\Setting\SettingController;
use App\Invoice\Sumex\SumexController;
use App\Invoice\Task\TaskController;
use App\Invoice\TaxRate\TaxRateController;
use App\Invoice\Unit\UnitController;
use App\Invoice\UnitPeppol\UnitPeppolController;
use App\Invoice\UserClient\UserClientController;
use App\Invoice\UserInv\UserInvController;
use App\Invoice\Upload\UploadController;

return [
  // Lonely pages of site
    Route::get('/')
    ->action([SiteController::class, 'index'])
    ->name('site/index'),
    Route::methods([Method::GET, Method::POST], '/contact')
    ->action([ContactController::class, 'contact'])
    ->name('site/contact'),
  // Auth
  Route::methods([Method::GET, Method::POST], '/login')
    ->middleware(LimitRequestsMiddleware::class)
    ->action([AuthController::class, 'login'])
    ->name('auth/login'),
    Route::post('/logout')
    ->action([AuthController::class, 'logout'])
    ->name('auth/logout'),
    Route::methods([Method::GET, Method::POST], '/signup')
    ->middleware(fn(
      ResponseFactoryInterface $responseFactory,
      StorageInterface $storage
      ) => new LimitRequestsMiddleware(new Counter($storage, 10, 10), $responseFactory))
    ->action([SignupController::class, 'signup'])
    ->name('auth/signup'),
    Route::methods([Method::GET, Method::POST], '/reset')
    ->middleware(fn(
      ResponseFactoryInterface $responseFactory,
      StorageInterface $storage
      ) => new LimitRequestsMiddleware(new Counter($storage, 10, 10), $responseFactory))
    ->action([ResetController::class, 'reset'])
    ->name('auth/reset'),
    Group::create('/user')
      ->routes(
      // User
      Route::methods(['GET', 'POST'], '[/{page:\d+}/{pagesize:\d+}]')
      ->name('user/index')
      ->action([UserController::class, 'index']),
      // Profile page
      Route::get('/{login}')
      ->action([UserController::class, 'profile'])
      ->name('user/profile'),
    ),
  
    Group::create('/api')
      ->middleware(FormatDataResponseAsXml::class)
      ->middleware(ApiDataWrapper::class)
      ->routes(
      Route::get('/info/v1')
      ->name('api/info/v1')
      ->action(function (DataResponseFactoryInterface $responseFactory) {
      return $responseFactory->createResponse(['version' => '1.0', 'author' => 'yiisoft']);
      }),
      Route::get('/info/v2')
      ->name('api/info/v2')
      ->middleware(FormatDataResponseAsJson::class)
      ->action(ApiInfo::class),
      Route::get('/user')
      ->name('api/user/index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))  
      ->action([ApiUserController::class, 'index']),
      Route::get('/user/{login}')
      ->name('api/user/profile')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))  
      ->middleware(FormatDataResponseAsJson::class)
      ->action([ApiUserController::class, 'profile'])
    ),
        
    Group::create('/invoice')
    ->routes(
      Route::get('')
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'index'])
      ->name('invoice/index'),
      // InvItemAllowanceCharge
      Route::get('/acii')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemAllowanceChargeController::class, 'index'])
      ->name('acii/index'),
      // Add
      Route::methods([Method::GET, Method::POST], '/acii/add/{inv_item_id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemAllowanceChargeController::class, 'add'])
      ->name('acii/add'),
      // Edit
      Route::methods([Method::GET, Method::POST], '/acii/edit/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemAllowanceChargeController::class, 'edit'])
      ->name('acii/edit'),
      Route::methods([Method::GET, Method::POST], '/acii/delete/{id}')
      ->name('acii/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemAllowanceChargeController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/acii/view/{id}')
      ->name('acii/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemAllowanceChargeController::class, 'view']),
      Route::get('/allowancecharge')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([AllowanceChargeController::class, 'index'])
      ->name('allowancecharge/index'),
      Route::methods([Method::GET, Method::POST], '/allowancecharge/add_allowance')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([AllowanceChargeController::class, 'add_allowance'])
      ->name('allowancecharge/add_allowance'),
      Route::methods([Method::GET, Method::POST], '/allowancecharge/add_charge')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([AllowanceChargeController::class, 'add_charge'])
      ->name('allowancecharge/add_charge'),
      Route::methods([Method::GET, Method::POST], '/allowancecharge/edit_allowance/{id}')
      ->name('allowancecharge/edit_allowance')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([AllowanceChargeController::class, 'edit_allowance']),
      Route::methods([Method::GET, Method::POST], '/allowancecharge/edit_charge/{id}')
      ->name('allowancecharge/edit_charge')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([AllowanceChargeController::class, 'edit_charge']),
      Route::methods([Method::GET, Method::POST], '/allowancecharge/delete/{id}')
      ->name('allowancecharge/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([AllowanceChargeController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/allowancecharge/view/{id}')
      ->name('allowancecharge/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([AllowanceChargeController::class, 'view']),
      // Step 1: After https://app.storecove.com/en/docs 1.1.1 and 1.1.2 completed
      Route::get('/store_cove_call_api')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'store_cove_call_api'])
      ->name('invoice/store_cove_call_api'),
      // Step 2 - 1.1.4 a
      Route::get('/store_cove_call_api_get_legal_entity_id')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'store_cove_call_api_get_legal_entity_id'])
      ->name('invoice/store_cove_call_api_get_legal_entity_id'),
      // Step 3a and/or LEGAL entity identifier - 1.1.4 b
      Route::get('/store_cove_call_api_legal_entity_identifier')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'store_cove_call_api_legal_entity_identifier'])
      ->name('invoice/store_cove_call_api_legal_entity_identifier'),
      // Step 4 - 1.1.5
      Route::get('/store_cove_send_test_json_invoice')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'store_cove_send_test_json_invoice'])
      ->name('invoice/store_cove_send_test_json_invoice'),
      Route::get('/dashboard')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'dashboard'])
      ->name('invoice/dashboard'),
      Route::get('/faq/{topic}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'faq'])
      ->name('invoice/faq'),
      Route::get('/test_data_remove')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'test_data_remove'])
      ->name('invoice/test_data_remove'),
      Route::get('/test_data_reset')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'test_data_reset'])
      ->name('invoice/test_data_reset'),
      Route::get('/setting_reset')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'setting_reset'])
      ->name('invoice/setting_reset'),
      Route::get('/ubuntu')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvoiceController::class, 'ubuntu'])
      ->name('invoice/ubuntu'),
      Route::get('/client[/page/{page:\d+}[/active/{active}]]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientController::class, 'index'])
      ->name('client/index'),
      Route::methods([Method::GET, Method::POST], '/client/create_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientController::class, 'create_confirm'])
      ->name('client/create_confirm'),
      Route::methods([Method::GET, Method::POST], '/edit-a-client/{id}')
      ->name('client/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/client/edit_submit')
      ->name('client/edit_submit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientController::class, 'edit_submit']),
      Route::methods([Method::GET, Method::POST], '/client/delete/{id}')
      ->name('client/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/client/guest')
      ->name('client/guest')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editClientPeppol'))
      ->middleware(Authentication::class)
      ->action([ClientController::class, 'guest']),
      Route::methods([Method::GET, Method::POST], '/client/save_client_note_new')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientController::class, 'save_client_note_new'])
      ->name('client/save_client_note_new'),
      Route::methods([Method::GET, Method::POST], '/client/save_custom_fields')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientController::class, 'save_custom_fields'])
      ->name('client/save_custom_fields'),
      Route::methods([Method::GET, Method::POST], '/client/view/{id}[/page/{page:\d+}[/status/{status}]]')
      ->name('client/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientController::class, 'view']),
      Route::get('/clientpeppol')
      ->middleware(Authentication::class)
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->action([ClientPeppolController::class, 'index'])
      ->name('clientpeppol/index'),
      // Add
      Route::methods([Method::GET, Method::POST], '/clientpeppol/add/{client_id}')
      ->middleware(Authentication::class)
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editClientPeppol'))
      ->action([ClientPeppolController::class, 'add'])
      ->name('clientpeppol/add'),
      // Edit
      Route::methods([Method::GET, Method::POST], '/clientpeppol/edit/{client_id}')
      ->name('clientpeppol/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editClientPeppol'))
      ->middleware(Authentication::class)
      ->action([ClientPeppolController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/clientpeppol/delete/{client_id}')
      ->name('clientpeppol/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editClientPeppol'))
      ->middleware(Authentication::class)
      ->action([ClientPeppolController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/clientpeppol/view/{client_id}')
      ->name('clientpeppol/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editClientPeppol'))
      ->middleware(Authentication::class)
      ->action([ClientPeppolController::class, 'view']),
      Route::get('/company')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyController::class, 'index'])
      ->name('company/index'),
      Route::methods([Method::GET, Method::POST], '/company/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyController::class, 'add'])
      ->name('company/add'),
      Route::methods([Method::GET, Method::POST], '/company/edit/{id}')
      ->name('company/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/company/delete/{id}')
      ->name('company/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/company/view/{id}')
      ->name('company/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyController::class, 'view']),
      Route::get('/companyprivate')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyPrivateController::class, 'index'])
      ->name('companyprivate/index'),
      Route::methods([Method::GET, Method::POST], '/companyprivate/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyPrivateController::class, 'add'])
      ->name('companyprivate/add'),
      Route::methods([Method::GET, Method::POST], '/companyprivate/edit/{id}')
      ->name('companyprivate/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyPrivateController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/companyprivate/delete/{id}')
      ->name('companyprivate/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyPrivateController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/companyprivate/view/{id}')
      ->name('companyprivate/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CompanyPrivateController::class, 'view']),
      Route::get('/customfield')
      ->middleware(Authentication::class)
      ->action([CustomFieldController::class, 'index'])
      ->name('customfield/index'),
      Route::methods([Method::GET, Method::POST], '/customfield/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CustomFieldController::class, 'add'])
      ->name('customfield/add'),
      Route::methods([Method::GET, Method::POST], '/customfield/edit/{id}')
      ->name('customfield/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CustomFieldController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/customfield/delete/{id}')
      ->name('customfield/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CustomFieldController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/customfield/view/{id}')
      ->name('customfield/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([CustomFieldController::class, 'view']),
      Route::get('/customvalue')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([CustomValueController::class, 'index'])
      ->name('customvalue/index'),
      Route::methods([Method::GET, Method::POST], '/customvalue/field/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([CustomValueController::class, 'field'])
      ->name('customvalue/field'),
      Route::methods([Method::GET, Method::POST], '/customvalue/new/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CustomValueController::class, 'new'])
      ->name('customvalue/new'),
      Route::methods([Method::GET, Method::POST], '/customvalue/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CustomValueController::class, 'add'])
      ->name('customvalue/add'),
      Route::methods([Method::GET, Method::POST], '/customvalue/edit/{id}')
      ->name('customvalue/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CustomValueController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/customvalue/delete/{id}')
      ->name('customvalue/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([CustomValueController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/customvalue/view/{id}')
      ->name('customvalue/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([CustomValueController::class, 'view']),
      Route::get('/clientcustom')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([ClientCustomController::class, 'index'])
      ->name('clientcustom/index'),
      Route::methods([Method::GET, Method::POST], '/clientcustom/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientCustomController::class, 'add'])
      ->name('clientcustom/add'),
      Route::methods([Method::GET, Method::POST], '/clientcustom/edit/{id}')
      ->name('clientcustom/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientCustomController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/clientcustom/delete/{id}')
      ->name('clientcustom/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientCustomController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/clientcustom/view/{id}')
      ->name('clientcustom/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([ClientCustomController::class, 'view']),
      Route::get('/clientnote')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([ClientNoteController::class, 'index'])
      ->name('clientnote/index'),
      Route::methods([Method::GET, Method::POST], '/clientnote/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientNoteController::class, 'add'])
      ->name('clientnote/add'),
      Route::methods([Method::GET, Method::POST], '/clientnote/edit/{id}')
      ->name('clientnote/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientNoteController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/clientnote/delete/{id}')
      ->name('clientnote/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ClientNoteController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/clientnote/view/{id}')
      ->name('clientnote/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([ClientNoteController::class, 'view']),
      Route::get('/contract')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([ContractController::class, 'index'])
      ->name('contract/index'),
      Route::methods([Method::GET, Method::POST], '/contract/add/{client_id}')
      ->name('contract/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([ContractController::class, 'add']),
      Route::methods([Method::GET, Method::POST], '/contract/edit/{id}')
      ->name('contract/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([ContractController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/contract/delete/{id}')
      ->name('contract/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ContractController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/contract/view/{id}')
      ->name('contract/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([ContractController::class, 'view']),
      Route::get('/del[/page/{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([DeliveryLocationController::class, 'index'])
      ->name('del/index'),
      Route::methods([Method::GET, Method::POST], '/del/add/{client_id}')
      ->name('del/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([DeliveryLocationController::class, 'add']),
      Route::methods([Method::GET, Method::POST], '/del/edit/{id}')
      ->name('del/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([DeliveryLocationController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/del/delete/{id}')
      ->name('del/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([DeliveryLocationController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/del/view/{id}')
      ->name('del/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([DeliveryLocationController::class, 'view']),
      Route::get('/delivery')
      ->middleware(Authentication::class)
      ->action([DeliveryController::class, 'index'])
      ->name('delivery/index'),
      // Add
      Route::methods([Method::GET, Method::POST], '/delivery/add/{inv_id}')
      ->middleware(Authentication::class)
      ->action([DeliveryController::class, 'add'])
      ->name('delivery/add'),
      // Edit
      Route::methods([Method::GET, Method::POST], '/delivery/edit/{id}')
      ->name('delivery/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([DeliveryController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/delivery/delete/{id}')
      ->name('delivery/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([DeliveryController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/delivery/view/{id}')
      ->name('delivery/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([DeliveryController::class, 'view']),
      Route::get('/emailtemplate')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([EmailTemplateController::class, 'index'])
      ->name('emailtemplate/index'),
      Route::methods([Method::GET, Method::POST], '/emailtemplate/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([EmailTemplateController::class, 'add'])
      ->name('emailtemplate/add'),
      Route::methods([Method::GET, Method::POST], '/emailtemplate/edit/{email_template_id}')
      ->name('emailtemplate/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([EmailTemplateController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/emailtemplate/delete/{email_template_id}')
      ->name('emailtemplate/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([EmailTemplateController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/emailtemplate/get_content/{email_template_id}')
      ->name('emailtemplate/get_content')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([EmailTemplateController::class, 'get_content']),
      Route::methods([Method::GET, Method::POST], '/emailtemplate/view/{email_template_id}')
      ->name('emailtemplate/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([EmailTemplateController::class, 'view']),
      Route::get('/family[/page/{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([FamilyController::class, 'index'])
      ->name('family/index'),
      Route::methods([Method::GET, Method::POST], '/family/test')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(FormatDataResponseAsJson::class)
      ->action([FamilyController::class])
      ->name('family/test'),
      Route::methods([Method::GET, Method::POST], '/family/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([FamilyController::class, 'add'])
      ->name('family/add'),
      Route::methods([Method::GET, Method::POST], '/family/edit/{id}')
      ->name('family/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([FamilyController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/family/delete/{id}')
      ->name('family/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([FamilyController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/family/view/{id}')
      ->name('family/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([FamilyController::class, 'view']),
      Route::get('/from')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([FromDropDownController::class, 'index'])
      ->name('from/index'),    
      Route::methods([Method::GET, Method::POST], '/from/add')
      ->middleware(Authentication::class)
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->action([FromDropDownController::class, 'add'])
      ->name('from/add'),
      Route::methods([Method::GET, Method::POST], '/from/edit/{id}')
      ->name('from/edit')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([FromDropDownController::class, 'edit']), 
      Route::methods([Method::GET, Method::POST], '/from/delete/{id}')
      ->name('from/delete')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([FromDropDownController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/from/view/{id}')
      ->name('from/view')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([FromDropDownController::class, 'view']),
      Route::get('/generator')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'index'])
      ->name('generator/index'),
      Route::methods([Method::GET, Method::POST], '/generator/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'add'])
      ->name('generator/add'),
      Route::methods([Method::GET, Method::POST], '/generator/edit/{id}')
      ->name('generator/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/generator/delete/{id}')
      ->name('generator/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/generator/view/{id}')
      ->name('generator/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'view']),
      Route::methods([Method::GET, Method::POST], '/generator/entity/{id}')
      ->name('generator/entity')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'entity']),
      Route::methods([Method::GET, Method::POST], '/generator/repo/{id}')
      ->name('generator/repo')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'repo']),
      Route::methods([Method::GET, Method::POST], '/generator/service/{id}')
      ->name('generator/service')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'service']),
      Route::methods([Method::GET, Method::POST], '/generator/mapper/{id}')
      ->name('generator/mapper')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'mapper']),
      Route::methods([Method::GET, Method::POST], '/generator/controller/{id}')
      ->name('generator/controller')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'controller']),
      Route::methods([Method::GET, Method::POST], '/generator/form/{id}')
      ->name('generator/form')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'form']),
      Route::methods([Method::GET, Method::POST], '/generator/scope/{id}')
      ->name('generator/scope')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'scope']),
      Route::methods([Method::GET, Method::POST], '/generator/_index/{id}')
      ->name('generator/_index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, '_index']),
      Route::methods([Method::GET, Method::POST], '/generator/_index_adv_paginator/{id}')
      ->name('generator/_index_adv_paginator')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, '_index_adv_paginator']),
      Route::methods([Method::GET, Method::POST], '/generator/_index_adv_paginator_with_filter/{id}')
      ->name('generator/_index_adv_paginator_with_filter')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, '_index_adv_paginator_with_filter']),
      Route::methods([Method::GET, Method::POST], '/generator/_form/{id}')
      ->name('generator/_form')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, '_form']),
      Route::methods([Method::GET, Method::POST], '/generator/_view/{id}')
      ->name('generator/_view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, '_view']),
      Route::methods([Method::GET, Method::POST], '/generator/_route/{id}')
      ->name('generator/_route')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, '_route']),
      Route::methods([Method::GET, Method::POST], '/generator/_form_modal_field/{id}')
      ->name('generator/_form_modal_field')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, '_form_modal_field']),
      Route::methods([Method::GET, Method::POST], '/generator/_form_modal_create/{id}')
      ->name('generator/_form_modal_create')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, '_form_modal_create']),
      Route::methods([Method::GET, Method::POST], '/generator/quick_view_schema')
      ->name('generator/quick_view_schema')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'quick_view_schema']),
      // type = eg. 'ip' or 'gateway'
      // Translate either ip_lang.php or gateway_lang.php in src/Invoice/Language/English
      // using Setting google_translate_locale under Settings...View...Google Translate
      Route::methods([Method::GET, Method::POST], '/generator/google_translate_lang/{type}')
      ->name('generator/google_translate_lang')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorController::class, 'google_translate_lang']),
      Route::get('/generatorrelation')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorRelationController::class, 'index'])
      ->name('generatorrelation/index'),
      Route::methods([Method::GET, Method::POST], '/generatorrelation/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorRelationController::class, 'add'])
      ->name('generatorrelation/add'),
      Route::methods([Method::GET, Method::POST], '/generatorrelation/edit/{id}')
      ->name('generatorrelation/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorRelationController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/generatorrelation/delete/{id}')
      ->name('generatorrelation/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorRelationController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/generatorrelation/view/{id}')
      ->name('generatorrelation/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GeneratorRelationController::class, 'view']),
      Route::get('/group')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GroupController::class, 'index'])
      ->name('group/index'),
      Route::methods([Method::GET, Method::POST], '/group/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GroupController::class, 'add'])
      ->name('group/add'),
      Route::methods([Method::GET, Method::POST], '/group/edit/{id}')
      ->name('group/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GroupController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/group/delete/{id}')
      ->name('group/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GroupController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/group/view/{id}')
      ->name('group/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([GroupController::class, 'view']),
      Route::get('/inv[/page/{page:\d+}[/status/{status:\d+}]]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'index'])
      ->name('inv/index'),
      Route::methods([Method::GET, Method::POST], '/inv/pdf_dashboard_include_cf/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'pdf_dashboard_include_cf'])
      ->name('inv/pdf_dashboard_include_cf'),
      Route::methods([Method::GET, Method::POST], '/inv/pdf_dashboard_exclude_cf/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'pdf_dashboard_exclude_cf'])
      ->name('inv/pdf_dashboard_exclude_cf'),
      Route::methods([Method::GET, Method::POST], '/inv/pdf_download_include_cf/{url_key}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'pdf_download_include_cf'])
      ->name('inv/pdf_download_include_cf'),
      Route::methods([Method::GET, Method::POST], '/inv/pdf_download_exclude_cf/{url_key}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'pdf_download_exclude_cf'])
      ->name('inv/pdf_download_exclude_cf'),
      Route::methods([Method::GET, Method::POST], '/inv/peppol_stream_toggle/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'peppol_stream_toggle'])
      ->name('inv/peppol_stream_toggle'),
      Route::methods([Method::GET, Method::POST], '/archive')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'archive'])
      ->name('inv/archive'),
      Route::methods([Method::GET, Method::POST], '/download_file/{upload_id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'download_file'])
      ->name('inv/download_file'),
      Route::methods([Method::GET, Method::POST], '/inv/save_custom')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'save_custom'])
      ->name('inv/save_custom'),
      Route::methods([Method::GET, Method::POST], '/inv/save_inv_allowance_charge')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'save_inv_allowance_charge'])
      ->name('inv/save_inv_allowance_charge'),
      Route::methods([Method::GET, Method::POST], '/inv/save_inv_tax_rate')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'save_inv_tax_rate'])
      ->name('inv/save_inv_tax_rate'),
      Route::methods([Method::GET, Method::POST], '/inv/delete_inv_tax_rate/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'delete_inv_tax_rate'])
      ->name('inv/delete_inv_tax_rate'),
      Route::methods([Method::GET, Method::POST], '/inv/delete_inv_item/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'delete_inv_item'])
      ->name('inv/delete_inv_item'),
      Route::methods([Method::GET, Method::POST], '/inv/email_stage_0/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'email_stage_0'])
      ->name('inv/email_stage_0'),
      Route::methods([Method::GET, Method::POST], '/inv/email_stage_2/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'email_stage_2'])
      ->name('inv/email_stage_2'),
      Route::methods([Method::GET, Method::POST], '/inv/modal_change_client')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'modal_change_client'])
      ->name('inv/modal_change_client'),
      Route::methods([Method::GET, Method::POST], '/inv/attachment/{id}')
      ->name('inv/attachment')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'attachment']),
      Route::methods([Method::GET, Method::POST], '/inv/edit/{id}')
      ->name('inv/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/inv/peppol/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'peppol'])
      ->name('inv/peppol'),
      Route::methods([Method::GET, Method::POST], '/inv/storecove/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'storecove'])
      ->name('inv/storecove'),
      Route::methods([Method::GET, Method::POST], '/inv/test')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->action([InvController::class, 'test'])
      ->name('inv/test'),
      Route::methods([Method::GET, Method::POST], '/inv/save')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'save'])
      ->name('inv/save'),
      Route::methods([Method::GET, Method::POST], '/inv/delete/{id}')
      ->name('inv/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/inv/view/{id}')
      ->name('inv/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'view']),
      Route::methods([Method::GET, Method::POST], '/inv/generate_sumex_pdf/{inv_id}')
      ->name('inv/generate_sumex_pdf')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'generate_sumex_pdf']),
      Route::get('/client_invoices[/page/{page:\d+}[/status/{status:\d+}]]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'guest'])
      ->name('inv/guest'),
      Route::methods([Method::GET, Method::POST], '/inv/url_key/{url_key}/{gateway}')
      ->name('inv/url_key')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'url_key']),
      Route::methods([Method::GET, Method::POST], '/inv/pdf/{include}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'pdf'])
      ->name('inv/pdf'),
      Route::methods([Method::GET, Method::POST], '/inv/html/{include}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'html'])
      ->name('inv/html'),
      Route::methods([Method::GET, Method::POST], '/inv/save_inv_item')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'save_inv_item'])
      ->name('inv/save_inv_item'),
      Route::methods([Method::GET, Method::POST], '/inv/modalcreate')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'modalcreate'])
      ->name('inv/modalcreate'),
      Route::methods([Method::GET, Method::POST], '/inv/confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'confirm'])
      ->name('inv/confirm'),
      Route::methods([Method::GET, Method::POST], '/inv/create_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'create_confirm'])
      ->name('inv/create_confirm'),
      Route::methods([Method::GET, Method::POST], '/inv/create_credit_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'create_credit_confirm'])
      ->name('inv/create_credit_confirm'),
      Route::methods([Method::GET, Method::POST], '/inv/download/{invoice}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'download'])
      ->name('inv/download'),
      Route::methods([Method::GET, Method::POST], '/inv/inv_to_inv_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvController::class, 'inv_to_inv_confirm'])
      ->name('inv/inv_to_inv_confirm'),
      // InvAllowanceCharge
      // InvAllowanceCharge
      Route::get('/invallowancecharge')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvAllowanceChargeController::class, 'index'])
      ->name('invallowancecharge/index'),
      // Add
      Route::methods([Method::GET, Method::POST], '/invallowancecharge/add')
      ->middleware(Authentication::class)
      ->action([InvAllowanceChargeController::class, 'add'])
      ->name('invallowancecharge/add'),
      // Edit
      Route::methods([Method::GET, Method::POST], '/invallowancecharge/edit/{id}')
      ->name('invallowancecharge/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvAllowanceChargeController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/invallowancecharge/delete/{id}')
      ->name('invallowancecharge/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvAllowanceChargeController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/invallowancecharge/view/{id}')
      ->name('invallowancecharge/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvAllowanceChargeController::class, 'view']),
      // InvRecurring
      Route::get('/invrecurring')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvRecurringController::class, 'index'])
      ->name('invrecurring/index'),
      // Add
      Route::methods([Method::GET, Method::POST], '/invrecurring/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvRecurringController::class, 'add'])
      ->name('invrecurring/add'),
      // Create via inv.js create_recurring_confirm
      Route::methods([Method::GET, Method::POST], '/invrecurring/create_recurring_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvRecurringController::class, 'create_recurring_confirm'])
      ->name('invrecurring/create_recurring_confirm'),
      Route::methods([Method::GET, Method::POST], '/invrecurring/get_recur_start_date')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvRecurringController::class, 'get_recur_start_date'])
      ->name('invrecurring/get_recur_start_date'),
      // Edit
      Route::methods([Method::GET, Method::POST], '/invrecurring/edit/{id}')
      ->name('invrecurring/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvRecurringController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/invrecurring/delete/{id}')
      ->name('invrecurring/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvRecurringController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/invrecurring/stop/{id}')
      ->name('invrecurring/stop')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvRecurringController::class, 'stop']),
      Route::methods([Method::GET, Method::POST], '/invrecurring/view/{id}')
      ->name('invrecurring/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvRecurringController::class, 'view']),
      Route::get('/invamount')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvAmountController::class, 'index'])
      ->name('invamount/index'),
      Route::methods([Method::GET, Method::POST], '/invamount/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvAmountController::class, 'add'])
      ->name('invamount/add'),
      Route::methods([Method::GET, Method::POST], '/invamount/edit/{id}')
      ->name('invamount/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvAmountController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/invamount/delete/{id}')
      ->name('invamount/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvAmountController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/invamount/view/{id}')
      ->name('invamount/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvAmountController::class, 'view']),
      Route::get('/invcustom')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvCustomController::class, 'index'])
      ->name('invcustom/index'),
      Route::methods([Method::GET, Method::POST], '/invcustom/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvCustomController::class, 'add'])
      ->name('invcustom/add'),
      Route::methods([Method::GET, Method::POST], '/invcustom/edit/{id}')
      ->name('invcustom/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvCustomController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/invcustom/delete/{id}')
      ->name('invcustom/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvCustomController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/invcustom/view/{id}')
      ->name('invcustom/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvCustomController::class, 'view']),
      Route::methods([Method::POST], '/invitem/add_product')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemController::class, 'add_product'])
      ->name('invitem/add_product'),
      Route::methods([Method::POST], '/invitem/add_task')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemController::class, 'add_task'])
      ->name('invitem/add_task'),
      Route::methods([Method::GET, Method::POST], '/invitem/edit_product/{id}')
      ->name('invitem/edit_product')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemController::class, 'edit_product']),
      Route::methods([Method::GET, Method::POST], '/invitem/edit_task/{id}')
      ->name('invitem/edit_task')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemController::class, 'edit_task']),
      Route::methods([Method::GET, Method::POST], '/invitem/delete/{id}')
      ->name('invitem/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/invitem/multiple')
      ->name('invitem/multiple')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([InvItemController::class, 'multiple']),
      Route::methods([Method::GET, Method::POST], '/invitem/view/{id}')
      ->name('invitem/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([InvItemController::class, 'view']),
      Route::get('/itemlookup')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([ItemLookupController::class, 'index'])
      ->name('itemlookup/index'),
      Route::methods([Method::GET, Method::POST], '/itemlookup/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ItemLookupController::class, 'add'])
      ->name('itemlookup/add'),
      Route::methods([Method::GET, Method::POST], '/itemlookup/edit/{id}')
      ->name('itemlookup/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ItemLookupController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/itemlookup/delete/{id}')
      ->name('itemlookup/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ItemLookupController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/itemlookup/view/{id}')
      ->name('itemlookup/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ItemLookupController::class, 'view']),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/amazon_complete/{url_key}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'amazon_complete'])
      ->name('paymentinformation/amazon_complete'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/braintree_complete/{url_key}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'braintree_complete'])
      ->name('paymentinformation/braintree_complete'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/stripe_complete/{url_key}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'stripe_complete'])
      ->name('paymentinformation/stripe_complete'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/stripe_incomplete/{url_key}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'stripe_incomplete'])
      ->name('paymentinformation/stripe_incomplete'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/make_payment_stripe_pci/{url_key}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'make_payment_stripe_pci'])
      ->name('paymentinformation/make_payment_stripe_pci'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/make_payment_amazon_pci/{url_key}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'make_payment_amazon_pci'])
      ->name('paymentinformation/make_payment_amazon_pci'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/make_payment_omnipay/{url_key}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'make_payment_omnipay'])
      ->name('paymentinformation/make_payment_omnipay'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/fetch')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'fetch'])
      ->name('paymentinformation/fetch'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/form/{url_key}/{gateway}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'form'])
      ->name('paymentinformation/form'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/omnnipay_payment_return/{url_key}/{driver}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'omnipay_payment_return'])
      ->name('paymentinformation/omnipay_payment_return'),
      Route::methods([Method::GET, Method::POST], '/paymentinformation/omnnipay_payment_cancel/{url_key}/{driver}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentInformationController::class, 'omnipay_payment_cancel'])
      ->name('paymentinformation/omnipay_payment_cancel'),
      Route::get('/paymentpeppol')
      ->middleware(Authentication::class)
      ->action([PaymentPeppolController::class, 'index'])
      ->name('paymentpeppol/index'),    
      // Add
      Route::methods([Method::GET, Method::POST], '/paymentpeppol/add')
      ->middleware(Authentication::class)
      ->action([PaymentPeppolController::class, 'add'])
      ->name('paymentpeppol/add'),
      // Edit 
      Route::methods([Method::GET, Method::POST], '/paymentpeppol/edit/{id}')
      ->name('paymentpeppol/edit')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentPeppolController::class, 'edit']), 
      Route::methods([Method::GET, Method::POST], '/paymentpeppol/delete/{id}')
      ->name('paymentpeppol/delete')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentPeppolController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/paymentpeppol/view/{id}')
      ->name('paymentpeppol/view')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentPeppolController::class, 'view']),
      // PostalAddress
      Route::get('/postaladdress')
      ->middleware(Authentication::class)
      ->action([PostalAddressController::class, 'index'])
      ->name('postaladdress/index'),
      // Add
      Route::methods([Method::GET, Method::POST], '/postaladdress/add/{client_id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PostalAddressController::class, 'add'])
      ->name('postaladdress/add'),
      // Edit
      Route::methods([Method::GET, Method::POST], '/postaladdress/edit/{id}')
      ->name('postaladdress/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PostalAddressController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/postaladdress/delete/{id}')
      ->name('postaladdress/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PostalAddressController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/postaladdress/view/{id}')
      ->name('postaladdress/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PostalAddressController::class, 'view']),
      Route::get('/product[/page/{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductController::class, 'index'])
      ->name('product/index'),
      Route::methods([Method::GET, Method::POST], '/product/test')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->middleware(FormatDataResponseAsJson::class)
      ->action([ProductController::class, 'test'])
      ->name('product/test'),
      Route::methods([Method::GET, Method::POST], '/product/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductController::class, 'add'])
      ->name('product/add'),
      Route::methods([Method::GET, Method::POST], '/product/lookup')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductController::class, 'lookup'])
      ->name('product/lookup'),
      Route::get('/product/selection_quote')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->action([ProductController::class, 'selection_quote'])
      ->name('product/selection_quote'),
      Route::get('/product/selection_inv')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->action([ProductController::class, 'selection_inv'])
      ->name('product/selection_inv'),
      Route::methods([Method::GET, Method::POST], '/product/edit/{id}')
      ->name('product/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/product/delete/{id}')
      ->name('product/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/product/view/{id}')
      ->name('product/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductController::class, 'view']),
      Route::methods([Method::GET, Method::POST], '/image/{product_image_id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductController::class, 'download_image_file'])
      ->name('product/download_image_file'),
      Route::methods([Method::GET, Method::POST], '/image_attachment/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductController::class, 'image_attachment'])
      ->name('product/image_attachment'),
      Route::get('/productimage')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductImageController::class, 'index'])
      ->name('productimage/index'),
      Route::methods([Method::GET, Method::POST], '/image/add')
      ->middleware(Authentication::class)
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->action([ProductImageController::class, 'add'])
      ->name('productimage/add'),
      Route::methods([Method::GET, Method::POST], '/image/edit/{id}')
      ->name('productimage/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductImageController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/image/delete/{id}')
      ->name('productimage/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductImageController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/image/view/{id}')
      ->name('productimage/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductImageController::class, 'view']),
      // ProductProperty
      Route::get('/productproperty')
      ->middleware(Authentication::class)
      ->action([ProductPropertyController::class, 'index'])
      ->name('productproperty/index'),    
      // Add
      Route::methods([Method::GET, Method::POST], '/productproperty/add/{product_id}')
      ->middleware(Authentication::class)
      ->action([ProductPropertyController::class, 'add'])
      ->name('productproperty/add'),
      // Edit 
      Route::methods([Method::GET, Method::POST], '/productproperty/edit/{id}')
      ->name('productproperty/edit')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductPropertyController::class, 'edit']), 
      Route::methods([Method::GET, Method::POST], '/productproperty/delete/{id}')
      ->name('productproperty/delete')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductPropertyController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/productproperty/view/{id}')
      ->name('productproperty/view')
      ->middleware(fn (AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProductPropertyController::class, 'view']),
      Route::get('/profile')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProfileController::class, 'index'])
      ->name('profile/index'),
      Route::methods([Method::GET, Method::POST], '/profile/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProfileController::class, 'add'])
      ->name('profile/add'),
      Route::methods([Method::GET, Method::POST], '/profile/edit/{id}')
      ->name('profile/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProfileController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/profile/delete/{id}')
      ->name('profile/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProfileController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/profile/view/{id}')
      ->name('profile/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProfileController::class, 'view']),
      Route::get('/project[/page/{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProjectController::class, 'index'])
      ->name('project/index'),
      Route::methods([Method::GET, Method::POST], '/project/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProjectController::class, 'add'])
      ->name('project/add'),
      Route::methods([Method::GET, Method::POST], '/project/edit/{id}')
      ->name('project/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProjectController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/project/delete/{id}')
      ->name('project/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProjectController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/project/view/{id}')
      ->name('project/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ProjectController::class, 'view']),
      Route::get('/client_salesorders[/page/{page:\d+}[/status/{status:\d+}]]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'guest'])
      ->name('salesorder/guest'),
      Route::get('/salesorder[/page/{page:\d+}[/status/{status:\d+}]]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'index'])
      ->name('salesorder/index'),
      Route::methods([Method::GET, Method::POST], '/salesorder/add')
      ->name('salesorder/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'add']),
      Route::methods([Method::GET, Method::POST], '/salesorder/agree_to_terms/{url_key}')
      ->name('salesorder/agree_to_terms')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'agree_to_terms']),
      Route::methods([Method::GET, Method::POST], '/salesorder/edit/{id}')
      ->name('salesorder/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/salesorder/delete/{id}')
      ->name('salesorder/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/salesorder/pdf/{include}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'pdf'])
      ->name('salesorder/pdf'),
      Route::methods([Method::GET, Method::POST], '/salesorder/reject/{url_key}')
      ->name('salesorder/reject')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'reject']),
      Route::methods([Method::GET, Method::POST], '/salesorder/view/{id}')
      ->name('salesorder/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'view']),
      Route::methods([Method::GET, Method::POST], '/salesorder/so_to_invoice_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'so_to_invoice_confirm'])
      ->name('salesorder/so_to_invoice_confirm'),
      Route::methods([Method::GET, Method::POST], '/salesorder/url_key/{key}')
      ->name('salesorder/url_key')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderController::class, 'url_key']),
      Route::methods([Method::GET, Method::POST], '/salesorderitem/edit/{id}')
      ->name('salesorderitem/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([SalesOrderItemController::class, 'edit']),
      Route::get('/setting/debug_index[/page{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'debug_index'])
      ->name('setting/debug_index'),
      Route::methods([Method::GET, Method::POST], '/setting/save')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'save'])
      ->name('setting/save'),
      Route::methods([Method::GET, Method::POST], '/setting/tab_index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'tab_index'])
      ->name('setting/tab_index'),
      Route::methods([Method::GET, Method::POST], '/setting/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'add'])
      ->name('setting/add'),
      Route::methods([Method::GET, Method::POST], '/setting/draft/{setting_id}')
      ->name('setting/draft')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'inv_draft_has_number_switch']),
      Route::methods([Method::GET, Method::POST], '/setting/edit/{setting_id}')
      ->name('setting/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/setting/delete/{setting_id}')
      ->name('setting/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/setting/index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'index'])
      ->name('setting/index'),
      Route::methods([Method::GET, Method::POST], '/setting/get_cron_key')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'get_cron_key'])
      ->name('setting/get_cron_key'),
      Route::methods([Method::GET, Method::POST], '/setting/view/{setting_id}')
      ->name('setting/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'view']),
      Route::methods([Method::GET, Method::POST], '/setting/clear')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SettingController::class, 'clear'])
      ->name('setting/clear'),
      Route::get('/task[/page/{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaskController::class, 'index'])
      ->name('task/index'),
      Route::methods([Method::GET, Method::POST], '/task/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaskController::class, 'add'])
      ->name('task/add'),
      Route::methods([Method::GET, Method::POST], '/task/selection_inv')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaskController::class, 'selection_inv'])
      ->name('task/selection_inv'),
      Route::methods([Method::GET, Method::POST], '/task/edit/{id}')
      ->name('task/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaskController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/task/delete/{id}')
      ->name('task/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaskController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/task/view/{id}')
      ->name('task/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaskController::class, 'view']),
      Route::get('/taxrate')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaxRateController::class, 'index'])
      ->name('taxrate/index'),
      Route::methods([Method::GET, Method::POST], '/taxrate/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaxRateController::class, 'add'])
      ->name('taxrate/add'),
      Route::methods([Method::GET, Method::POST], '/taxrate/edit/{tax_rate_id}')
      ->name('taxrate/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaxRateController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/taxrate/delete/{tax_rate_id}')
      ->name('taxrate/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaxRateController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/taxrate/view/{tax_rate_id}')
      ->name('taxrate/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([TaxRateController::class, 'view']),
      Route::get('/unit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UnitController::class, 'index'])
      ->name('unit/index'),
      Route::methods([Method::GET, Method::POST], '/unit/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UnitController::class, 'add'])
      ->name('unit/add'),
      Route::methods([Method::GET, Method::POST], '/unit/edit/{id}')
      ->name('unit/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UnitController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/unit/delete/{id}')
      ->name('unit/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UnitController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/unit/view/{id}')
      ->name('unit/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UnitController::class, 'view']),
      // UnitPeppol
      Route::get('/unitpeppol')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UnitPeppolController::class, 'index'])
      ->name('unitpeppol/index'),
      // Add
      Route::methods([Method::GET, Method::POST], '/unitpeppol/add')
      ->middleware(Authentication::class)
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->action([UnitPeppolController::class, 'add'])
      ->name('unitpeppol/add'),
      // Edit
      Route::methods([Method::GET, Method::POST], '/unitpeppol/edit/{id}')
      ->name('unitpeppol/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UnitPeppolController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/unitpeppol/delete/{id}')
      ->name('unitpeppol/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UnitPeppolController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/unitpeppol/view/{id}')
      ->name('unitpeppol/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UnitPeppolController::class, 'view']),
      Route::get('/import')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ImportController::class, 'index'])
      ->name('import/index'),
      Route::methods([Method::GET, Method::POST], '/import/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ImportController::class, 'add'])
      ->name('import/add'),
      Route::methods([Method::GET, Method::POST], '/import/edit/{id}')
      ->name('import/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ImportController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/import/delete/{id}')
      ->name('import/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ImportController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/import/view/{id}')
      ->name('import/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ImportController::class, 'view']),
      Route::get('/merchant')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([MerchantController::class, 'index'])
      ->name('merchant/index'),
      Route::methods([Method::GET, Method::POST], '/merchant/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([MerchantController::class, 'add'])
      ->name('merchant/add'),
      Route::methods([Method::GET, Method::POST], '/merchant/edit/{id}')
      ->name('merchant/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([MerchantController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/merchant/delete/{id}')
      ->name('merchant/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([MerchantController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/merchant/view/{id}')
      ->name('merchant/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([MerchantController::class, 'view']),
      Route::methods([Method::GET, Method::POST], '/payment/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentController::class, 'add'])
      ->name('payment/add'),
      Route::methods([Method::GET, Method::POST], '/payment/edit/{id}')
      ->name('payment/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/payment/delete/{id}')
      ->name('payment/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/payment/view/{id}')
      ->name('payment/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentController::class, 'view']),
      Route::get('/payment[/page/{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentController::class, 'index'])
      ->name('payment/index'),
      Route::get('/user_client_payments[/page/{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentController::class, 'guest'])
      ->name('payment/guest'),
      Route::get('/online_log[/page/{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentController::class, 'online_log'])
      ->name('payment/online_log'),
      Route::get('/guest_online_log[/page/{page:\d+}]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewPayment'))
      ->middleware(Authentication::class)
      ->action([PaymentController::class, 'guest_online_log'])
      ->name('payment/guest_online_log'),
      Route::get('/paymentmethod')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentMethodController::class, 'index'])
      ->name('paymentmethod/index'),
      Route::methods([Method::GET, Method::POST], '/paymentmethod/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentMethodController::class, 'add'])
      ->name('paymentmethod/add'),
      Route::methods([Method::GET, Method::POST], '/paymentmethod/edit/{id}')
      ->name('paymentmethod/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentMethodController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/paymentmethod/delete/{id}')
      ->name('paymentmethod/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentMethodController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/paymentmethod/view/{id}')
      ->name('paymentmethod/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentMethodController::class, 'view']),
      Route::get('/paymentcustom')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentCustomController::class, 'index'])
      ->name('paymentcustom/index'),
      Route::methods([Method::GET, Method::POST], '/paymentcustom/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentCustomController::class, 'add'])
      ->name('paymentcustom/add'),
      Route::methods([Method::GET, Method::POST], '/paymentcustom/edit/{id}')
      ->name('paymentcustom/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentCustomController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/paymentcustom/delete/{id}')
      ->name('paymentcustom/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentCustomController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/paymentcustom/view/{id}')
      ->name('paymentcustom/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentCustomController::class, 'view']),
      Route::get('/paymentterm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentTermController::class, 'index'])
      ->name('paymentterm/index'),
      Route::methods([Method::GET, Method::POST], '/paymentterm/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentTermController::class, 'add'])
      ->name('paymentterm/add'),
      Route::methods([Method::GET, Method::POST], '/paymentterm/edit/{id}')
      ->name('paymentterm/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentTermController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/paymentterm/delete/{id}')
      ->name('paymentterm/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentTermController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/paymentterm/view/{id}')
      ->name('paymentterm/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([PaymentTermController::class, 'view']),
      Route::methods([Method::GET, Method::POST], '/quote/email_stage_0/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'email_stage_0'])
      ->name('quote/email_stage_0'),
      Route::methods([Method::GET, Method::POST], '/quote/email_stage_2/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'email_stage_2'])
      ->name('quote/email_stage_2'),
      Route::get('/quote[/page/{page:\d+}[/status/{status:\d+}]]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'index'])
      ->name('quote/index'),
      Route::get('/client_quotes[/page/{page:\d+}[/status/{status:\d+}]]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'guest'])
      ->name('quote/guest'),
      Route::methods([Method::GET, Method::POST], '/quote/save_custom')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'save_custom'])
      ->name('quote/save_custom'),
      Route::methods([Method::GET, Method::POST], '/quote/save_quote_tax_rate')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'save_quote_tax_rate'])
      ->name('quote/save_quote_tax_rate'),
      Route::methods([Method::GET, Method::POST], '/quote/delete_quote_tax_rate/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'delete_quote_tax_rate'])
      ->name('quote/delete_quote_tax_rate'),
      Route::methods([Method::GET, Method::POST], '/quote/delete_quote_item/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'delete_quote_item'])
      ->name('quote/delete_quote_item'),
      Route::methods([Method::GET, Method::POST], '/quote/pdf/{include}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'pdf'])
      ->name('quote/pdf'),
      Route::methods([Method::GET, Method::POST], '/quote/pdf_dashboard_include_cf/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'pdf_dashboard_include_cf'])
      ->name('quote/pdf_dashboard_include_cf'),
      Route::methods([Method::GET, Method::POST], '/quote/pdf_dashboard_exclude_cf/{id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'pdf_dashboard_exclude_cf'])
      ->name('quote/pdf_dashboard_exclude_cf'),
      Route::methods([Method::GET, Method::POST], '/quote/save_quote_item')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'save_quote_item'])
      ->name('quote/save_quote_item'),
      Route::methods([Method::GET, Method::POST], '/quote/modalcreate')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'modalcreate'])
      ->name('quote/modalcreate'),
      Route::methods([Method::GET, Method::POST], '/quote/confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'confirm'])
      ->name('quote/confirm'),
      Route::methods([Method::GET, Method::POST], '/quote/create_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'create_confirm'])
      ->name('quote/create_confirm'),
      Route::methods([Method::GET, Method::POST], '/quote/quote_to_invoice_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'quote_to_invoice_confirm'])
      ->name('quote/quote_to_invoice_confirm'),
      // The client is responsible for issuing the Purchase Order
      Route::methods([Method::GET, Method::POST], '/quote/quote_to_so_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'quote_to_so_confirm'])
      ->name('quote/quote_to_so_confirm'),
      Route::methods([Method::GET, Method::POST], '/quote/quote_to_quote_confirm')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'quote_to_quote_confirm'])
      ->name('quote/quote_to_quote_confirm'),
      Route::methods([Method::GET, Method::POST], '/quote/modal_change_client')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'modal_change_client'])
      ->name('quote/modal_change_client'),
      Route::methods([Method::GET, Method::POST], '/quote/edit/{id}')
      ->name('quote/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/quote/test')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->action([QuoteController::class, 'test'])
      ->name('quote/test'),
      Route::methods([Method::GET, Method::POST], '/quote/save')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'save'])
      ->name('quote/save'),
      Route::methods([Method::GET, Method::POST], '/quote/delete/{id}')
      ->name('quote/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/quote/view/{id}')
      ->name('quote/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'view']),
      // The individual must have been give the url on the email sent and also
      // have been assigned the observer role under resources/rbac/items by using
      // assignRole command at command prompt
      Route::methods([Method::GET, Method::POST], '/quote/url_key/{url_key}')
      ->name('quote/url_key')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'url_key']),
      // The individual that is sent the quote approves with/without a purchase order number
      Route::methods([Method::GET, Method::POST], '/quote/approve')
      ->name('quote/approve')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'approve']),
      // The individual that is sent the quote rejects it.
      Route::methods([Method::GET, Method::POST], '/quote/reject/{url_key}')
      ->name('quote/reject')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'reject']),
      Route::methods([Method::GET, Method::POST], '/quote/generate_quote_pdf/{url_key}')
      ->name('quote/generate_quote_pdf')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteController::class, 'generate_quote_pdf']),
      Route::get('/quoteamount')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteAmountController::class, 'index'])
      ->name('quoteamount/index'),
      Route::methods([Method::GET, Method::POST], '/quoteamount/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteAmountController::class, 'add'])
      ->name('quoteamount/add'),
      Route::methods([Method::GET, Method::POST], '/quoteamount/edit/{id}')
      ->name('quoteamount/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteAmountController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/quoteamount/delete/{id}')
      ->name('quoteamount/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteAmountController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/quoteamount/view/{id}')
      ->name('quoteamount/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteAmountController::class, 'view']),
      Route::get('/quotecustom')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([QuoteCustomController::class, 'index'])
      ->name('quotecustom/index'),
      Route::methods([Method::GET, Method::POST], '/quotecustom/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteCustomController::class, 'add'])
      ->name('quotecustom/add'),
      Route::methods([Method::GET, Method::POST], '/quotecustom/edit/{id}')
      ->name('quotecustom/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteCustomController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/quotecustom/delete/{id}')
      ->name('quotecustom/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteCustomController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/quotecustom/view/{id}')
      ->name('quotecustom/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteCustomController::class, 'view']),
      Route::get('/quoteitem')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemController::class, 'index'])
      ->name('quoteitem/index'),
      Route::methods([Method::POST], '/quoteitem/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemController::class, 'add'])
      ->name('quoteitem/add'),
      Route::methods([Method::GET, Method::POST], '/quoteitem/edit/{id}')
      ->name('quoteitem/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/quoteitem/delete/{id}')
      ->name('quoteitem/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/quoteitem/multiple')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemController::class, 'multiple'])
      ->name('quoteitem/delete_multiple'),
      Route::methods([Method::GET, Method::POST], '/quoteitem/view/{id}')
      ->name('quoteitem/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemController::class, 'view']),
      Route::get('/quoteitemamount')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemAmountController::class, 'index'])
      ->name('quoteitemamount/index'),
      Route::methods([Method::GET, Method::POST], '/quoteitemamount/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemAmountController::class, 'add'])
      ->name('quoteitemamount/add'),
      Route::methods([Method::GET, Method::POST], '/quoteitemamount/edit/{id}')
      ->name('quoteitemamount/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemAmountController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/quoteitemamount/delete/{id}')
      ->name('quoteitemamount/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemAmountController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/quoteitemamount/view/{id}')
      ->name('quoteitemamount/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([QuoteItemAmountController::class, 'view']),
      Route::get('/report')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ReportController::class, 'index'])
      ->name('report/index'),
      Route::methods([Method::GET, Method::POST], '/sales_by_client_index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ReportController::class, 'sales_by_client_index'])
      ->name('report/sales_by_client_index'),
      Route::methods([Method::GET, Method::POST], '/sales_by_product_index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ReportController::class, 'sales_by_product_index'])
      ->name('report/sales_by_product_index'),
      Route::methods([Method::GET, Method::POST], '/sales_by_task_index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ReportController::class, 'sales_by_task_index'])
      ->name('report/sales_by_task_index'),
      Route::methods([Method::GET, Method::POST], '/sales_by_year_index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ReportController::class, 'sales_by_year_index'])
      ->name('report/sales_by_year_index'),
      Route::methods([Method::GET, Method::POST], '/invoice_aging_index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ReportController::class, 'invoice_aging_index'])
      ->name('report/invoice_aging_index'),
      Route::methods([Method::GET, Method::POST], '/payment_history_index')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ReportController::class, 'payment_history_index'])
      ->name('report/payment_history_index'),
      Route::methods([Method::GET, Method::POST], '/sales_by_year')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([ReportController::class, 'sales_by_year'])
      ->name('report/sales_by_year'),
      Route::get('/sumex')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SumexController::class, 'index'])
      ->name('sumex/index'),
      Route::methods([Method::GET, Method::POST], '/sumex/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SumexController::class, 'add'])
      ->name('sumex/add'),
      Route::methods([Method::GET, Method::POST], '/sumex/delete/{id}')
      ->name('sumex/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SumexController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/sumex/edit/{invoice}')
      ->name('sumex/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SumexController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/sumex/view/{id}')
      ->name('sumex/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([SumexController::class, 'view']),
      Route::get('/upload')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UploadController::class, 'index'])
      ->name('upload/index'),
      Route::methods([Method::GET, Method::POST], '/upload/add')
      ->middleware(Authentication::class)
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->action([UploadController::class, 'add'])
      ->name('upload/add'),
      Route::methods([Method::GET, Method::POST], '/upload/edit/{id}')
      ->name('upload/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UploadController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/upload/delete/{id}')
      ->name('upload/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UploadController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/upload/view/{id}')
      ->name('upload/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UploadController::class, 'view']),
      // UserClient
      Route::get('/userclient')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('viewInv'))
      ->middleware(Authentication::class)
      ->action([UserClientController::class, 'index'])
      ->name('userclient/index'),
      // Add
      Route::methods([Method::GET, Method::POST], '/userclient/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserClientController::class, 'add'])
      ->name('userclient/add'),
      Route::methods([Method::GET, Method::POST], '/userclient/new/{user_id}')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserClientController::class, 'new'])
      ->name('userclient/new'),
      // Edit
      Route::methods([Method::GET, Method::POST], '/userclient/edit/{id}')
      ->name('userclient/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserClientController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/userclient/delete/{id}')
      ->name('userclient/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserClientController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/userclient/view/{id}')
      ->name('userclient/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserClientController::class, 'view']),
      // UserInv
      Route::get('/userinv[/page/{page:\d+}[/active/{active}]]')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'index'])
      ->name('userinv/index'),
      // Add
      Route::methods([Method::GET, Method::POST], '/userinv/add')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'add'])
      ->name('userinv/add'),
      // Edit
      Route::methods([Method::GET, Method::POST], '/userinv/edit/{id}')
      ->name('userinv/edit')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'edit']),
      Route::methods([Method::GET, Method::POST], '/userinv/guest')
      ->name('userinv/guest')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editUserInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'guest']),
      Route::methods([Method::GET, Method::POST], '/userinv/client/{id}')
      ->name('userinv/client')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'client']),
      Route::methods([Method::GET, Method::POST], '/userinv/delete/{id}')
      ->name('userinv/delete')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'delete']),
      Route::methods([Method::GET, Method::POST], '/userinv/view/{id}')
      ->name('userinv/view')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'view']),
      Route::methods([Method::GET, Method::POST], '/userinv/accountant/{user_id}')
      ->name('userinv/accountant')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'assignAccountantRole']),
      Route::methods([Method::GET, Method::POST], '/userinv/observer/{user_id}')
      ->name('userinv/observer')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'assignObserverRole']),
      Route::methods([Method::GET, Method::POST], '/userinv/admin/{user_id}')
      ->name('userinv/admin')
      ->middleware(fn(AccessChecker $checker) => $checker->withPermission('editInv'))
      ->middleware(Authentication::class)
      ->action([UserInvController::class, 'assignAdminRole']),
    ), //invoice
];
