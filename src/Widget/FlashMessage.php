<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Widget\Widget;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

final class FlashMessage extends Widget
{
    public function __construct(private FlashInterface $flash)
    {
    }
    
    public function render(): string
    {
        $flashes = $this->flash->getAll();
        $html = [];
        /** @var array $data */
        foreach ($flashes as $type => $data) {
            /** @var array $message */
            foreach ($data as $message) {
                $matchedType = match ($type) {
                    'danger' => AlertType::DANGER,
                    'info' => AlertType::INFO,
                    'primary' => AlertType::PRIMARY,
                    'secondary' => AlertType::SECONDARY,
                    'success' => AlertType::SUCCESS,
                    'warning' => AlertType::WARNING,
                    'default' => AlertType::INFO
                };
                $html[] = Alert::widget()
                    ->addClass('shadow')
                    ->type($matchedType)
                    ->body((string)$message['body'], true)    
                    ->dismissable(true)
                   ->render();
            }
        }
        return implode($html);
    }
}
