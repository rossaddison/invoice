<?php

declare(strict_types=1);

namespace App\Invoice\Dwelling;

use App\Infrastructure\Persistence\Inv\Inv;
use Stringable;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * The lead's "Delegated" button on inv/guest for a house that is a pull point: one tap once the
 * surrounding houses have been handed to the workers. The time of the last tap is shown beside it.
 * Empty for any house that is not a pull point.
 */
final readonly class PullPointDelegateCell
{
    public function __construct(
        private DwellingRepository $dwR,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private string|Stringable $csrf,
    ) {
    }

    public function render(Inv $inv): string
    {
        $dwellingId = $inv->getClientDwellingId();
        $dwelling = null === $dwellingId ? null : $this->dwR->repoDwellingQuery($dwellingId);
        if (null === $dwelling || !$dwelling->isPullPoint() || !$inv->hasIdentity()) {
            return '';
        }
        $at = $dwelling->getPullPointSortOrder();
        $button = Html::tag('button', '✔ ' . $this->translator->translate('dwelling.pull.point.delegate'), [
            'type' => 'submit',
            'class' => 'btn btn-outline-success btn-sm',
            'data-bs-toggle' => 'tooltip',
            'title' => $this->translator->translate('dwelling.pull.point.delegate.tip'),
        ])->render();
        $format = 'j M H:i';
        if (null !== $at && $at->format('Y-m-d') === date('Y-m-d')) {
            $format = 'H:i';
        }
        $stamp = null === $at ? '' : Html::span(
            sprintf(
                $this->translator->translate('dwelling.pull.point.delegated.at'),
                $at->format($format)
            ),
            ['class' => 'small text-body ms-1']
        )->render();
        return new Form()
            ->post($this->urlGenerator->generate('inv/delegatepullpoint', ['inv_id' => $inv->reqId()]))
            ->csrf($this->csrf)
            ->content($button . $stamp)
            ->encode(false)
            ->render();
    }
}
