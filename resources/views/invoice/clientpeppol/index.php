<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $alert
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

?>

<h1><?= $translator->translate('client.peppol'); ?></h1>