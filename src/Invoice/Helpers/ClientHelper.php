<?php

declare(strict_types=1);

Namespace App\Invoice\Helpers;

use App\Invoice\Entity\Client;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Translator\TranslatorInterface as Translator;

Class ClientHelper 
{
    private SettingRepository $s;

    public function __construct(SettingRepository $s) {
        $this->s = $s;
    }
    
    public function format_client(array|object|null $client): string
    {
        if ($client instanceof Client) {
            if (null!==$client->getClient_surname()){
                return rtrim($client->getClient_name() . " " . ($client->getClient_surname() ?? ''));
            } else {
                return $client->getClient_name();        
            }
        }
        return '';
    }

    public function format_gender(int $gender, Translator $t): string
    {
        if ($gender == 0) {
            return $t->translate('i.gender_male');
        }

        if ($gender == 1) {
            return $t->translate('i.gender_female');
        }

        return $t->translate('i.gender_other');
    }
}