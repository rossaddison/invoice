<?php

declare(strict_types=1);

namespace App\Invoice\CustomField;

use App\Invoice\Entity\CustomField;

final readonly class CustomFieldService
{
    public function __construct(private CustomFieldRepository $repository)
    {
    }

    /**
     * @param CustomField $model
     * @param array $array
     */
    public function saveCustomField(CustomField $model, array $array): void
    {
        isset($array['table']) ? $model->setTable((string) $array['table']) : '';
        isset($array['label']) ? $model->setLabel((string) $array['label']) : '';
        isset($array['type']) ? $model->setType((string) $array['type']) : '';
        isset($array['location']) ? $model->setLocation((int) $array['location']) : '';
        isset($array['order']) ? $model->setOrder((int) $array['order']) : '';
        isset($array['email_min_length']) ? $model->setEmailMinLength((int) $array['email_min_length']) : '';
        isset($array['email_max_length']) ? $model->setEmailMaxLength((int) $array['email_max_length']) : '';
        $model->setEmailMultiple($array['email_multiple'] === '1' ? true : false);
        isset($array['text_min_length']) ? $model->setTextMinLength((int) $array['text_min_length']) : '';
        isset($array['text_max_length']) ? $model->setTextMaxLength((int) $array['text_max_length']) : '';
        isset($array['text_area_min_length']) ? $model->setTextAreaMinLength((int) $array['text_area_min_length']) : '';
        isset($array['text_area_max_length']) ? $model->setTextAreaMaxLength((int) $array['text_area_max_length']) : '';
        isset($array['text_area_cols']) ? $model->setTextAreaCols((int) $array['text_area_cols']) : '';
        isset($array['text_area_rows']) ? $model->setTextAreaRows((int) $array['text_area_rows']) : '';
        isset($array['text_area_wrap']) ? $model->setTextAreaWrap((string) $array['text_area_wrap']) : '';
        isset($array['number_min']) ? $model->setNumberMin((int) $array['number_min']) : '';
        isset($array['number_max']) ? $model->setNumberMax((int) $array['number_max']) : '';
        isset($array['url_min_length']) ? $model->setUrlMinLength((int) $array['url_min_length']) : '';
        isset($array['url_max_length']) ? $model->setUrlMaxLength((int) $array['url_max_length']) : '';
        $model->setRequired($array['required'] === '1' ? true : false);
        $this->repository->save($model);
    }

    /**
     * @param CustomField $model
     */
    public function deleteCustomField(CustomField $model): void
    {
        $this->repository->delete($model);
    }
}
