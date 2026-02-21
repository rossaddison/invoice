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
            $trimmedName = trim($client->getClient_name());
            $trimmedSurname = trim($client->getClient_surname() ?? '');
            $ln = strlen($trimmedName);
            $ls = strlen($trimmedSurname);
            $ns = trim($trimmedName . ' ' . $trimmedSurname);

            return match(true) {
                $ls > 0              => $ns,
                $ls == 0 && $ln > 0  => $trimmedName,
                default              => '',
            };
        }

        return '';
    }

    public function format_gender(int $gender, Translator $t): string
    {
        if ($gender == 0) {
            return $t->translate('gender.male');
        }

        if ($gender == 1) {
            return $t->translate('gender.female');
        }

        return $t->translate('gender.other');
    }
}
