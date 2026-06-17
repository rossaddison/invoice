<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget;

use App\Invoice\Inv\InvRepository;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

final readonly class InvsToolbarParams
{
    public function __construct(
        public TranslatorInterface $translator,
        public UrlGeneratorInterface $urlGenerator,
        public CurrentRoute $currentRoute,
        public string|\Stringable $csrf,
        public InvRepository $iR,
        public SettingRepository $sR,
        public int $clientCount,
        public string $groupBy,
        public bool $enableGrouping,
    ) {}
}
