<?php

declare(strict_types=1);

namespace App\Backend\Controller\Trait;

use Psr\Http\Message\ResponseInterface as Response;
use Yiisoft\Yii\AuthClient\RequestUtil;

trait HmrcIncomeCategoryTrait
{
    public function incomeDividends(): Response
    {
        return $this->renderIncomeCategory('dividends');
    }

    public function incomeEmployments(): Response
    {
        return $this->renderIncomeCategory('employments');
    }

    public function incomeForeign(): Response
    {
        return $this->renderIncomeCategory('foreign');
    }

    public function incomeInsurancePolicies(): Response
    {
        return $this->renderIncomeCategory('insurance-policies');
    }

    public function incomeOther(): Response
    {
        return $this->renderIncomeCategory('other');
    }

    public function incomePartner(): Response
    {
        return $this->renderIncomeCategory('partner');
    }

    public function incomePensions(): Response
    {
        return $this->renderIncomeCategory('pensions');
    }

    public function incomeSavings(): Response
    {
        return $this->renderIncomeCategory('savings');
    }

    /**
     * Validates NINO + HMRC access token are both set, redirecting
     * with a flash message when either is missing -- the same guard
     * itsaStatus(), selfEmploymentBusinesses() and
     * incomeTaxObligations() already repeat inline (all three
     * pre-existing on main before this PR). Extracted here and used by
     * individualCalculations() and renderIncomeCategory() below --
     * both new in this PR -- purely because two new inline copies of
     * this exact guard is what SonarCloud's own
     * new_duplicated_lines_density flagged as real duplication against
     * itsaStatus()'s own pre-existing inline copy; not a general
     * refactor of the three pre-existing call sites, which stays out
     * of scope for this change (see this session's own scope-creep
     * discipline).
     *
     * @return array{0: string, 1: string}|Response Either [nino,
     *   token] on success, or a redirect Response to render directly.
     */
    private function ensureNinoAndToken(): array|Response
    {
        $nino = $this->sR->getSetting('nino');
        $tokenString = (string) $this->session->get('hmrc_access_token');
        if ($nino === '' || strlen($tokenString) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.business.missing.nino.or.token'),
            );
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }
        return [$nino, $tokenString];
    }

    /**
     * Shared GET/render logic for all eight income-category actions
     * above -- see incomeCategories()'s own docblock for why one
     * shared action+view serves all eight rather than eight near-
     * identical copies (the exact DRY reasoning itsaEntry() already
     * applies to the catalogue side of this same API family).
     */
    private function renderIncomeCategory(string $category): Response
    {
        $config = self::incomeCategories()[$category];

        $guard = $this->ensureNinoAndToken();
        if ($guard instanceof Response) {
            return $guard;
        }
        [$nino, $tokenString] = $guard;
        $otpReference = (string) $this->session->get('otpRef');

        $taxYear = $config['needsTaxYear'] ? $this->currentUkTaxYear() : '';

        $url = $this->resolveHmrcApiBaseUrl() . '/individuals/'
            . $config['segment'] . '/' . urlencode($nino)
            . ($taxYear !== '' ? '/' . urlencode($taxYear) : '')
            . $config['suffix'];

        $request = $this->createRequest('GET', $url);
        $request = RequestUtil::addHeaders($request, array_merge(
            [
                'Accept'        =>
                    'application/vnd.hmrc.' . $config['version'] . '+json',
                'Authorization' => 'Bearer ' . $tokenString,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        ));

        $response = $this->sendRequest($request);
        /** @var array<string, mixed> $parsed */
        $parsed = (array) json_decode(
            $response->getBody()->getContents(),
            true,
        );

        return $this->webViewRenderer->render('incomeCategory', [
            'alert'      => $this->alert(),
            'nino'       => $nino,
            'taxYear'    => $taxYear,
            'label'      => $config['label'],
            'statusCode' => $response->getStatusCode(),
            'raw'        => $parsed,
        ]);
    }
}
