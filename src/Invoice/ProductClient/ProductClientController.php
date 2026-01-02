<?php

declare(strict_types=1);

namespace App\Invoice\ProductClient;

use App\Invoice\BaseController;
use App\Invoice\Entity\ProductClient;
use App\Invoice\Entity\Client;
use App\Invoice\ProductClient\ProductClientForm;
use App\Invoice\ProductClient\ProductClientService;
use App\Invoice\ProductClient\ProductClientRepository;
use App\Invoice\Setting\SettingRepository as sR;

use App\Invoice\Client\ClientRepository;
use App\Invoice\Client\ClientService;
use App\Invoice\Product\ProductRepository;
use App\User\UserService;
use App\Service\WebControllerService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

use \Exception;
use \DateTimeImmutable;

final class ProductClientController extends BaseController
{
    protected string $controllerName = 'invoice/productclient';

    public function __construct(
        private ProductClientService $productclientService,
        private ClientService $clientService,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        ViewRenderer $viewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct(
            $webService, $userService, $translator, $viewRenderer, $session,
            $sR, $flash);
        $this->productclientService = $productclientService;
        $this->clientService = $clientService;
    }
    
    /**
     * Handle batch association of multiple products from family commalist
     * Either associate a current client with the product e.g. house or 
     * create a new client and associate it with the product.
     * 
     * When the client signsup, use settings 'Invoice User Account' to associate 
     * the above (new) client with the signedup observer account in the
     * invoice/userinv/index table.
     * 
     * @param Request $request
     * @param FormHydrator $formHydrator 
     * @param ClientRepository $clientRepository
     * @param ProductRepository $productRepository
     * @return Response
     */
    public function associateMultiple(
        Request $request,
        FormHydrator $formHydrator,
        ClientRepository $clientRepository,
        ProductRepository $productRepository
    ): Response {
        $body = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        
        // Get product IDs from query parameters (coming from Family controller)
        /** @var array<int> $productIds */
        $productIds = [];
        if (isset($queryParams['product_ids']) &&
                is_string($queryParams['product_ids'])) {
            $productIds = array_map('intval',
                    explode(',', $queryParams['product_ids']));
            $productIds = array_filter($productIds,
                    fn(int $id): bool => $id > 0);
        }
        
        if (empty($productIds)) {
            $this->flash->add(
                'danger',
                $this->translator->translate(
                    'no.products.specified.for.association'),
                true
            );
            return $this->webService->getRedirectResponse('family/index');
        }
        
        // Get current processing index
        $currentIndex = (int)($queryParams['index'] ?? 0);
        
        if ($currentIndex >= count($productIds)) {
            // All products processed
            $this->flash->add(
                'success',
                $this->translator->translate(
                    'all.product.client.associations.completed'),
                true
            );
            return $this->webService->getRedirectResponse('productclient/index');
        }
        
        $currentProductId = $productIds[$currentIndex];
        $product = $productRepository->repoProductQuery((string) $currentProductId);
        
        if (!$product) {
            $this->flash->add(
                'danger',
                $this->translator->translate('product.not.found') .
                    ': ' . $currentProductId,
                true
            );
            return $this->webService->getRedirectResponse('family/index');
        }
        
        // Handle form submission
        if ($request->getMethod() === Method::POST && is_array($body)) {
            /** @var array<string, mixed> $body */
            $associationType = (string) ($body['association_type'] ?? 'existing');
            $suggestedClientGroup = $this->getClientGroupFromSession();
            
            if ($associationType === 'existing') {
                // Associate with existing client
                $clientId = (int)($body['client_id'] ?? 0);
                $client = $clientRepository->repoClientQuery((string) $clientId);
                
                if ($clientRepository->repoClientCount((string) $clientId) > 0) {
                    // Save client group for future suggestions
                    if (strlen($clientGroup = ($client->getClient_group() ?? '')) > 0) {
                        $this->saveClientGroupToSession($clientGroup);
                    }
                    
                    // Create association
                    $this->createProductClientAssociation(
                        $currentProductId, $clientId, $body);
                    
                    // Move to next product
                    return $this->redirectToNextProduct(
                        $productIds, $currentIndex + 1);
                }
                
                $this->flash->add(
                    'danger',
                    $this->translator->translate('client.not.found'),
                    true
                );
            } else {
                // Create new client
                $newClient = $this->createNewClient($body, $suggestedClientGroup);
                
                if ($newClient) {
                    // Save client group for future suggestions  
                    if (strlen($clientGroup = ($newClient->getClient_group() ?? '')) > 0) {
                        $this->saveClientGroupToSession($clientGroup);
                    }
                    
                    // Create association
                    $clientId = $newClient->getClient_id();
                    if ($clientId !== null) {
                        $this->createProductClientAssociation(
                            $currentProductId, $clientId, $body);
                    }
                    
                    // Move to next product
                    return $this->redirectToNextProduct($productIds, $currentIndex + 1);
                }
                
                $this->flash->add(
                    'danger',
                    $this->translator->translate('failed.to.create.client'),
                    true
                );
            }
        }
        
        // Show association form for current product
        $suggestedClientGroup = $this->getClientGroupFromSession();
        
        $parameters = [
            'title' => $this->translator->translate('associate.product.with.client'),
            'actionName' => 'productclient/associate-multiple',
            'actionArguments' => [
                'product_ids' => implode(',', $productIds),
                'index' => $currentIndex
            ],
            'errors' => [],
            'form' => new ProductClientForm(
                new ProductClient(), $currentProductId, null),
            'clients' => $this->buildClientOptionsArray(
                $clientRepository->findAllPreloaded()),
            'product' => $product,
            'productId' => $currentProductId,
            'showClientCreation' => true,
            'suggestedClientGroup' => $suggestedClientGroup,
            'currentIndex' => $currentIndex + 1,
            'totalProducts' => count($productIds),
            'remainingProducts' => count($productIds) - $currentIndex,
        ];
        
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * Get client group suggestion from session
     */
    private function getClientGroupFromSession(): string
    {
        return (string) $this->session->get('suggested_client_group');
    }
    
    /**
     * Save client group to session for future suggestions
     */
    private function saveClientGroupToSession(string $clientGroup): void
    {
        $this->session->set('suggested_client_group', $clientGroup) ;
    }
    
    /**
     * Clear client group suggestion from session
     */
    private function clearClientGroupFromSession(): void
    {
        $this->session->remove('suggested_client_group');
    }
    
    /**
     * Create new client from form data
     * @param array<string, mixed> $body
     */
    private function createNewClient(array $body, ?string $suggestedClientGroup): ?Client
    {
        try {
            $client = new Client();
            
            // Build client data array for service
            $clientData = [
                'client_name' => (string)($body['new_client_name'] ?? ''),
                'client_surname' => (string)($body['new_client_surname'] ?? ''),
                'client_email' => (string)($body['new_client_email'] ?? ''),
                'client_mobile' => (string)($body['new_client_mobile'] ?? ''),
                'client_group' => (string)($body['new_client_group'] ?? $suggestedClientGroup ?? ''),
                'client_active' => '1', // String format expected by service
            ];
            
            // Use the client service to save with SettingRepository
            $clientId = $this->clientService->saveClient($client, $clientData, $this->sR);
            
            if ($clientId !== null) {
                // The service returns the ID, and the client object should now have it
                return $client;
            }
            
            return null;
            
        } catch (Exception $e) {
            $this->flash->add(
                'danger',
                $this->translator->translate('error.creating.client') . ': '
                    . $e->getMessage(),
                true
            );
            return null;
        }
    }
    
    /**
     * Create ProductClient association
     * @param array<string, mixed> $body
     */
    private function createProductClientAssociation(int $productId, int $clientId, array $body): void
    {
        try {
            $productClient = new ProductClient();
            $associationData = [
                'product_id' => $productId,
                'client_id' => $clientId,
                'created_at' => (new DateTimeImmutable())->format('Y-m-d'),
                'updated_at' => (new DateTimeImmutable())->format('Y-m-d'),
            ];
            
            $this->productclientService->save($productClient, $associationData);
            
        } catch (Exception $e) {
            $this->flash->add(
                'danger',
                $this->translator->translate('error.creating.association') .
                    ': ' . $e->getMessage(),
                true
            );
        }
    }
    
    /**
     * Redirect to next product or complete the batch process
     * @param array<int> $productIds
     */
    private function redirectToNextProduct(array $productIds, int $nextIndex): Response
    {
        if ($nextIndex >= count($productIds)) {
            // All products processed, clear session and redirect
            $this->clearClientGroupFromSession();
            $this->flash->add(
                'success',
                $this->translator->translate('all.product.client.associations.completed'),
                true
            );
            return $this->webService->getRedirectResponse('productclient/index');
        }
        
        // Redirect to next product
        return $this->webService->getRedirectResponse('productclient/associate-multiple', [
            'product_ids' => implode(',', $productIds),
            'index' => $nextIndex
        ]);
    }
    
    public function add(Request $request,
        FormHydrator $formHydrator,
        ClientRepository $clientRepository,
        ProductRepository $productRepository,
        #[RouteArgument('productId')] string $productId,
        #[RouteArgument('clientId')] string $clientId,
    ) : Response
    {
        $productclient = new ProductClient();
        $form = new ProductClientForm($productclient, (int) $productId, (int) $clientId);
        $parameters = [
            'title' => $this->translator->translate('add'),
            'actionName' => 'productclient/add',
            'actionArguments' => [],
            'errors' => [],
            'form' => $form,
            'clients' => $clientRepository->findAllPreloaded(),
            'products' => $productRepository->findAllPreloaded(),
        ];
        
        if ($request->getMethod() === Method::POST) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                if ($formHydrator->populateFromPostAndValidate(
                        $form, $request)) {
                    $this->productclientService
                         ->save($productclient, $body);
                    return $this->webService
                                ->getRedirectResponse('productclient/index');
                }
                $parameters['errors'] =
                    $form->getValidationResult()
                         ->getErrorMessagesIndexedByProperty();
                $parameters['form'] = $form;
            }
        }
        return $this->viewRenderer->render('_form', $parameters);
    }
    
    /**
     * @param ProductClientRepository $productClientRepository
     * @param int $id
     * @return Response
     */
    public function delete(ProductClientRepository $productClientRepository,
        #[RouteArgument('id')] int $id 
    ): Response {
        try {
            $productclient = $this->productclient($productClientRepository, $id);
            if ($productclient) {
                $this->productclientService
                     ->delete($productclient);
                $this->flashMessage(
                    'info',
                    $this->translator
                         ->translate('record.successfully.deleted'));
                return $this->webService
                            ->getRedirectResponse('productclient/index');
            }
            return $this->webService->getRedirectResponse('productclient/index');
	} catch (Exception $e) {
            $this->flashMessage('danger', $e->getMessage());
            return $this->webService->getRedirectResponse('productclient/index');
        }
    }
        
    public function edit(
        Request $request,
        FormHydrator $formHydrator,
        ProductClientRepository $productclientRepository,
        ClientRepository $clientRepository,
        ProductRepository $productRepository,
        #[RouteArgument('id')] int $id): Response {
        $productclient = $this->productclient($productclientRepository, $id);
        if ($productclient){
            $form = new ProductClientForm($productclient,
            $productclient->getProductId(), $productclient->getClientId());
            $parameters = [
                'title' => $this->translator->translate('edit'),
                'actionName' => 'productclient/edit',
                'actionArguments' => ['id' => $id],
                'errors' => [],
                'form' => $form,
                'clients'=>$clientRepository->findAllPreloaded(),
                'products'=>$productRepository->findAllPreloaded()
            ];
            if ($request->getMethod() === Method::POST) {
                $body = $request->getParsedBody() ?? [];
                if (is_array($body)) {
                    if ($formHydrator
                             ->populateFromPostAndValidate($form, $request)) {
                        $this->productclientService
                             ->save($productclient, $body);
                        return $this->webService
                                    ->getRedirectResponse('productclient/index');
                    }
                    $parameters['errors'] =
                        $form->getValidationResult()
                             ->getErrorMessagesIndexedByProperty();
                    $parameters['form'] = $form;
                }
            }
            return $this->viewRenderer->render('_form', $parameters);
        }
        return $this->webService->getRedirectResponse('productclient/index');
    }

    /**
     * @param ProductClientRepository $productclientRepository
     * @param int $id
     * @return ProductClient|null
     */
    private function productclient(
        ProductClientRepository $productclientRepository,
        int $id) : ProductClient|null
    {
        if ($id) {
            $productclient = $productclientRepository->repoProductClientQuery($id);
            return $productclient;
        }
        return null;
    }

    /**
     * @return \Yiisoft\Data\Cycle\Reader\EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    private function productclients(
        ProductClientRepository $productclientRepository)
            : \Yiisoft\Data\Cycle\Reader\EntityReader
    {
        $productclients = $productclientRepository->findAllPreloaded();
        return $productclients;
    }
        
    /**
     * @param ProductClientRepository $productclientRepository
     * @param int $id
     * @return \Yiisoft\DataResponse\DataResponse|Response
     */
    public function view(
        ProductClientRepository $productclientRepository,
        #[RouteArgument('id')] int $id)
            : \Yiisoft\DataResponse\DataResponse|Response
    {
        $productclient = $this->productclient($productclientRepository, $id);
        if ($productclient) {
            $product = $productclient->getProduct();
            $client = $productclient->getClient();
            
            $form = new ProductClientForm(
                $productclient,
                $productclient->getProductId(),
                $productclient->getClientId()
            );
            $parameters = [
                'title' => $this->translator->translate('view'),
                'actionName' => 'productclient/view',
                'actionArguments' => ['id' => $id],
                'form' => $form,
                'productClient' => $productclient,
                'product' => $product,
                'client' => $client,
                'alert' => '',
            ];
        return $this->viewRenderer->render('_view', $parameters);
        }
        return $this->webService->getRedirectResponse('productclient/index');
    }
    
    /**
     * Build client options array for dropdown
     * @param \Yiisoft\Data\Cycle\Reader\EntityReader $clients
     * @return array<string, string>
     */
    private function buildClientOptionsArray(
        \Yiisoft\Data\Cycle\Reader\EntityReader $clients): array
    {
        $options = [];
        /**
         * @var Client $client
         */
        foreach ($clients as $client) {
            $status = $client->getClient_active() ?
                $this->translator->translate('active') :
                $this->translator->translate('inactive');
            $options[(string) $client->getClient_id()] =
                $client->getClient_name()
                    . ' '
                    . ($client->getClient_surname()
                        ?? $this->translator->translate('not.set.yet'))
                    . ' ' . ($status ?: $this->translator->translate('inactive'));
        }
        return $options;
    }
}
