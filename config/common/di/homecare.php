<?php

declare(strict_types=1);

use App\Invoice\Inv\InvRepository;
use App\Invoice\Inv\InvRepositoryInterface;
use App\Invoice\InvItem\InvItemRepository;
use App\Invoice\InvItem\InvItemRepositoryInterface;
use App\Invoice\Payment\PaymentRepository;
use App\Invoice\Payment\PaymentRepositoryInterface;
use App\Invoice\Product\ProductRepository;
use App\Invoice\Product\ProductRepositoryInterface;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\Setting\SettingRepositoryInterface;

// HomeCareCleaningEligibilityService (src/Invoice/Inv/HomeCareCleaningEligibilityService.php)
// is constructor-injected via these interfaces so it can be unit-tested with
// Mockery (the concrete Cycle repository classes below are all `final`, so
// Mockery cannot mock them directly). The container needs an explicit
// Interface::class => Concrete::class mapping for each, same as
// As4MessageRepositoryInterface in config/common/di/as4.php — Cycle's
// RepositoryContainer delegate only resolves concrete repository class names
// declared on an entity's #[Entity(repository: ...)] attribute, not
// arbitrary interfaces.
return [
    InvRepositoryInterface::class     => InvRepository::class,
    PaymentRepositoryInterface::class => PaymentRepository::class,
    InvItemRepositoryInterface::class => InvItemRepository::class,
    ProductRepositoryInterface::class => ProductRepository::class,
    SettingRepositoryInterface::class => SettingRepository::class,
];
