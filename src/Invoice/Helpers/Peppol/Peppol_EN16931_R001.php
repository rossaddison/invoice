<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Html\Html;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface;

final class Peppol_EN16931_R001 extends \RuntimeException implements FriendlyExceptionInterface
{
    public function __construct(private readonly string $client_id, private readonly TranslatorInterface $translator, private readonly UrlGenerator $urlGenerator)
    {
    }

    #[\Override]
    public function getName(): string
    {
        return $this->translator->translate('rules.peppol.en16931.001');
    }

    #[\Override]
    public function getSolution(): ?string
    {
        $string = (string)Html::a('Please try again', $this->urlGenerator->generate('controller/function', ['client_id' => $this->client_id]));
        $open = "<<<'SOLUTION'";
        $close = 'SOLUTION;';
        return $open . $string . $close;
    }
}
