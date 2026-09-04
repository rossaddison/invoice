<?php

declare(strict_types=1);

namespace App\Invoice\CompanyPrivate;

use App\Auth\Permissions;
use App\Invoice\BaseController;
use App\Invoice\Company\CompanyRepository;
use App\Infrastructure\Persistence\CompanyPrivate\CompanyPrivate;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Widget\CompanyPrivateFormFields;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Security\Random;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Rule\File;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class CompanyPrivateController extends BaseController
{
    protected string $controllerName = 'invoice/companyprivate';

    /** @var list<string> */
    private const array ALLOWED_LOGO_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png', 'bmp', 'tiff'];
    /** @var list<string> */
    private const array ALLOWED_LOGO_MIME_TYPES = ['image/gif', 'image/jpeg', 'image/png', 'image/bmp', 'image/tiff'];
    private const int MAX_LOGO_UPLOAD_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private CompanyPrivateService $companyPrivateService,
        private CompanyPrivateFormFields $formFields,
        private ValidatorInterface $validator,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
        $this->companyPrivateService = $companyPrivateService;
    }

    /**
     * @param CompanyPrivateRepository $companyprivateRepository
     */
    public function index(CompanyPrivateRepository $companyprivateRepository): \Psr\Http\Message\ResponseInterface
    {
        $canEdit = $this->rbac();
        $paginator = new OffsetPaginator($this->companyprivates($companyprivateRepository));
        $parameters = [
            'canEdit' => $canEdit,
            'paginator' => $paginator,
            'alert' => $this->alert(),
        ];

        return $this->webViewRenderer->render('index', $parameters);
    }

    /**
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function add(
        Request $request,
        FormHydrator $formHydrator,
        CompanyRepository $companyRepository,
    ): Response {
        $company_private = new CompanyPrivate();
        $form = new CompanyPrivateForm();
        $body = $request->getParsedBody() ?? [];
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'companyprivate/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'formFields' => $this->formFields,
            'companies' => $companyRepository->findAllPreloaded(),
            'company_public' => $this->translator->translate('company.public'),
        ];
        $aliases = $this->sR->getCompanyPrivateLogosFolderAliases();
        $targetPath = $aliases->get('@company_private_logos');
        $targetPublicPath = $aliases->get('@public_logo');
        if (!is_writable($targetPath)) {
            $this->flashMessage('warning', $this->translator->translate('is.not.writable'));
            return $this->webService->getRedirectResponse('companyprivate/index');
        }
        if ($request->getMethod() === Method::POST) {
            /** @var UploadedFileInterface|null $file */
            $file = $request->getUploadedFiles()['logo_filename'] ?? null;
            if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
                throw new CompanyPrivateException('No file uploaded or temporary file missing.');
            }

            if ($file->getError() !== UPLOAD_ERR_OK) {
                throw new CompanyPrivateException('File upload error: ' . $file->getError());
            }

            if (!$this->validateLogoFile($file)) {
                throw new CompanyPrivateException('Uploaded logo failed validation.');
            }

            $originalFileName = basename((string) $file->getClientFilename()); // Extract original file name
            $spaceToUnderscore = preg_replace('/\s+/', '_', $originalFileName); // Replace spaces with underscores

            if ($spaceToUnderscore !== null && is_array($body)) {
                $body['logo_filename'] = $this->processLogoFileUpload(
                    $file,
                    $targetPath,
                    $targetPublicPath,
                    $spaceToUnderscore
                );
                if ($formHydrator->populateAndValidate($form, $body)) {
                    $this->companyPrivateService->saveCompanyPrivate($company_private, $body);
                    $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
                    return $this->webService->getRedirectResponse('companyprivate/index');
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
        }
        return $this->webViewRenderer->render('_form', $parameters);
    }

    private function processLogoFileUpload(
        UploadedFileInterface $file,
        string $targetPath,
        string $targetPublicPath,
        string $spaceToUnderscore,
    ): string {
        $fileInfo = pathinfo($spaceToUnderscore);
        $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
        $modified_original_file_name = Random::string(4) . '_' . $fileInfo['filename'] . $extension;
        $target_file_name = $targetPath . '/' . $modified_original_file_name;
        $target_public_logo = $targetPublicPath . '/' . $modified_original_file_name;
        try {
            $file->moveTo($target_file_name);
        } catch (RuntimeException $e) {
            throw new CompanyPrivateException('Failed to move uploaded file.', 0, $e);
        }
        if (!copy($target_file_name, $target_public_logo)) {
            throw new CompanyPrivateException('Failed to copy file to public folder.');
        }
        return $modified_original_file_name;
    }

    /**
     * Whitelists extension, MIME type (sniffed from the real upload contents), and size.
     */
    private function validateLogoFile(UploadedFileInterface $file): bool
    {
        $result = $this->validator->validate($file, [
            new File(
                extensions: self::ALLOWED_LOGO_EXTENSIONS,
                mimeTypes: self::ALLOWED_LOGO_MIME_TYPES,
                maxSize: self::MAX_LOGO_UPLOAD_BYTES,
            ),
        ]);
        return $result->isValid();
    }

    /**
     * Moves a freshly uploaded logo into place, refusing to overwrite an existing target.
     *
     * @return bool True if there was an error and no move took place.
     */
    public function fileUploadingErrors(
        UploadedFileInterface $file,
        string $target_file_name,
        string $target_public_logo,
    ): bool {
        if (file_exists($target_file_name) || file_exists($target_public_logo)) {
            return true;
        }
        try {
            $file->moveTo($target_file_name);
        } catch (RuntimeException) {
            return true;
        }
        return !copy($target_file_name, $target_public_logo);
    }

    /**
     * A new file upload must pass whitelist validation, then replace the
     * previous one, or keep the existing file if there's no valid new upload.
     */
    private function resolveLogoFilename(
        bool $hasNewFile,
        ?UploadedFileInterface $file,
        string $targetFileName,
        string $targetPublicLogo,
        string $modifiedOriginalFileName,
        string $existingLogoFilename,
    ): string {
        $isValidNewUpload = $hasNewFile
            && $file instanceof UploadedFileInterface
            && $this->validateLogoFile($file)
            && !$this->fileUploadingErrors($file, $targetFileName, $targetPublicLogo);

        return $isValidNewUpload ? $modifiedOriginalFileName : $existingLogoFilename;
    }

    /**
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param CompanyPrivateRepository $companyprivateRepository
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function edit(
        Request $request,
        CurrentRoute $currentRoute,
        FormHydrator $formHydrator,
        CompanyPrivateRepository $companyprivateRepository,
        CompanyRepository $companyRepository,
    ): Response {
        $company_private = $this->companyprivate($currentRoute, $companyprivateRepository);
        if (!$company_private) {
            return $this->webService->getRedirectResponse('companyprivate/index');
        }
        $form = CompanyPrivateForm::show($company_private);
        $parameters = [
            'title' => $this->translator->translate('edit'),
            'actionName' => 'companyprivate/edit',
            'actionArguments' => ['id' => $company_private->reqId()],
            'errors' => [],
            'form' => $form,
            'formFields' => $this->formFields,
            'companies' => $companyRepository->findAllPreloaded(),
            'company_public' => $this->translator->translate('setting.company'),
        ];
        $aliases = $this->sR->getCompanyPrivateLogosFolderAliases();
        $targetPath = $aliases->get('@company_private_logos');
        $targetPublicPath = $aliases->get('@public_logo');
        if (!is_writable($targetPath)) {
            $this->flashMessage('warning', $this->translator->translate('is.not.writable'));
            return $this->webService->getRedirectResponse('companyprivate/index');
        }
        $redirect = null;
        if ($request->getMethod() === Method::POST) {
            /** @var array $body */
            $body = $request->getParsedBody() ?? [];
            // the filename before it was changed
            $existing_logo_filename = (string) ($body['existing_logo_filename'] ?? ($company_private->getLogoFilename() ?? ''));
            // the file that has just been selected, if any
            /** @var UploadedFileInterface|null $file */
            $file = $request->getUploadedFiles()['logo_filename'] ?? null;
            $hasNewFile = $file instanceof UploadedFileInterface && $file->getError() !== UPLOAD_ERR_NO_FILE;
            $body['logo_filename'] = $hasNewFile
            ? (string) $file->getClientFilename()
            : $existing_logo_filename;
            if ($formHydrator->populateAndValidate($form, $body)) {
                // Replace filename's spaces with underscore and add random string preventing overwrites
                $modified_original_file_name = Random::string(4) . '_' . (string) preg_replace('/\s+/', '_', $body['logo_filename']);
                // Build a unique target file name
                $target_file_name = $targetPath . '/' . $modified_original_file_name;
                $target_public_logo = $targetPublicPath . '/' . $modified_original_file_name;
                // Save the body including the logo_filename field
                $this->companyPrivateService->saveCompanyPrivate($company_private, $body);

                // Prepare the after save for the logo_filename field
                $after_save =
                    $companyprivateRepository->repoCompanyPrivatequery(
                        $company_private->reqId()
                    );
                if ($after_save) {
                    // A new file upload must pass whitelist validation, then replace the
                    // previous one, or keep the existing file if there's no valid new upload.
                    $after_save->setLogoFilename(
                        $this->resolveLogoFilename(
                            $hasNewFile,
                            $file,
                            $target_file_name,
                            $target_public_logo,
                            $modified_original_file_name,
                            $existing_logo_filename,
                        ),
                    );
                    $companyprivateRepository->save($after_save);

                    $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                    $redirect = $this->webService->getRedirectResponse('companyprivate/index');
                } // after  save
            }
            $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
        }
        return $redirect ?? $this->webViewRenderer->render('_form', $parameters);
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CompanyPrivateRepository $companyprivateRepository
     * @return Response
     */
    public function delete(
        CurrentRoute $currentRoute,
        CompanyPrivateRepository $companyprivateRepository,
    ): Response {
        $company_private = $this->companyprivate($currentRoute, $companyprivateRepository);
        if ($company_private) {
            $logo = $company_private->getLogoFilename();
            if (isset($logo) && !empty($logo)) {
                $aliases = $this->sR->getCompanyPrivateLogosFolderAliases();
                $targetPath = $aliases->get('@company_private_logos');
                $targetPublicPath = $aliases->get('@public_logo');
                $target_file_name = $targetPath . DIRECTORY_SEPARATOR . $logo;
                unlink($target_file_name);
                $target_public_logo = $targetPublicPath . DIRECTORY_SEPARATOR . $logo;
                unlink($target_public_logo);
                $this->companyPrivateService->deleteCompanyPrivate($company_private);
                $this->flashMessage('info', $this->translator->translate('record.successfully.deleted'));
                return $this->webService->getRedirectResponse('companyprivate/index');
            }
        }
        return $this->webService->getRedirectResponse('companyprivate/index');
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CompanyPrivateRepository $companyprivateRepository
     * @param CompanyRepository $companyRepository
     */
    public function view(
        CurrentRoute $currentRoute,
        CompanyPrivateRepository $companyprivateRepository,
        CompanyRepository $companyRepository,
    ): Response {
        $company_private = $this->companyprivate($currentRoute, $companyprivateRepository);
        if ($company_private) {
            $form = CompanyPrivateForm::show($company_private);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'companyprivate/view',
                'actionArguments' => ['id' => $company_private->reqId()],
                'form' => $form,
                'formFields' => $this->formFields,
                'companies' => $companyRepository->findAllPreloaded(),
                'companyprivate' => $company_private,
                'company_public' => $this->translator->translate('company.public'),
            ];
            return $this->webViewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('companyprivate/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission(Permissions::EDIT_INV);
        if (!$canEdit) {
            $this->flashMessage('warning', $this->translator->translate('permission'));
            return $this->webService->getRedirectResponse('companyprivate/index');
        }
        return $canEdit;
    }

    /**
     * @param CurrentRoute $currentRoute
     * @param CompanyPrivateRepository $companyprivateRepository
     * @return CompanyPrivate|null
     */
    private function companyprivate(
        CurrentRoute $currentRoute,
        CompanyPrivateRepository $companyprivateRepository
    ): ?CompanyPrivate {
        $id = (int) $currentRoute->getArgument('id');
        return $companyprivateRepository->repoCompanyPrivatequery($id);
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function companyprivates(CompanyPrivateRepository $companyprivateRepository): \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        return $companyprivateRepository->findAllPreloaded();
    }
}
