<?php

declare(strict_types=1);

namespace App\Invoice\Setting\Trait;

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\I;

/**
 * Separates bootstrap5 settings rendering from the main SettingController.
 *
 * Responsibilities:
 *  - Pre-populate $body from the repository (Fat Controller / Thin View)
 *  - Build the panel wrapper HTML
 *  - Delegate each border-section to a dedicated sub-partial
 *
 * Sub-partials (resources/views/invoice/setting/views/bootstrap5/):
 *  - partial_offcanvas.php  (border-primary:  offcanvas enable + placement)
 *  - partial_alert.php      (border-warning:  alert font, font size, close-button size)
 *  - partial_navbar.php     (border-danger:   navbar font, navbar font size)
 */
trait SettingsTabBootstrap5
{
    /**
     * All bootstrap5 setting keys pre-loaded from the repository.
     *
     * @return array<string, string>
     */
    private function buildBootstrap5Body(): array
    {
        return [
            'settings[bootstrap5_offcanvas_enable]'
                => $this->sR->getSetting('bootstrap5_offcanvas_enable'),
            'settings[bootstrap5_offcanvas_placement]'
                => $this->sR->getSetting('bootstrap5_offcanvas_placement'),
            'settings[bootstrap5_alert_message_font]'
                => $this->sR->getSetting('bootstrap5_alert_message_font'),
            'settings[bootstrap5_alert_message_font_size]'
                => $this->sR->getSetting('bootstrap5_alert_message_font_size'),
            'settings[bootstrap5_alert_close_button_font_size]'
                => $this->sR->getSetting('bootstrap5_alert_close_button_font_size'),
            'settings[bootstrap5_layout_invoice_navbar_font]'
                => $this->sR->getSetting('bootstrap5_layout_invoice_navbar_font'),
            'settings[bootstrap5_layout_invoice_navbar_font_size]'
                => $this->sR->getSetting('bootstrap5_layout_invoice_navbar_font_size'),
        ];
    }

    /**
     * Renders the full bootstrap5 settings panel.
     * Called by tab_index() in place of renderPartialAsString on the old monolithic partial.
     */
    private function bootstrap5Partial(): string
    {
        $fonts = [
            'Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Verdana',
            'Georgia', 'Palatino', 'Garamond', 'Trebuchet MS', 'Impact',
            'PT Sans', 'PT Serif', 'Roboto',
        ];
        $fontSizes = [
            '5','6','7','8','9','10','11','12','13',
            '14','15','16','17','18','19','20',
        ];

        $body = $this->buildBootstrap5Body();

        $offcanvas = $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/views/bootstrap5/partial_offcanvas',
            [
                'body' => $body,
                'placements' => ['top', 'bottom', 'start', 'end'],
            ],
        );

        $alert = $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/views/bootstrap5/partial_alert',
            [
                'body' => $body,
                'fonts' => $fonts,
                'fontSizes' => $fontSizes,
            ],
        );

        $navbar = $this->webViewRenderer->renderPartialAsString(
            '//invoice/setting/views/bootstrap5/partial_navbar',
            [
                'body' => $body,
                'fonts' => $fonts,
                'fontSizes' => $fontSizes,
            ],
        );

        $sep = H::openTag('div', ['class' => 'border']) . H::closeTag('div');

        return
            H::openTag('div', ['class' => 'row'])
            . H::openTag('div', ['class' => 'col-xs-12 col-md-8 col-md-offset-2'])
            . H::openTag('div', ['class' => 'panel panel-default'])
            . H::openTag('div', ['class' => 'panel-heading'])
            . (new I())->addClass('bi bi-bootstrap')->render()
            . H::closeTag('div')
            . H::openTag('div', ['class' => 'panel-body'])
            . H::openTag('div', ['class' => 'row'])
            . $offcanvas
            . $sep
            . $alert
            . $sep
            . $navbar
            . H::closeTag('div')   // row (inner)
            . H::closeTag('div')   // panel-body
            . H::closeTag('div')   // panel panel-default
            . H::closeTag('div')   // col-md-8
            . H::closeTag('div');  // row (outer)
    }
}
