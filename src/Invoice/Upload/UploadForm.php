<?php

declare(strict_types=1);

namespace App\Invoice\Upload;

use Yiisoft\Form\FormModel;
use Yiisoft\Validator\Rule\Required;

final class UploadForm extends FormModel
{    
    private ?int $client_id=null;
    private string $url_key='';
    private string $file_name_original='';
    private string $file_name_new='';
    private string $description='';
    private string $uploaded_date='';

    public function getClient_id() : int|null
    {
      return $this->client_id;
    }

    public function getUrl_key() : string
    {
      return $this->url_key;
    }

    public function getFile_name_original() : string
    {
      return $this->file_name_original;
    }

    public function getFile_name_new() : string
    {
      return $this->file_name_new;
    }
    
    public function getDescription() : string
    {
      return $this->description;  
    }
    
    public function getUploaded_date() : string|null
    {
       return $this->uploaded_date;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function getFormName(): string
    {
      return '';
    }

    /**
     * @return Required[][]
     *
     * @psalm-return array{url_key: list{Required}, file_name_original: list{Required}, file_name_new: list{Required}, uploaded_date: list{Required}}
     */
    public function getRules(): array    {
      return [
        'url_key' => [
            new Required(),
        ],
        'file_name_original' => [
            new Required(),
        ],
        'file_name_new' => [
            new Required(),
        ],
        'uploaded_date' => [
            new Required(),
        ],
    ];
}
}
