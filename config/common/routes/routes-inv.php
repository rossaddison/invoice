<?php

declare(strict_types=1);

use App\Auth\Permissions;
use App\Invoice\Inv\InvController;
use App\Middleware\RoutePermission;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

return [
    RoutePermission::invoiceGroup(
Route::methods([Method::GET, Method::POST], '/inv/pdfDashboardIncludeCf/{id}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'pdfDashboardIncludeCf'])
                ->name('inv/pdfDashboardIncludeCf'),

            Route::methods([Method::GET, Method::POST], '/inv/pdfDashboardExcludeCf/{id}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'pdfDashboardExcludeCf'])
                ->name('inv/pdfDashboardExcludeCf'),

            Route::methods([Method::GET, Method::POST], '/inv/pdfDownloadIncludeCf/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'pdfDownloadIncludeCf'])
                ->name('inv/pdfDownloadIncludeCf'),

            Route::methods([Method::GET, Method::POST], '/inv/pdfDownloadExcludeCf/{url_key}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'pdfDownloadExcludeCf'])
                ->name('inv/pdfDownloadExcludeCf'),

            Route::methods([Method::GET, Method::POST], '/download_file/{upload_id}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'downloadFile'])
                ->name('inv/downloadFile'),

            // Because the inv/view is accessible to the observer and the admin
            // the inv/view function is further refined with rbac
            Route::methods([Method::GET, Method::POST], '/inv/view/{id}')
                ->name('inv/view')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'view']),

            // id acquired by session
            Route::get('/client_invoices[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'guest'])
                ->name('inv/guest'),

            // Lets a signed-in guest print their own home-care QR code
            Route::get('/client_invoices/qr')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'guestQrCode'])
                ->name('inv/guest/qr'),

            Route::methods([Method::GET, Method::POST], '/inv/urlKey/{url_key}/{gateway}')
                ->name('inv/urlKey')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'urlKey']),

            // id acquired by session
            Route::methods([Method::GET, Method::POST], '/inv/pdf/{include}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'pdf'])
                ->name('inv/pdf'),

            // {invoice} is a complete string
            Route::methods([Method::GET, Method::POST], '/inv/download/{invoice}')
                ->middleware(RoutePermission::check(Permissions::VIEW_INV))
                ->action([InvController::class, 'download'])
                ->name('inv/download'),

            Route::get('/inv[/page/{page:\d+}[/status/{status:\d+}]]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'index'])
                ->name('inv/index'),

            Route::get('/inv/[/status/{status:\d+}]')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'indexMark'])
                ->name('inv/indexmark'),

            Route::methods([Method::GET, Method::POST], '/inv/peppolStreamToggle/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'peppolStreamToggle'])
                ->name('inv/peppolStreamToggle'),

            Route::methods([Method::GET, Method::POST], '/inv/peppolDocCurrencyToggle/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'peppolDocCurrencyToggle'])
                ->name('inv/peppolDocCurrencyToggle'),

            Route::methods([Method::GET, Method::POST], '/inv/peppolSend/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'peppolSend'])
                ->name('inv/peppolSend'),

            Route::methods([Method::GET, Method::POST], '/archive')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'archive'])
                ->name('inv/archive'),

            Route::methods([Method::GET, Method::POST], '/inv/saveCustom')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'saveCustom'])
                ->name('inv/saveCustom'),

            Route::methods([Method::GET, Method::POST], '/inv/saveInvAllowanceCharge')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'saveInvAllowanceCharge'])
                ->name('inv/saveInvAllowanceCharge'),

            Route::methods([Method::GET, Method::POST], '/inv/saveInvTaxRate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'saveInvTaxRate'])
                ->name('inv/saveInvTaxRate'),

            Route::methods([Method::GET, Method::POST], '/inv/deleteInvTaxRate/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'deleteInvTaxRate'])
                ->name('inv/deleteInvTaxRate'),

            Route::methods([Method::GET, Method::POST], '/inv/deleteInvItem/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'deleteInvItem'])
                ->name('inv/deleteInvItem'),

            Route::methods([Method::GET, Method::POST], '/inv/emailStage0/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'emailStage0'])
                ->name('inv/emailStage0'),

            Route::methods([Method::GET, Method::POST], '/inv/emailStage2/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'emailStage2'])
                ->name('inv/emailStage2'),

            Route::methods([Method::GET, Method::POST], '/inv/markAsSent')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'markAsSent'])
                ->name('inv/markAsSent'),

            Route::methods([Method::GET, Method::POST], '/inv/markSentAsDraft')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'markSentAsDraft'])
                ->name('inv/markSentAsDraft'),

            Route::methods([Method::GET, Method::POST], '/inv/setworker/{inv_id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'setWorker'])
                ->name('inv/setworker'),

            Route::methods([Method::GET], '/inv/batchEmailPreview')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'batchEmailPreview'])
                ->name('inv/batchEmailPreview'),

            Route::methods([Method::GET], '/inv/batchEmail')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'batchEmail'])
                ->name('inv/batchEmail'),

            Route::methods([Method::GET, Method::POST], '/inv/modalChangeClient')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'modalChangeClient'])
                ->name('inv/modalChangeClient'),

            Route::methods([Method::GET, Method::POST], '/inv/attachment/{id}')
                ->name('inv/attachment')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'attachment']),

            Route::methods([Method::GET, Method::POST], '/inv/edit/{id}')
                ->name('inv/edit')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'edit']),

            Route::methods([Method::GET, Method::POST], '/inv/flush')
                ->name('inv/flush')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'flush']),

            Route::methods([Method::GET, Method::POST], '/inv/peppol/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'peppol'])
                ->name('inv/peppol'),

            Route::methods([Method::GET, Method::POST], '/inv/storecove/{id}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'storecove'])
                ->name('inv/storecove'),

            Route::methods([Method::GET, Method::POST], '/inv/test')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'test'])
                ->name('inv/test'),

            Route::methods([Method::GET, Method::POST], '/inv/save')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'save'])
                ->name('inv/save'),

            Route::methods([Method::GET, Method::POST], '/inv/delete/{id}')
                ->name('inv/delete')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'delete']),

            Route::get('/inv/trash')
                ->name('inv/trash')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'trash']),

            Route::methods([Method::GET, Method::POST], '/inv/restore/{id}')
                ->name('inv/restore')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'restore']),

            Route::methods([Method::GET, Method::POST], '/inv/html/{include}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'html'])
                ->name('inv/html'),

            Route::methods([Method::GET, Method::POST], '/inv/saveInvItem')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'saveInvItem'])
                ->name('inv/saveInvItem'),

            Route::methods([Method::GET, Method::POST], '/inv/modalcreate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'modalcreate'])
                ->name('inv/modalcreate'),

            Route::methods([Method::GET, Method::POST], '/inv/multiplecopy')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'multiplecopy'])
                ->name('inv/multiplecopy'),

            Route::methods([Method::GET, Method::POST], '/inv/multiplecopyspreadsheet')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'multiplecopyspreadsheet'])
                ->name('inv/multiplecopyspreadsheet'),

            Route::methods([Method::GET, Method::POST], '/inv/copyalltodate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'copyAllToDate'])
                ->name('inv/copyalltodate'),

            Route::methods([Method::GET], '/inv/copycsvtemplate')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'csvTemplateInvCopy'])
                ->name('inv/copycsvtemplate'),

            Route::methods([Method::GET], '/inv/bulkquickpay')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'bulkquickpay'])
                ->name('inv/bulkquickpay'),

            Route::methods([Method::GET], '/inv/quickpayform')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'quickpayform'])
                ->name('inv/quickpayform'),

            Route::methods([Method::GET], '/inv/quickpay')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'quickpay'])
                ->name('inv/quickpay'),

            Route::methods([Method::GET, Method::POST], '/inv/confirm')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'confirm'])
                ->name('inv/confirm'),

            Route::methods([Method::GET, Method::POST], '/inv/add/{origin}')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'add'])
                ->name('inv/add'),

            Route::methods([Method::GET, Method::POST], '/inv/createConfirm')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'createConfirm'])
                ->name('inv/createConfirm'),

            Route::methods([Method::GET, Method::POST], '/inv/createCreditConfirm')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'createCreditConfirm'])
                ->name('inv/createCreditConfirm'),

            Route::methods([Method::GET, Method::POST], '/inv/invToInvConfirm')
                ->middleware(RoutePermission::check(Permissions::EDIT_INV))
                ->action([InvController::class, 'invToInvConfirm'])
                ->name('inv/invToInvConfirm'),
        ), // invoice
];
