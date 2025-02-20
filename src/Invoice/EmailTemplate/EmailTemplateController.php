<?php

declare(strict_types=1);

namespace App\Invoice\EmailTemplate;

use App\Invoice\Entity\EmailTemplate;
use App\Invoice\FromDropDown\FromDropDownRepository;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\DataResponseFactoryInterface as Factory;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Json\Json;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class EmailTemplateController
{
    use FlashMessage;
    private Flash $flash;
    private ViewRenderer $viewRenderer;

    public function __construct(
        private SessionInterface $session,
        ViewRenderer $viewRenderer,
        private WebControllerService $webService,
        private EmailTemplateService $emailtemplateService,
        private UserService $userService,
        private TranslatorInterface $translator,
        private Factory $factory
    ) {
        $this->flash = new Flash($this->session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/emailtemplate')
                                           ->withLayout('@views/layout/invoice.php');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param EmailTemplateRepository $emailtemplateRepository
     * @param SettingRepository $settingRepository
     */
    public function index(CurrentRoute $currentRoute, EmailTemplateRepository $emailtemplateRepository, SettingRepository $settingRepository): \Yiisoft\DataResponse\DataResponse
    {
        $page = (int)$currentRoute->getArgument('page', '1');
        /** @psalm-var positive-int $currentPageNeverZero */
        $currentPageNeverZero = $page > 0 ? $page : 1;
        $this->rbac();
        $parameters = [
            'paginator' => (new OffsetPaginator($this->emailtemplates($emailtemplateRepository)))
                            ->withPageSize($settingRepository->positiveListLimit())
                            ->withCurrentPage($currentPageNeverZero),
            'alert' => $this->alert(),
            'email_templates' => $this->emailtemplates($emailtemplateRepository),
        ];
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param CustomFieldRepository $customfieldRepository
     * @param FromDropDownRepository $fromR
     * @return Response
     */
    public function add_invoice(
        Request $request,
        FormHydrator $formHydrator,
        SettingRepository $settingRepository,
        CustomFieldRepository $customfieldRepository,
        FromDropDownRepository $fromR
    ): Response {
        $email_template = new EmailTemplate();
        $form = new EmailTemplateForm($email_template);
        $parameters = [
            'actionName' => 'emailtemplate/add_invoice',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'email_template_tags' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags-with-inv', [
                'template_tags_inv' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags-inv', [
                    'custom_fields_inv_custom' => $customfieldRepository->repoTablequery('inv_custom'),
                ]),
                'custom_fields' => [
                    'client_custom' => $customfieldRepository->repoTablequery('client_custom'),
                ],
            ]),
            //Email templates can be built for either a quote or an invoice.
            'invoiceTemplates' => $settingRepository->get_invoice_templates('pdf'),
            // see src\Invoice\Asset\rebuild-1.13\js\mailer_ajax_email_addresses
            'admin_email' => $settingRepository->getConfigAdminEmail(),
            'sender_email' => $settingRepository->getConfigSenderEmail(),
            'from_email' => $fromR->getDefault()?->getEmail() ?? $this->translator->translate('invoice.email.default.none.set'),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (null !== $this->userService->getUser() && $formHydrator->populateAndValidate($form, $body)) {
                if (is_array($body)) {
                    $this->emailtemplateService->saveEmailTemplate(new EmailTemplate(), $body);
                    $this->flashMessage('info', $this->translator->translate('invoice.email.template.successfully.added'));
                    return $this->webService->getRedirectResponse('emailtemplate/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form_invoice', $parameters, );
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param CustomFieldRepository $customfieldRepository
     * @param FromDropDownRepository $fromR
     * @return Response
     */
    public function add_quote(
        Request $request,
        FormHydrator $formHydrator,
        SettingRepository $settingRepository,
        CustomFieldRepository $customfieldRepository,
        FromDropDownRepository $fromR
    ): Response {
        $email_template = new EmailTemplate();
        $form = new EmailTemplateForm($email_template);
        $parameters = [
            'actionName' => 'emailtemplate/add_quote',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'email_template_tags' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags-with-quote', [
                'template_tags_quote' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags-quote', [
                    'custom_fields_quote_custom' => $customfieldRepository->repoTablequery('quote_custom'),
                ]),
                'custom_fields' => [
                    'client_custom' => $customfieldRepository->repoTablequery('client_custom'),
                ],
            ]),
            'quoteTemplates' => $settingRepository->get_quote_templates('pdf'),
            // see src\Invoice\Asset\rebuild-1.13\js\mailer_ajax_email_addresses
            'admin_email' => $settingRepository->getConfigAdminEmail(),
            'sender_email' => $settingRepository->getConfigSenderEmail(),
            'from_email' => $fromR->getDefault()?->getEmail() ?? $this->translator->translate('invoice.email.default.none.set'),
        ];

        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (null !== $this->userService->getUser() && $formHydrator->populateAndValidate($form, $body)) {
                if (is_array($body)) {
                    $this->emailtemplateService->saveEmailTemplate(new EmailTemplate(), $body);
                    $this->flashMessage('info', $this->translator->translate('invoice.email.template.successfully.added'));
                    return $this->webService->getRedirectResponse('emailtemplate/index');
                }
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('_form_quote', $parameters, );
    }

    /**
     * @return string
     */
    private function alert(): string
    {
        return $this->viewRenderer->renderPartialAsString(
            '//invoice/layout/alert',
            [
                'flash' => $this->flash,
            ]
        );
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param EmailTemplateRepository $emailtemplateRepository
     * @param CustomFieldRepository $customfieldRepository
     * @param SettingRepository $settingRepository
     * @param FromDropDownRepository $fromR
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit_invoice(
        CurrentRoute $currentRoute,
        Request $request,
        EmailTemplateRepository $emailtemplateRepository,
        CustomFieldRepository $customfieldRepository,
        SettingRepository $settingRepository,
        FromDropDownRepository $fromR,
        FormHydrator $formHydrator,
    ): Response {
        $email_template = $this->emailtemplate($currentRoute, $emailtemplateRepository);
        if ($email_template) {
            $form = new EmailTemplateForm($email_template);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'emailtemplate/edit_invoice',
                'actionArguments' => ['email_template_id' => $email_template->getEmail_template_id()],
                'errors' => [],
                'email_template' => $email_template,
                'form' => $form,
                'aliases' => new Aliases(['@invoice' => dirname(__DIR__), '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language']),
                'email_template_tags' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags-with-inv', [
                    'template_tags_inv' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags-inv', [
                        'custom_fields_inv_custom' => $customfieldRepository->repoTablequery('inv_custom'),
                    ]),
                    'custom_fields' => [
                        'client_custom' => $customfieldRepository->repoTablequery('client_custom'),
                    ],
                ]),
                'invoiceTemplates' => $settingRepository->get_invoice_templates('pdf'),
                'selected_pdf_template' => $email_template->getEmail_template_pdf_template(),
                // see src\Invoice\Asset\rebuild-1.13\js\mailer_ajax_email_addresses
                'admin_email' => $settingRepository->getConfigAdminEmail(),
                'sender_email' => $settingRepository->getConfigSenderEmail(),
                'from_email' => ($fromR->getDefault()?->getEmail() ?? $this->translator->translate('invoice.email.default.none.set')),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->emailtemplateService->saveEmailTemplate($email_template, $body);
                        $this->flashMessage('info', $this->translator->translate('invoice.email.template.successfully.edited'));
                        return $this->webService->getRedirectResponse('emailtemplate/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form_invoice', $parameters);
        }
        return $this->webService->getRedirectResponse('emailtemplate/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param Request $request
     * @param EmailTemplateRepository $emailtemplateRepository
     * @param CustomFieldRepository $customfieldRepository
     * @param SettingRepository $settingRepository
     * @param FromDropDownRepository $fromR
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit_quote(
        CurrentRoute $currentRoute,
        Request $request,
        EmailTemplateRepository $emailtemplateRepository,
        CustomFieldRepository $customfieldRepository,
        SettingRepository $settingRepository,
        FromDropDownRepository $fromR,
        FormHydrator $formHydrator,
    ): Response {
        $email_template = $this->emailtemplate($currentRoute, $emailtemplateRepository);
        if ($email_template) {
            $form = new EmailTemplateForm($email_template);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'actionName' => 'emailtemplate/edit_quote',
                'actionArguments' => ['email_template_id' => $email_template->getEmail_template_id()],
                'errors' => [],
                'email_template' => $email_template,
                'form' => $form,
                'aliases' => new Aliases(['@invoice' => dirname(__DIR__), '@language' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Language']),
                'email_template_tags' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags-with-quote', [
                    'template_tags_quote' => $this->viewRenderer->renderPartialAsString('//invoice/emailtemplate/template-tags-quote', [
                        'custom_fields_quote_custom' => $customfieldRepository->repoTablequery('quote_custom'),
                    ]),
                    'custom_fields' => [
                        'client_custom' => $customfieldRepository->repoTablequery('client_custom'),
                    ],
                ]),
                'quoteTemplates' => $settingRepository->get_quote_templates('pdf'),
                'selected_pdf_template' => $email_template->getEmail_template_pdf_template(),
                // see src\Invoice\Asset\rebuild-1.13\js\mailer_ajax_email_addresses
                'admin_email' => $settingRepository->getConfigAdminEmail(),
                'sender_email' => $settingRepository->getConfigSenderEmail(),
                'from_email' => ($fromR->getDefault()?->getEmail() ?? $this->translator->translate('invoice.email.default.none.set')),
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if ($formHydrator->populateFromPostAndValidate($form, $request)) {
                    if (is_array($body)) {
                        $this->emailtemplateService->saveEmailTemplate($email_template, $body);
                        $this->flashMessage('info', $this->translator->translate('invoice.email.template.successfully.edited'));
                        return $this->webService->getRedirectResponse('emailtemplate/index');
                    }
                }
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('_form_quote', $parameters);
        }
        return $this->webService->getRedirectResponse('emailtemplate/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param EmailTemplateRepository $emailtemplateRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        EmailTemplateRepository $emailtemplateRepository
    ): Response {
        $email_template = $this->emailtemplate($currentRoute, $emailtemplateRepository);
        if ($email_template) {
            $this->emailtemplateService->deleteEmailTemplate($email_template);
            $this->flashMessage('info', $this->translator->translate('invoice.email.template.successfully.deleted'));
            return $this->webService->getRedirectResponse('emailtemplate/index');
        }
        return $this->webService->getRedirectResponse('emailtemplate/index');
    }

    /**
     * @param Request $request
     * @param EmailTemplateRepository $etR
     */
    public function get_content(Request $request, EmailTemplateRepository $etR): \Yiisoft\DataResponse\DataResponse
    {
        //views/invoice/inv/mailer_invoice'
        $get_content = $request->getQueryParams();
        /** @var int $get_content['email_template_id'] */
        $email_template_id = $get_content['email_template_id'];
        $email_template = $etR->repoEmailTemplateCount((string)$email_template_id) > 0 ? $etR->repoEmailTemplatequery((string)$email_template_id) : null;
        return $this->factory->createResponse(Json::htmlEncode($email_template ?
            ['email_template' => [
                'email_template_body' => $email_template->getEmail_template_body(),
                'email_template_subject' => $email_template->getEmail_template_subject(),
                'email_template_from_name' => $email_template->getEmail_template_from_name(),
                'email_template_from_email' => $email_template->getEmail_template_from_email(),
                'email_template_cc' => $email_template->getEmail_template_cc() ?? '',
                'email_template_bcc' => $email_template->getEmail_template_bcc() ?? '',
                'email_template_pdf_template' => $email_template->getEmail_template_pdf_template() ?? '',
            ],
                'success' => 1]
            :
            ['success' => 0]));
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param EmailTemplateRepository $emailtemplateRepository
     */
    public function preview(CurrentRoute $currentRoute, EmailTemplateRepository $emailtemplateRepository): Response
    {
        $email_template = $this->emailtemplate($currentRoute, $emailtemplateRepository);
        if ($email_template) {
            $form = new EmailTemplateForm($email_template);
            $parameters = [
                'title' => $this->translator->translate('i.preview'),
                'actionName' => 'emailtemplate/preview',
                'actionArguments' => ['email_template_id' => $email_template->getEmail_template_id()],
                'errors' => [],
                'emailtemplate' => $email_template,
                'form' => $form,
            ];
            return $this->viewRenderer->render('_pre_view', $parameters);
        }
        return $this->webService->getRedirectResponse('emailtemplate/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param EmailTemplateRepository $emailtemplateRepository
     */
    public function view(CurrentRoute $currentRoute, EmailTemplateRepository $emailtemplateRepository): Response
    {
        $email_template = $this->emailtemplate($currentRoute, $emailtemplateRepository);
        if ($email_template) {
            $form = new EmailTemplateForm($email_template);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'actionName' => 'emailtemplate/view',
                'actionArguments' => ['email_template_id' => $email_template->getEmail_template_id()],
                'errors' => [],
                'emailtemplate' => $email_template,
                'form' => $form,
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('emailtemplate/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('emailtemplate/index');
        }
        return $canEdit;
    }

    private function emailtemplate(CurrentRoute $currentRoute, EmailTemplateRepository $emailtemplateRepository): EmailTemplate|null
    {
        $email_template_id = $currentRoute->getArgument('email_template_id');
        if (null !== $email_template_id) {
            return $emailtemplateRepository->repoEmailTemplatequery($email_template_id);
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function emailtemplates(EmailTemplateRepository $emailtemplateRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $emailtemplateRepository->findAllPreloaded();
    }
}
