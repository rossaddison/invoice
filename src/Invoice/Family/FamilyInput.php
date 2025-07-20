<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Input\Http\Attribute\Parameter\Body;

#[FromBody(['family_name'])]
final readonly class FamilyInput
{
    public function __construct(
        #[Body('family_name')]
        private string $family_name,
    ) {}

    public function getFamilyName(): string
    {
        return $this->family_name;
    }
}
