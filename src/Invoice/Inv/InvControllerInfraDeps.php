<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Delivery\DeliveryRepository;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\Mailer\MailerInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;

final class InvControllerInfraDeps
{
    public function __construct(
        public readonly DataResponseFactoryInterface $factory,
        public readonly HtmlResponseFactory $htmlResponseFactory,
        public readonly LoggerInterface $logger,
        public readonly MailerInterface $mailer,
        public readonly UrlGenerator $urlGenerator,
        public readonly DeliveryRepository $delRepo,
    ) {
    }
}
