<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\UserInv;

use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Relation\BelongsTo;
use Cycle\ORM\Entity\Behavior;
use DateTimeImmutable;
use App\Infrastructure\Persistence\User\User;
use Yiisoft\Translator\TranslatorInterface as Translator;
use App\Infrastructure\Persistence\UserInv\Trait\UserInvTrait1;
use App\Infrastructure\Persistence\UserInv\Trait\UserInvTrait2;
use App\Infrastructure\Persistence\UserInv\Trait\UserInvTrait3;
use App\Infrastructure\Persistence\UserInv\Trait\UserInvTrait4;

#[Entity(repository: \App\Invoice\UserInv\UserInvRepository::class)]
#[Behavior\CreatedAt(field: 'date_created', column: 'date_created')]
#[Behavior\UpdatedAt(field: 'date_modified', column: 'date_modified')]
class UserInv
{
    use RequireId;
    use UserInvTrait1;
    use UserInvTrait2;
    use UserInvTrait3;
    use UserInvTrait4;
    
    #[BelongsTo(target: User::class, nullable: false)]
    private ?User $user = null;

    #[Column(type: 'datetime', nullable: false)]
    private readonly DateTimeImmutable $date_created;

    #[Column(type: 'datetime', nullable: false)]
    private readonly DateTimeImmutable $date_modified;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $user_id = null,
        /**
         * Related logic: see src/Invoice/UserInv/UserInvForm 0 => Admin,
         *  1 => Not Admin
         */
        #[Column(type: 'integer(11)', nullable: false, default: 0)]
        private ?int $type = null,
        #[Column(type: 'bool', typecast: 'bool', default: false)]
        private ?bool $active = false,
        #[Column(type: 'string(191)', nullable: true, default: 'system')]
        private ?string $language = '',
        #[Column(type: 'string(151)', nullable: true)]
        private ?string $name = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $company = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $address_1 = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $address_2 = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $city = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $state = '',
        #[Column(type: 'string(10)', nullable: true)]
        private ?string $zip = '',
        #[Column(type: 'string(50)', nullable: true)]
        private ?string $country = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $phone = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $fax = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $mobile = '',
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $web = '',
        #[Column(type: 'string(20)', nullable: true)]
        private ?string $vat_id = '',
        #[Column(type: 'string(15)', nullable: true)]
        private ?string $tax_code = '',
        #[Column(type: 'bool', typecast: 'bool', default: false)]
        private ?bool $all_clients = false,
        #[Column(type: 'string(40)', nullable: true)]
        private ?string $subscribernumber = '',
        #[Column(type: 'string(34))', nullable: true)]
        private ?string $iban = '',
        #[Column(type: 'bigInteger(20)', nullable: true)]
        private ?int $gln = null,
        #[Column(type: 'string(7)', nullable: true)]
        private ?string $rcc = '',
        #[Column(type: 'integer(3)', nullable: true, default: 10)]
        private ?int $listLimit = 10,
        #[Column(type: 'bool', typecast: 'bool', default: false)]
        private ?bool $consent_periodic_invoice = false,
        #[Column(type: 'bool', typecast: 'bool', default: false)]
        private ?bool $consent_telegram_outstanding = false,
        #[Column(type: 'string(100)', nullable: true)]
        private ?string $telegram_chat_id = null,
    ) {
        $this->date_created = new DateTimeImmutable();
        $this->date_modified = new DateTimeImmutable();
    }
}
