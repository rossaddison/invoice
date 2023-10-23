<?php

declare(strict_types=1); 

namespace App\Invoice\Profile;

use App\Invoice\Company\CompanyRepository;
use App\Invoice\Entity\Profile;
use App\Invoice\Profile\ProfileService;
use App\Invoice\Profile\ProfileRepository;
use App\Invoice\Setting\SettingRepository;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Form\FormHydrator;
use Yiisoft\Form\Helper\HtmlFormErrors;
use Yiisoft\Yii\View\ViewRenderer;

final class ProfileController
{
    private Flash $flash;
    private Session $session;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private UserService $userService;
    private ProfileService $profileService;
    private TranslatorInterface $translator;
        
    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        UserService $userService,
        ProfileService $profileService,
        TranslatorInterface $translator
    )    
    {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/profile')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->userService = $userService;
        $this->profileService = $profileService;
        $this->translator = $translator;
    }
    
    /**
     * @param ProfileRepository $profileRepository
     * @param SettingRepository $settingRepository
     */
    public function index(ProfileRepository $profileRepository, SettingRepository $settingRepository): \Yiisoft\DataResponse\DataResponse
    {      
         $canEdit = $this->rbac();
         $this->flash_message('info', $this->translator->translate('invoice.profile.new'));
         $parameters = [
          's'=>$settingRepository,
          'canEdit' => $canEdit,
          'profiles' => $this->profiles($profileRepository),
          'alert'=> $this->alert()
         ];
        
        return $this->viewRenderer->render('index', $parameters);
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param FormHydrator $formHydrator
     * @param SettingRepository $settingRepository
     * @param CompanyRepository $companyRepository
     * @return Response
     */    
    public function add(ViewRenderer $head, Request $request, 
                        FormHydrator $formHydrator,
                        SettingRepository $settingRepository,                        
                        CompanyRepository $companyRepository
    ): Response
    {
        $parameters = [
            'title' => $this->translator->translate('invoice.add'),
            'action' => ['profile/add'],
            'errors' => [],
            'body' => $request->getParsedBody(),
            's'=>$settingRepository,
            'head'=>$head,            
            'companies'=>$companyRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            
            $form = new ProfileForm();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->profileService->saveProfile(new Profile(),$form);
                return $this->webService->getRedirectResponse('profile/index');
            }
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @return string
     */
    private function alert(): string {
      return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
      [ 
         'flash' => $this->flash,
         'errors' => [],
      ]);
    }
    
    /**
     * @param ViewRenderer $head
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param FormHydrator $formHydrator
     * @param ProfileRepository $profileRepository
     * @param SettingRepository $settingRepository
     * @param CompanyRepository $companyRepository
     * @return Response
     */
    public function edit(ViewRenderer $head, Request $request, CurrentRoute $currentRoute,
                        FormHydrator $formHydrator,
                        ProfileRepository $profileRepository, 
                        SettingRepository $settingRepository,                        
                        CompanyRepository $companyRepository
    ): Response {
        $profile = $this->profile($currentRoute, $profileRepository);
        if ($profile) {
        $parameters = [
            'title' => $settingRepository->trans('edit'),
            'action' => ['profile/edit', ['id' => $profile->getId()]],
            'errors' => [],
            'body' => $this->body($profile),
            'head'=>$head,
            's'=>$settingRepository,
            'companies'=>$companyRepository->findAllPreloaded()
        ];
        if ($request->getMethod() === Method::POST) {
            $form = new ProfileForm();
            $body = $request->getParsedBody();
            if ($formHydrator->populate($form, $parameters['body']) && $form->isValid()) {
                $this->profileService->saveProfile($profile, $form);
                return $this->webService->getRedirectResponse('profile/index');
            }
            $parameters['body'] = $body;
            $parameters['errors'] = HtmlFormErrors::getFirstErrors($form);
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    return $this->webService->getRedirectResponse('profile/index');    
}
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProfileRepository $profileRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, ProfileRepository $profileRepository 
    ): Response {
        try {
            $profile = $this->profile($currentRoute, $profileRepository);
            if ($profile) {
                if ($this->profileService->deleteProfile($profile)) {               
                    $this->flash_message('info', $this->translator->translate('invoice.profile.deleted'));
                } else {
                    $this->flash_message('info', $this->translator->translate('invoice.profile.not.deleted'));
                }    
            }
            return $this->webService->getRedirectResponse('profile/index');
	} catch (\Exception $e) {
            unset($e);
            $this->flash_message('danger', $this->translator->translate('invoice.profile.history'));
            return $this->webService->getRedirectResponse('profile/index'); 
        }
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProfileRepository $profileRepository
     * @param SettingRepository $settingRepository
     */
    public function view(CurrentRoute $currentRoute, ProfileRepository $profileRepository,
        SettingRepository $settingRepository,
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $profile = $this->profile($currentRoute, $profileRepository);
        if ($profile) {
            $parameters = [
                'title' => $settingRepository->trans('view'),
                'action' => ['profile/view', ['id' => $profile->getId()]],
                'errors' => [],
                'body' => $this->body($profile),
                's'=> $settingRepository,             
                'profile'=>$profileRepository->repoProfilequery((string)$profile->getId()),
            ];
            return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('profile/index'); 
    }
        
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response 
    {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('profile/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param ProfileRepository $profileRepository
     * @return Profile|null
     */
    private function profile(CurrentRoute $currentRoute, ProfileRepository $profileRepository): Profile|null
    {
        $id = $currentRoute->getArgument('id');       
        if (null!==$id) {
            $profile = $profileRepository->repoProfilequery($id);
            return $profile;
        }
        return null;
    }
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function profiles(ProfileRepository $profileRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
    {
        $profiles = $profileRepository->findAllPreloaded();        
        return $profiles;
    }
    
    /**
     * 
     * @param Profile $profile
     * @return array
     */
    private function body(Profile $profile): array {
        $body = [                
          'id'=>$profile->getId(),
          'company_id'=>$profile->getCompany_id(),
          'current'=>$profile->getCurrent(),
          'mobile'=>$profile->getMobile(),
          'email'=>$profile->getEmail(),
          'description'=>$profile->getDescription(),
          'date_created'=>$profile->getDate_created(),
          'date_modified'=>$profile->getDate_modified()
        ];
        return $body;
    }
    
    /**
     * @param string $level
     * @param string $message
     * @return Flash
     */
    private function flash_message(string $level, string $message): Flash {
      $this->flash->add($level, $message, true);
      return $this->flash;
    }
}