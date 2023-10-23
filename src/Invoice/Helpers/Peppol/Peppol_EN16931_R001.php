<?php
declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Html\Html;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface;

class Peppol_EN16931_R001 extends \RuntimeException implements FriendlyExceptionInterface
{
    private TranslatorInterface $translator;
    private UrlGenerator $urlGenerator;
    private string $client_id;
    
    public function __construct(string $client_id, TranslatorInterface $translator, UrlGenerator $urlGenerator) {
        $this->client_id = $client_id;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }
    
    public function getName(): string
    {
        return $this->translator->translate('invoice.rules.peppol.en16931.001');
    }
    
    public function getSolution(): ?string
    {
        $string = Html::a('Please try again', $this->urlGenerator->generate('controller/function',['client_id'=>$this->client_id]));
        $open = "<<<'SOLUTION'";
        $close = "SOLUTION;";
        return $open.$string.$close;
    }
}