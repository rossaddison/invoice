<?php

declare(strict_types=1);

namespace App\Invoice\CompanyPrivate;

use App\Invoice\BaseController;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Entity\CompanyPrivate;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use App\User\UserService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Security\Random;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;
use RuntimeException;

final class CompanyPrivateController extends BaseController
{
    protected string $controllerName = 'invoice/companyprivate';

    public function __construct(
        private CompanyPrivateService $companyPrivateService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $viewRenderer, $session, $sR, $flash);
        $this->companyPrivateService = $companyPrivateService;
    }

    /**
     * @param CompanyPrivateRepository $companyprivateRepository
     */
    public function index(CompanyPrivateRepository $companyprivateRepository): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $paginator = new OffsetPaginator($this->companyprivates($companyprivateRepository));
        $parameters = [
            'canEdit' => $canEdit,
            'company_private' => $this->translator->translate('setting.company.private'),
            'paginator' => $paginator,
            'alert' => $this->alert(),
        ];

        return $this->viewRenderer->render('index', $parameters);
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
        $form = new CompanyPrivateForm($company_private);
        $body = $request->getParsedBody() ?? [];
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'companyprivate/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'companies' => $companyRepository->findAllPreloaded(),
            'company_public' => $this->translator->translate('company.public'),
        ];
        $aliases = $this->sR->get_company_private_logos_folder_aliases();
        $targetPath = $aliases->get('@company_private_logos');
        $targetPublicPath = $aliases->get('@public_logo');
        if (!is_writable($targetPath)) {
            $this->flashMessage('warning', $this->translator->translate('is.not.writable'));
            return $this->webService->getRedirectResponse('companyprivate/index');
        }
        if ($request->getMethod() === Method::POST) {
            $logoFileName = $_FILES['logo_filename']['name'];
            if (!isset($_FILES['logo_filename']['tmp_name']) || empty($_FILES['logo_filename']['tmp_name'])) {
                throw new RuntimeException('No file uploaded or temporary file missing.');
            }

            if ($_FILES['logo_filename']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('File upload error: ' . $_FILES['logo_filename']['error']);
            }

            $tmp = $_FILES['logo_filename']['tmp_name'];
            $originalFileName = basename($logoFileName); // Extract original file name
            $spaceToUnderscore = preg_replace('/\s+/', '_', $originalFileName); // Replace spaces with underscores

            if (null !== $spaceToUnderscore) {
                // Handle filename and extension
                $fileInfo = pathinfo($spaceToUnderscore);
                $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
                $modified_original_file_name = Random::string(4) . '_' . $fileInfo['filename'] . $extension;

                // Build target paths
                $target_file_name = $targetPath . '/' . $modified_original_file_name;
                $target_public_logo = $targetPublicPath . '/' . $modified_original_file_name;

                if (is_array($body)) {
                    $body['logo_filename'] = $modified_original_file_name;

                    // Move the uploaded file
                    if (!move_uploaded_file($tmp, $target_file_name)) {
                        throw new RuntimeException('Failed to move uploaded file.');
                    }

                    // Copy the file to the public folder
                    if (!copy($target_file_name, $target_public_logo)) {
                        throw new RuntimeException('Failed to copy file to public folder.');
                    }

                    // Process form data
                    if ($formHydrator->populateAndValidate($form, $body)) {
                        $this->companyPrivateService->saveCompanyPrivate($company_private, $body, $this->sR);
                        $this->flashMessage('info', $this->translator->translate('record.successfully.created'));
                        return $this->webService->getRedirectResponse('companyprivate/index');
                    }
                    $parameters['form'] = $form;
                    $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
                }
            }
        }
        return $this->viewRenderer->render('_form', $parameters);
    }

    /**
     * @param string $tmp
     * @param string $target_file_name
     * @param string $target_public_logo
     * @return bool
     */
    public function file_uploading_errors(
        string $tmp,
        string $target_file_name,
        string $target_public_logo,
    ): bool {
        $return = true;
        if (is_uploaded_file($tmp)) {
            $return = false;
        } else {
            return true;
        }
        if (!file_exists($target_file_name)) {
            $return = false;
        } else {
            return true;
        }
        if (!file_exists($target_public_logo)) {
            $return = false;
        } else {
            return true;
        }
        if (move_uploaded_file($tmp, $target_file_name)) {
            $return = false;
        } else {
            return true;
        }
        if (copy($target_file_name, $target_public_logo)) {
            $return = false;
        } else {
            return true;
        }
        return $return;
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
        if ($company_private) {
            $form = new CompanyPrivateForm($company_private);
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'companyprivate/edit',
                'actionArguments' => ['id' => $company_private->getId()],
                'errors' => [],
                'form' => $form,
                'companies' => $companyRepository->findAllPreloaded(),
                'company_public' => $this->translator->translate('setting.company'),
            ];
            $aliases = $this->sR->get_company_private_logos_folder_aliases();
            $targetPath = $aliases->get('@company_private_logos');
            $targetPublicPath = $aliases->get('@public_logo');
            if (!is_writable($targetPath)) {
                $this->flashMessage('warning', $this->translator->translate('is.not.writable'));
                return $this->webService->getRedirectResponse('companyprivate/index');
            }
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                // the filename before it was changed
                $existing_logo_filename = (string) ($body['existing_logo_filename'] ?? ($company_private->getLogo_filename() ?? ''));
                // the file that has just been selected
                /**
                 * @var array $_FILES['logo_filename']
                 * @var array $body
                 */
                $body['logo_filename'] = !empty((string) $_FILES['logo_filename']['name'])
                ? (string) $_FILES['logo_filename']['name']
                : $existing_logo_filename;
                if ($formHydrator->populateAndValidate($form, $body)) {
                    // Replace filename's spaces with underscore and add random string preventing overwrites
                    $modified_original_file_name = Random::string(4) . '_' . (string) preg_replace('/\s+/', '_', $body['logo_filename']);
                    // Build a unique target file name
                    $target_file_name = $targetPath . '/' . $modified_original_file_name;
                    $target_public_logo = $targetPublicPath . '/' . $modified_original_file_name;
                    // Save the body including the logo_filename field
                    $this->companyPrivateService->saveCompanyPrivate($company_private, $body, $this->sR);

                    // Prepare the after save for the logo_filename field
                    $after_save = $companyprivateRepository->repoCompanyPrivatequery((string) $company_private->getId());
                    if ($after_save) {
                        // A new file upload must replace the previous one or keep existing file
                        /**
                         * @var array $_FILES['logo_filename']
                         * @var string $_FILES['logo_filename']['tmp_name']
                         */
                        $tmp_name = $_FILES['logo_filename']['tmp_name'];
                        $after_save->setLogo_filename(
                            // 1. tmp is an uploaded file and not a security risk
                            // 2. the target file name does not exist
                            // 3. tmp has been moved into the target destination
                            !$this->file_uploading_errors($tmp_name, $target_file_name, $target_public_logo)

                            // New file upload
                            ? $modified_original_file_name

                            // or Existing database file name
                            : $existing_logo_filename,
                        );
                        $companyprivateRepository->save($after_save);

                        $this->flashMessage('info', $this->translator->translate('record.successfully.updated'));
                        return $this->webService->getRedirectResponse('companyprivate/index');
                    } // after  save
                }
                $parameters['form'] = $form;
                $parameters['errors'] = $form->getValidationResult()->getErrorMessagesIndexedByProperty();
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('companyprivate/index');
    }

    /**
     * @param SessionInterface $session
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
            $logo = $company_private->getLogo_filename();
            if (isset($logo) && !empty($logo)) {
                $aliases = $this->sR->get_company_private_logos_folder_aliases();
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
            $form = new CompanyPrivateForm($company_private);
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'companyprivate/view',
                'actionArguments' => ['id' => $company_private->getId()],
                'form' => $form,
                'companies' => $companyRepository->findAllPreloaded(),
                'companyprivate' => $company_private,
                'company_public' => $this->translator->translate('company.public'),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('companyprivate/index');
    }

    /**
     * @return Response|true
     */
    private function rbac(): bool|Response
    {
        $canEdit = $this->userService->hasPermission('editInv');
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
    private function companyprivate(CurrentRoute $currentRoute, CompanyPrivateRepository $companyprivateRepository): CompanyPrivate|null
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $companyprivateRepository->repoCompanyPrivatequery($id);
        }
        return null;
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
