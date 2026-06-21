<?php

declare(strict_types=1);

namespace App\Invoice\Quote\Trait;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\{
    Quote\QuoteUrlKeyRepoDeps,
    Quote\QuoteUrlKeyUserDeps,
};
use Yiisoft\{
    Router\HydratorAttribute\RouteArgument,
    User\CurrentUser,
};
use Psr\Http\Message\ResponseInterface as Response;

trait UrlKey
{
    // When you click on Send Mail whilst in the view, you will get
    // mailer_quote view showing with the url_key at the bottom
    // Use this url_key to test what the customer will
    // experience eg. invoice/quote/url_key/{url_key}
    // config/routes accesschecker ensures client has viewInv permission

    public function urlKey(
        #[RouteArgument('url_key')] string $urlKey,
        CurrentUser $currentUser,
        QuoteUrlKeyRepoDeps $repos,
        QuoteUrlKeyUserDeps $ud,
    ): Response {
        $result = $this->webService->getNotFoundResponse();
        if ($urlKey !== '' && $repos->qR->repoUrlKeyGuestCount($urlKey) >= 1) {
            $resolved = $this->resolveUrlKeyResponse($urlKey, $currentUser, $repos, $ud);
            if ($resolved !== null) {
                $result = $resolved;
            }
        }
        return $result;
    }

    private function resolveUrlKeyResponse(
        string $urlKey,
        CurrentUser $currentUser,
        QuoteUrlKeyRepoDeps $repos,
        QuoteUrlKeyUserDeps $ud,
    ): ?Response {
        $quote = $repos->qR->repoUrlKeyGuestLoaded($urlKey);
        if (!$quote || !in_array($quote->reqStatusId(), [2, 3, 4, 5, 6])) {
            return null;
        }
        if (!$this->isUserAuthorizedForQuote($quote, $ud)) {
            return null;
        }
        return $this->buildAndRenderQuote($quote, $urlKey, $currentUser, $repos, $ud);
    }

    private function isUserAuthorizedForQuote(Quote $quote, QuoteUrlKeyUserDeps $ud): bool
    {
        $user_id = $quote->reqUserId();
        if ($ud->uiR->repoUserInvUserIdcount($user_id) !== 1) {
            return false;
        }
        $user_inv    = $ud->uiR->repoUserInvUserIdquery($user_id);
        $user_client = $ud->ucR->repoUserClientqueryCount($user_id, $quote->reqClientId()) === 1;
        if (!$user_inv || !$user_client || !$user_inv->getActive()) {
            return false;
        }
        return $user_inv->getType() == 1;
    }

    private function buildAndRenderQuote(
        Quote $quote,
        string $urlKey,
        CurrentUser $currentUser,
        QuoteUrlKeyRepoDeps $repos,
        QuoteUrlKeyUserDeps $ud,
    ): ?Response {
        $quote_id = $quote->reqId();
        $quote_tax_rates = $ud->qtrR->repoCount($quote_id) > 0
            ? $ud->qtrR->repoQuotequery($quote_id)
            : null;
        if ($quote->reqStatusId() === 2) {
            $quote->setStatusId(3);
        }
        $repos->qR->save($quote);
        $quote_amount = $repos->qaR->repoQuoteAmountCount($quote_id) > 0
            ? $repos->qaR->repoQuotequery($quote_id)
            : null;
        if (!$quote_amount) {
            return null;
        }
        return $this->renderUrlKeyView($quote, $quote_amount, $quote_tax_rates, $urlKey, $currentUser, $repos, $ud);
    }

    private function renderUrlKeyView(
        Quote $quote,
        mixed $quote_amount,
        mixed $quote_tax_rates,
        string $urlKey,
        CurrentUser $currentUser,
        QuoteUrlKeyRepoDeps $repos,
        QuoteUrlKeyUserDeps $ud,
    ): Response {
        $quote_id   = $quote->reqId();
        $user_id    = $quote->reqUserId();
        $template   = $this->sR->getSetting('public_quote_template') ?: 'Quote_Web';
        $userInv    = $ud->uiR->repoUserInvUserIdcount($user_id) > 0
            ? $ud->uiR->repoUserInvUserIdquery($user_id)
            : null;
        $hasExpired = new \DateTimeImmutable('now') > $quote->getDateExpires();
        $custom_fields = [
            'invoice' => $repos->cfR->repoTablequery('inv_custom'),
            'client'  => $repos->cfR->repoTablequery('client_custom'),
        ];
        $parameters = [
            'renderTemplate' => $this->webViewRenderer->renderPartialAsString(
                '//invoice/template/quote/public/' . $template,
                [
                    'isGuest'         => $currentUser->isGuest(),
                    'alert'           => $this->alert(),
                    'quote'           => $quote,
                    'qiaR'            => $repos->qiaR,
                    'acqiR'           => $repos->acqiR,
                    'quote_amount'    => $quote_amount,
                    'items'           => $repos->qiR->repoQuotequery($quote_id),
                    'quote_tax_rates' => $quote_tax_rates,
                    'quote_url_key'   => $urlKey,
                    'flash_message'   => $this->flashMessage('info', ''),
                    'custom_fields'   => $custom_fields,
                    'has_expired'     => $hasExpired,
                    'client'          => $quote->getClient(),
                    'userInv'         => $userInv,
                    'modal_purchase_order_number' => $this->webViewRenderer->renderPartialAsString(
                        '//invoice/quote/modal_purchase_order_number',
                        ['urlKey' => $urlKey],
                    ),
                ],
            ),
        ];
        return $this->webViewRenderer->render('url_key', $parameters);
    }
}
