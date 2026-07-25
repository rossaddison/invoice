<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\HomeCarePendingSignup;

use App\Infrastructure\Persistence\Trait\RequireId;
use App\Invoice\HomeCarePendingSignup\HomeCarePendingSignupRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table\Index;

/**
 * Holds the HomeCare-signup-specific answers (name/street/house
 * number/price/payment option) between the initial public signup POST and
 * the emailed confirmation-link click, keyed by user_id. Read once and
 * deleted at confirm-time by HomeCareSignupController::confirm().
 */
#[Entity(repository: HomeCarePendingSignupRepository::class)]
#[Index(columns: ['user_id'], unique: true)]
class HomeCarePendingSignup
{
    use RequireId;

    #[Column(type: 'primary')]
    private ?int $id = null;

    public function __construct(
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $user_id = null,
        #[Column(type: 'string(151)', nullable: false)]
        private string $client_name = '',
        #[Column(type: 'string(151)', nullable: false)]
        private string $client_surname = '',
        #[Column(type: 'string(151)', nullable: false)]
        private string $street = '',
        #[Column(type: 'string(10)', nullable: false)]
        private string $building_number = '',
        #[Column(type: 'decimal(20,2)', nullable: false, default: 0)]
        private float $price = 0.00,
        #[Column(type: 'string(20)', nullable: false)]
        private string $payment_option = '',
        #[Column(type: 'integer(11)', nullable: false, default: 0)]
        private int $secondary_category_id = 0,
    ) {
    }

    public function reqId(): int
    {
        return $this->requireId($this->id, 'HomeCarePendingSignup');
    }

    public function hasIdentity(): bool
    {
        return $this->id !== null;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function reqUserId(): int
    {
        return $this->requireId($this->user_id, 'User');
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getClientName(): string
    {
        return $this->client_name;
    }

    public function setClientName(string $client_name): void
    {
        $this->client_name = $client_name;
    }

    public function getClientSurname(): string
    {
        return $this->client_surname;
    }

    public function setClientSurname(string $client_surname): void
    {
        $this->client_surname = $client_surname;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getBuildingNumber(): string
    {
        return $this->building_number;
    }

    public function setBuildingNumber(string $building_number): void
    {
        $this->building_number = $building_number;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getPaymentOption(): string
    {
        return $this->payment_option;
    }

    public function setPaymentOption(string $payment_option): void
    {
        $this->payment_option = $payment_option;
    }

    public function getSecondaryCategoryId(): int
    {
        return $this->secondary_category_id;
    }

    public function setSecondaryCategoryId(int $secondary_category_id): void
    {
        $this->secondary_category_id = $secondary_category_id;
    }
}
