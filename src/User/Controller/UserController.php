<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\UserRepository;
use App\User\Widget\UsersListWidget;
use App\Invoice\Setting\SettingRepository as SR;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class UserController
{
    public function __construct(
        private WebViewRenderer $webViewRenderer,
        private readonly SR $sR,
        private readonly HtmlResponseFactory $htmlResponseFactory,
    ) {
        $this->webViewRenderer = $webViewRenderer->withControllerName('user');
    }

    public function index(Request $request, UserRepository $userRepository): Response
    {
        $paginator = (new OffsetPaginator($userRepository->findAllPreloaded()))
            ->withPageSize(max(1, (int) $this->sR->getSetting('default_list_limit')));

        if ($request->hasHeader('Hx-Request')) {
            return $this->htmlResponseFactory->createResponse(
                (UsersListWidget::widget()->withPaginator($paginator)->withSR($this->sR))->render()
            );
        }

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
