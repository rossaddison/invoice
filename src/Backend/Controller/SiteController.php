<?php

declare(strict_types=1);

namespace App\Backend\Controller;

use Yiisoft\Yii\View\Renderer\ViewRenderer;

final readonly class SiteController
{
    private ViewRenderer $viewRenderer;

    public function __construct(ViewRenderer $viewRenderer)
    {
        $this->viewRenderer = $viewRenderer
            ->withController($this)
            ->withViewPath('@resources/backend/views');
    }

    public function index(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('index');
    }
}
