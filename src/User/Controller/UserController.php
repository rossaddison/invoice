<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\UserRepository;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\View\ViewRenderer;

final class UserController
{
    private const PAGINATION_INDEX = 5;

    public function __construct(
            private ViewRenderer $viewRenderer,
            private WebControllerService $webService
    )
    {
        $this->viewRenderer = $viewRenderer->withControllerName('user');
        $this->webService = $webService;
    }

    public function index(
        CurrentRoute $currentRoute,
        ServerRequestInterface $request,
        UserRepository $userRepository
    ): \Yiisoft\DataResponse\DataResponse {
         
        /** @var array */
        $body = $request->getParsedBody();
        
        $sortOrderString = $request->getQueryParams();
        /** @var string $sortOrderString['sort'] */
        $sort = Sort::only(['id', 'login'])->withOrderString($sortOrderString['sort'] ?? 'id');

        $dataReader = $this->users_with_sort($userRepository, $sort); 

        $page = (int) $currentRoute->getArgument('page', '1');
        
        /** @var null|string $body['pageSize'] */
        $pageSize = (int) $currentRoute->getArgument(
            'pagesize',
            $body['pageSize'] ?? (string) OffSetPaginator::DEFAULT_PAGE_SIZE,
        );

        $paginator_new = (new OffsetPaginator($dataReader));
        $paginator = $paginator_new->withNextPageToken((string) $page)->withPageSize($pageSize);

        return $this->viewRenderer->render('index', ['paginator' => $paginator]);
    }
    
    /**
     * @param UserRepository $uR
     * @param Sort $sort
     *
     * @return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface
     *
     * @psalm-return \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface<int, \App\User\User>
     */
    private function users_with_sort(UserRepository $uR, Sort $sort): \Yiisoft\Data\Reader\SortableDataInterface&\Yiisoft\Data\Reader\DataReaderInterface {       
        $users = $uR->findAllPreloaded()
                    ->withSort($sort);
        return $users;
    }

    public function profile(
        CurrentRoute $currentRoute,
        ResponseFactoryInterface $responseFactory,
        UserRepository $userRepository
    ): Response {
        $login = $currentRoute->getArgument('login');
        if (null!==$login){
            $item = $userRepository->findByLogin($login);
            if ($item === null) {
                return $responseFactory->createResponse(404);
            }
            return $this->viewRenderer->render('profile', ['item' => $item]);
        }
        return $this->webService->getNotFoundResponse();
    }
}
