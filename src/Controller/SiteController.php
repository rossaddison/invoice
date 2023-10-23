<?php

declare(strict_types=1);

namespace App\Controller;

use Yiisoft\Yii\View\ViewRenderer;

final class SiteController
{
    public function __construct(private ViewRenderer $viewRenderer)
    {
        $this->viewRenderer = $viewRenderer->withController($this);
    }
    
    public function index(): \Yiisoft\DataResponse\DataResponse
    {
        return $this->viewRenderer->render('index');
    }    
}
