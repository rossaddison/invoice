<?php

declare(strict_types=1);

Namespace App\Invoice\Helpers;

use App\Invoice\Entity\Client;
use App\Invoice\Setting\SettingRepository; 

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

    public function format_gender(int $gender, SettingRepository $s): string
    {
        if ($gender == 0) {
            return $s->trans('gender_male');
        }

        if ($gender == 1) {
            return $s->trans('gender_female');
        }

        return $s->trans('gender_other');
    }
}