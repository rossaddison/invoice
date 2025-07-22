<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\Entity\Client;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Translator\TranslatorInterface as Translator;

class ClientHelper
{
    public function __construct(private readonly SettingRepository $s)
    {
    }

    public function format_client(array|object|null $client): string
    {
        if ($client instanceof Client) {
            if (null !== $client->getClient_surname()) {
                return rtrim($client->getClient_name().' '.($client->getClient_surname() ?? ''));
            }

            return $client->getClient_name();
        }

        return '';
    }

    public function format_gender(int $gender, Translator $t): string
    {
        if (0 == $gender) {
            return $t->translate('gender.male');
        }

        if (1 == $gender) {
            return $t->translate('gender.female');
        }

        return $t->translate('gender.other');
    }
}
