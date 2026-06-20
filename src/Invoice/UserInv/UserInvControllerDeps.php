<?php

declare(strict_types=1);

namespace App\Invoice\UserInv;

use App\Service\WebControllerService;
use Yiisoft\Rbac\AssignmentsStorageInterface as Assignment;
use Yiisoft\Rbac\ItemsStorageInterface as ItemStorage;
use Yiisoft\Rbac\RuleFactoryInterface as Rule;
use Yiisoft\Router\FastRoute\UrlGenerator;

final class UserInvControllerDeps
{
    public function __construct(
        public readonly WebControllerService $webService,
        public readonly ItemStorage $itemstorage,
        public readonly Assignment $assignment,
        public readonly Rule $rule,
        public readonly UrlGenerator $urlGenerator,
        public readonly UserInvService $userinvService,
        public readonly \App\Widget\UserInvFormFields $formFields,
    ) {
    }
}
