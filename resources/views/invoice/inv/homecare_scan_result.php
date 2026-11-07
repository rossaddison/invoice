<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * Public, unauthenticated result page for the homecare-cleaning QR scan
 * endpoint. Deliberately minimal — no client name, amounts, or other PII,
 * since anyone holding the printed QR code (not just the account owner)
 * can reach this page.
 *
 * @var string $outcome One of: 'generated', 'confirm', 'confirmed', 'not_eligible', 'contact_us'
 * @var string $token the printed token (outcome 'confirm' only)
 * @var string $visitDate the planned visit's date, Y-m-d (outcome 'confirm' only)
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string|\Stringable $csrf
 */

$messageKey = match ($outcome) {
    'generated' => 'homecare.scan.thanks',
    'confirmed' => 'homecare.scan.confirmed',
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
  .scan-confirm { font-size: 1.1rem; padding: 0.75rem 1.5rem; margin-top: 1rem; border: 2px solid #000; border-radius: 6px; background: #fff; cursor: pointer; }
</style>
</head>
<body>
<div class="scan-card">
<?php if ($outcome === 'confirm') : ?>
  <p class="scan-message"><?= H::encode($translator->translate('homecare.scan.confirm.question')) ?></p>
  <p><?= H::encode($translator->translate('homecare.scan.confirm.visit.date')) ?>: <?= H::encode($visitDate) ?></p>
  <?= H::form()
      ->post($urlGenerator->generate('public/homecare-scan-confirm', ['token' => $token]))
      ->csrf($csrf)
      ->content(H::submitButton($translator->translate('homecare.scan.confirm.button'), ['class' => 'scan-confirm'])->render())
      ->encode(false)
      ->render() ?>
<?php else : ?>
  <p class="scan-message"><?= H::encode($translator->translate($messageKey)) ?></p>
<?php endif; ?>
</div>
</body>
</html>
