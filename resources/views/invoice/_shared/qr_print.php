<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;

/**
 * @var App\Infrastructure\Persistence\Client\Client $client
 * @var string $qrDataUri
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */

$clientName = H::encode(trim($client->getClientName() . ' ' . ($client->getClientSurname() ?? '')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= H::encode($translator->translate('print.qr.code')) ?></title>
<style>
  body { font-family: sans-serif; text-align: center; padding: 2rem; color: #222; }
  .qr-card { display: inline-block; border: 1px solid #ccc; border-radius: 8px; padding: 2rem; max-width: 360px; }
  .qr-card img { width: 260px; height: 260px; }
  .qr-name { font-size: 1.1rem; font-weight: bold; margin-bottom: 0.5rem; }
  .qr-hint { color: #555; font-size: 0.95rem; margin-top: 1rem; }
  .qr-print-btn { margin-top: 1.5rem; padding: 0.5rem 1.5rem; font-size: 1rem; cursor: pointer; }
  @media print {
    .qr-print-btn { display: none; }
    body { padding: 0; }
  }
</style>
</head>
<body>
<div class="qr-card">
<?php if ($clientName !== '') { ?>
  <div class="qr-name"><?= $clientName ?></div>
<?php } ?>
  <img src="<?= H::encode($qrDataUri) ?>" alt="<?= H::encode($translator->translate('qr.code')) ?>">
  <p class="qr-hint"><?= H::encode($translator->translate('qr.code.instructions')) ?></p>
  <button type="button" class="qr-print-btn" onclick="window.print()">
    <?= H::encode($translator->translate('print')) ?>
  </button>
</div>
</body>
</html>
