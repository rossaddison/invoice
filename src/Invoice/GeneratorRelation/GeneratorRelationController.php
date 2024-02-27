<?php

declare(strict_types=1);

namespace App\Invoice\GeneratorRelation;

use App\Invoice\Entity\GentorRelation;
use App\Invoice\GeneratorRelation\GeneratorRelationForm;
use App\Invoice\Generator\GeneratorRepository;
use App\Invoice\Setting\SettingRepository;
use App\Service\WebControllerService;
use App\User\UserService;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\SessionInterface as Session;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\ViewRenderer;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


final class GeneratorRelationController
{
    private Session $session;
    private Flash $flash;
    private ViewRenderer $viewRenderer;
    private WebControllerService $webService;
    private GeneratorRelationService $generatorrelationService;    
    private UserService $userService;
    private TranslatorInterface $translator;

    public function __construct(
        Session $session,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        GeneratorRelationService $generatorrelationService,
        UserService $userService,
        TranslatorInterface $translator
    ) {
        $this->session = $session;
        $this->flash = new Flash($session);
        $this->viewRenderer = $viewRenderer->withControllerName('invoice/generatorrelation')
                                           ->withLayout('@views/layout/invoice.php');
        $this->webService = $webService;
        $this->generatorrelationService = $generatorrelationService;
        $this->userService = $userService;
        $this->translator = $translator;
    }
    
    /**
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @param SettingRepository $sR
     */
    public function index(GeneratorRelationRepository $generatorrelationRepository, SettingRepository $sR): \Yiisoft\DataResponse\DataResponse
    {
        $canEdit = $this->rbac();
        $generatorrelations = $this->generatorrelations($generatorrelationRepository);
        $paginator = (new OffsetPaginator($generatorrelations));
        $parameters = [
            'canEdit' => $canEdit,
            'alert' => $this->alert(),
            'grid_summary'=> $sR->grid_summary(
                $paginator, 
                $this->translator, 
                (int)$sR->get_setting('default_list_limit'), 
                $this->translator->translate('invoice.generator.relations'), 
                ''
            ),
            'paginator' => $paginator,
            
                
        ]; 
        return $this->viewRenderer->render('index', $parameters);
    }

    /**
     * 
     * @param Request $request
     * @param GeneratorRepository $generatorRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function add(Request $request, GeneratorRepository $generatorRepository, FormHydrator $formHydrator): Response
    {
        $generatorrelation = new GentorRelation();
        $form = new GeneratorRelationForm($generatorrelation);
        $parameters = [
            'title' => $this->translator->translate('invoice.generator.relation.form'),
            'action' => ['generatorrelation/add'],
            'form' => $form,
            'errors' => [],
            'generators' => $generatorRepository->findAllPreloaded()
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody();
           if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                $this->generatorrelationService->saveGeneratorRelation($generatorrelation, $body);
                return $this->webService->getRedirectResponse('generatorrelation/index');
            }
            $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
            $parameters['form'] = $form;
        }
        return $this->viewRenderer->render('__form', $parameters);
    }
    
    /**
     * 
     * @param Request $request
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @param GeneratorRepository $generatorRepository
     * @param FormHydrator $formHydrator
     * @return Response
     */
    public function edit(Request $request, 
                         CurrentRoute $currentRoute,
                         GeneratorRelationRepository $generatorrelationRepository, 
                         GeneratorRepository $generatorRepository, 
                         FormHydrator $formHydrator
    ): Response 
    {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        if ($generatorrelation) {
            $form = new GeneratorRelationForm($generatorrelation);
            $parameters = [
                'title' => $this->translator->translate('i.edit'),
                'action' => ['generatorrelation/edit', ['id' => $generatorrelation->getRelation_id()]],
                'errors' => [],
                'form' => $form,
                //relation generator
                'generators'=>$generatorRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody();
                /**
                 * @psalm-suppress PossiblyInvalidArgument $body 
                 */
                if ($formHydrator->populateFromPostAndValidate($form,  $request)) {
                    /**
                     * @psalm-suppress PossiblyInvalidArgument $body 
                     */
                    $this->generatorrelationService->saveGeneratorRelation($generatorrelation, $body);
                    return $this->webService->getRedirectResponse('generatorrelation/index');
                }
                $parameters['errors'] = $form->getValidationResult()?->getErrorMessagesIndexedByAttribute() ?? [];
                $parameters['form'] = $form;
            }
            return $this->viewRenderer->render('__form', $parameters);
        }
        return $this->webService->getRedirectResponse('generatorrelation/index');
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @return Response
     */
    public function delete(CurrentRoute $currentRoute, GeneratorRelationRepository $generatorrelationRepository): Response 
    {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        if ($generatorrelation) {
            $this->generatorrelationService->deleteGeneratorRelation($generatorrelation);
            return $this->webService->getRedirectResponse('generatorrelation/index');        
        }    
        return $this->webService->getRedirectResponse('generatorrelation/index');        
    }
    
    /**
     * 
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @param GeneratorRepository $generatorRepository
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(CurrentRoute $currentRoute, 
                         GeneratorRelationRepository $generatorrelationRepository,
                         GeneratorRepository $generatorRepository
        ): \Yiisoft\DataResponse\DataResponse|Response {
        $generatorrelation = $this->generatorrelation($currentRoute, $generatorrelationRepository);
        if ($generatorrelation) {
            $form = new GeneratorRelationForm($generatorrelation);
            $parameters = [
                'title' => $this->translator->translate('i.view'),
                'action' => ['generatorrelation/view', ['id' => $generatorrelation->getRelation_id()]],
                'errors' => [],
                'form' => $form,  
                'generatorrelation' => $generatorrelation,
                'generators' => $generatorRepository->findAllPreloaded(),
                'egrs' => $generatorrelationRepository->repoGeneratorRelationquery($generatorrelation->getRelation_id()),
            ];
            return $this->viewRenderer->render('__view', $parameters);
        }
        return $this->webService->getRedirectResponse('generatorrelation/index');        
    }
    
    /**
     * @return Response|true
     */
    private function rbac(): bool|Response {
        $canEdit = $this->userService->hasPermission('editInv');
        if (!$canEdit){
            $this->flash_message('warning', $this->translator->translate('invoice.permission'));
            return $this->webService->getRedirectResponse('generatorrelation/index');
        }
        return $canEdit;
    }
    
    /**
     * @param CurrentRoute $currentRoute
     * @param GeneratorRelationRepository $generatorrelationRepository
     * @return GentorRelation|null
     */
    private function generatorrelation(CurrentRoute $currentRoute, GeneratorRelationRepository $generatorrelationRepository): GentorRelation |null
    {
        $generatorrelation_id = $currentRoute->getArgument('id');
        if (null!==$generatorrelation_id) {
            $generatorrelation = $generatorrelationRepository->repoGeneratorRelationquery($generatorrelation_id);
            return $generatorrelation;             
        }
        return null;
    }
    
    //$generatorrelations = $this->generatorrelations();
    
    /**
     * @return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Yii\Cycle\Data\Reader\EntityReader
     */
    private function generatorrelations(GeneratorRelationRepository $generatorrelationRepository): \Yiisoft\Yii\Cycle\Data\Reader\EntityReader {
        $generatorrelations = $generatorrelationRepository->findAllPreloaded();
        return $generatorrelations;
    }
    
    /**
     * @return string
     */
    private function alert(): string {
      return $this->viewRenderer->renderPartialAsString('/invoice/layout/alert',
      [ 
        'flash' => $this->flash
      ]);
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
