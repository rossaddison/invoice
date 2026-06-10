<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Widget\FormFields;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;

final class QuoteControllerInfraDeps
{
    public function __construct(
        public readonly DataResponseFactoryInterface $factory,
        public readonly HtmlResponseFactory $htmlResponseFactory,
        public readonly LoggerInterface $logger,
        public readonly MailerInterface $mailer,
        public readonly UrlGenerator $urlGenerator,
        public readonly FormFields $formFields,
    ) {
    }
}
