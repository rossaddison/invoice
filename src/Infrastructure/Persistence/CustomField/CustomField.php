<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\CustomField;

use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use App\Infrastructure\Persistence\CustomField\Trait\CustomFieldTrait1;
use App\Infrastructure\Persistence\CustomField\Trait\CustomFieldTrait2;
use App\Infrastructure\Persistence\CustomField\Trait\CustomFieldTrait3;

#[Entity(repository: \App\Invoice\CustomField\CustomFieldRepository::class)]
class CustomField
{
    use RequireId;
    use CustomFieldTrait1;
    use CustomFieldTrait2;
    use CustomFieldTrait3;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $table = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $label = '',
        #[Column(type: 'string(151)', nullable: false, default: 'TEXT')]
        private string $type = '',
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $location = null,
        #[Column(type: 'integer(11)', nullable: true, default: 999)]
        private ?int $order = null,
        #[Column(type: 'bool', default: true)]
        private bool $required = false,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $email_min_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 100)]
        private ?int $email_max_length = null,
        #[Column(type: 'bool', default: false)]
        private bool $email_multiple = false,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $url_min_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 150)]
        private ?int $url_max_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $number_min = null,
        #[Column(type: 'integer(11)', nullable: true, default: 100)]
        private ?int $number_max = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $text_min_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 150)]
        private ?int $text_max_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 0)]
        private ?int $text_area_min_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 150)]
        private ?int $text_area_max_length = null,
        #[Column(type: 'integer(11)', nullable: true, default: 10)]
        private ?int $text_area_cols = null,
        #[Column(type: 'integer(11)', nullable: true, default: 10)]
        private ?int $text_area_rows = null,
        #[Column(type: 'string(4)', nullable: true)]
        private ?string $text_area_wrap = 'hard',
    ) {
    }
}
