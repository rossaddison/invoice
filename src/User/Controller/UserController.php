<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\UserRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Input\Http\Attribute\Parameter\Body;
use Yiisoft\Input\Http\Attribute\Parameter\Query;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class UserController
{
    public function __construct(
        private WebViewRenderer $webViewRenderer,
    ) {
        $this->webViewRenderer = $webViewRenderer->withControllerName('user');
    }

    public function index(
        UserRepository $userRepository,
        #[Body]
        ?array $body,
        #[Query('sort')]
        ?string $sortOrder = null,
        #[RouteArgument('page')]
        int $page = 1,
        #[RouteArgument('pagesize')]
        ?int $pageSize = null,
    ): Response {
        $order = null !== $sortOrder ? OrderHelper::stringToArray($sortOrder) : [];
        $sort = Sort::only(['id', 'login'])->withOrder($order);
        /**
         * @var \Yiisoft\Data\Cycle\Reader\EntityReader $dataReader
         */
        $dataReader = $userRepository->findAllPreloaded();

        if ($pageSize === null) {
            $pageSize = (int) ($body['pageSize'] ?? OffsetPaginator::DEFAULT_PAGE_SIZE);
        }

        $offsetPaginator = (new OffsetPaginator($dataReader));
        $paginator = $offsetPaginator
                      ->withPageSize($pageSize > 0 ? $pageSize : 1)
                      ->withSort($sort)
                      ->withToken(PageToken::next((string) $page));

        return $this->webViewRenderer->render('index', ['paginator' => $paginator]);
    }

    public function profile(
        #[RouteArgument('login')]
        string $login,
        ResponseFactoryInterface $responseFactory,
        UserRepository $userRepository,
    ): Response {
        $item = $userRepository->findByLogin($login);

        if ($item === null) {
            return $responseFactory->createResponse(404);
        }

        return $this->webViewRenderer->render('profile', ['item' => $item]);
    }
}
