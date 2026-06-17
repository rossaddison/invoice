<?php

declare(strict_types=1);

use App\Invoice\Generator\Widget\GeneratorListWidget;
use App\Invoice\GeneratorRelation\GeneratorRelationRepository;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Data\Paginator\OffsetPaginator;

/**
 * @var SettingRepository $s
 * @var GeneratorRelationRepository $grR
 * @var OffsetPaginator $paginator
 * @var string $alert
 * @var string $csrf
 * @var string $gridSummary
 * @var string $sortString
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo GeneratorListWidget::widget()
    ->withPaginator($paginator)
    ->withGrR($grR)
    ->withCsrf($csrf)
    ->withGridSummary($gridSummary)
    ->withSortString($sortString)
    ->render();
