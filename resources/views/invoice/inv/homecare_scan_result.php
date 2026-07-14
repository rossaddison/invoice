<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Public, unauthenticated result page for the homecare-cleaning QR scan
 * endpoint. Deliberately minimal — no client name, amounts, or other PII,
 * since anyone holding the printed QR code (not just the account owner)
 * can reach this page.
 *
 * @var string $outcome One of: 'generated', 'not_eligible', 'contact_us'
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

$messageKey = match ($outcome) {
    'generated' => 'homecare.scan.thanks',
    'not_eligible' => 'homecare.scan.not.eligible',
    default => 'homecare.scan.contact.us',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= H::encode($translator->translate('homecare.scan.title')) ?></title>
<style>
  body { font-family: sans-serif; text-align: center; padding: 3rem 1.5rem; color: #222; }
  .scan-card { display: inline-block; border: 1px solid #ccc; border-radius: 8px; padding: 2rem; max-width: 420px; }
  .scan-message { font-size: 1.1rem; }
</style>
</head>
<body>
<div class="scan-card">
  <p class="scan-message"><?= H::encode($translator->translate($messageKey)) ?></p>
</div>
</body>
</html>
