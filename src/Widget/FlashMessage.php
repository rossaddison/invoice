<?php

declare(strict_types=1);

namespace App\Widget;

use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Widget\Widget;

final class FlashMessage extends Widget
{
    public function __construct(private readonly FlashInterface $flash)
    {
    }

    #[\Override]
    public function render(): string
    {
        $flashes = $this->flash->getAll();
        $html    = [];
        /** @var array $data */
        foreach ($flashes as $type => $data) {
            /** @var array $message */
            foreach ($data as $message) {
                $matchedType = match ($type) {
                    'danger'    => AlertVariant::DANGER,
                    'info'      => AlertVariant::INFO,
                    'primary'   => AlertVariant::PRIMARY,
                    'secondary' => AlertVariant::SECONDARY,
                    'success'   => AlertVariant::SUCCESS,
                    'warning'   => AlertVariant::WARNING,
                    'default'   => AlertVariant::INFO,
                };
                $html[] = Alert::widget()
                    ->addClass('shadow')
                    ->variant($matchedType)
                    ->body((string) $message['body'], true)
                    ->dismissable(true)
                    ->render();
            }
        }

        return implode('', $html);
    }
}
