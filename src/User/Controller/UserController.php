<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\UserRepository;
use App\Invoice\Setting\SettingRepository as SR;
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
        private readonly SR $sR,
    ) {
        $this->webViewRenderer = $webViewRenderer->withControllerName('user');
    }

    public function index(
        UserRepository $userRepository,
        #[Query('sort')]
        ?string $querySort = null,
        #[RouteArgument('page')]
        string $page = '1',
        #[Query('page')]
        ?string $queryPage = null,
    ): Response {
        /**
         * @var \Yiisoft\Data\Cycle\Reader\EntityReader $users
         */
        $users = $userRepository->findAllPreloaded();
        $page = $queryPage ?? $page;
        
        return $this->webViewRenderer->render('index', [
            'defaultPageSizeOffsetPaginator' =>
                    $this->sR->getSetting('default_list_limit') ?
                        (int) $this->sR->getSetting('default_list_limit') : 1,
            'page' => (int) $page > 0 ? (int) $page : 1,
            'sortString' => $querySort ?? '-id',
            'users' => $users,
        ]);
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
