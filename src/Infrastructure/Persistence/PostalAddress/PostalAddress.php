<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PostalAddress;

use App\Infrastructure\Persistence\Trait\RequireId;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use App\Infrastructure\Persistence\PostalAddress\Trait\PostalAddressTrait1;
use App\Infrastructure\Persistence\PostalAddress\Trait\PostalAddressTrait2;

#[Entity(repository: \App\Invoice\PostalAddress\PostalAddressRepository::class)]

class PostalAddress
{
    use RequireId;
    use PostalAddressTrait1;
    use PostalAddressTrait2;

    public function __construct(
        #[Column(type: 'primary')]
        public ?int $id = null,
        #[Column(type: 'integer(11)', nullable: false)]
        private ?int $client_id = null,
        #[Column(type: 'string(50)', nullable: false)]
        private string $street_name = '',
        #[Column(type: 'string(50)', nullable: false)]
        private string $additional_street_name = '',
        #[Column(type: 'string(4)', nullable: false)]
        private string $building_number = '',
        #[Column(type: 'string(50)', nullable: false)]
        private string $city_name = '',
        #[Column(type: 'string(7)', nullable: false)]
        private string $postalzone = '',
        #[Column(type: 'string(50)', nullable: false)]
        private string $countrysubentity = '',
        #[Column(type: 'string(50)', nullable: false)]
        private string $country = '')
    {
    }
}
