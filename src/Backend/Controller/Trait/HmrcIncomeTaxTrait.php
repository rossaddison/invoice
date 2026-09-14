<?php

declare(strict_types=1);

namespace App\Backend\Controller\Trait;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Yiisoft\Http\Method;
use Yiisoft\Yii\AuthClient\RequestUtil;

trait HmrcIncomeTaxTrait
{
    /**
     * List every business (of any type) registered for the configured NINO.
     *
     * Live-testing fix 2026-09-10 (third pass): this read the NINO from
     * session ('hmrc_nino'), which nothing anywhere in this app ever
     * wrote -- every request landed here with an empty NINO and bounced
     * straight back to backend/hmrc's own "missing vrn or token" flash,
     * regardless of which API was picked. NINO now comes from the same
     * place VRN already does (Settings -> Making Tax Digital, a plain
     * getSetting() call), matching partial_settings_making_tax_digital.php's
     * own new 'nino' field.
     *
     * Live-testing fix 2026-09-10: this used to call the
     * self-employment-business-api's own "list all businesses" endpoint
     * (GET .../self-employment/{nino}/self-employments) -- confirmed
     * against that API's real current OAS spec (version 5.0) that this
     * endpoint no longer exists; every endpoint there now needs an
     * already-known businessId (annual/period/cumulative submissions),
     * nothing to discover one with. Business discovery moved to a
     * different API entirely -- Business Details (MTD)'s own /list
     * endpoint, which returns every business type (self-employment,
     * uk-property, foreign-property, property-unspecified) keyed by
     * typeOfBusiness. Needs its own Developer Hub subscription (Business
     * Details (MTD)), separate from Self Employment Business.
     * https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/business-details-api/2.0
     *
     * Live-testing fix 2026-09-10 (same day, second pass): this used to
     * filter the /list response down to typeOfBusiness === 'self-employment'
     * -- reasonable when the only way here was the "Self-employed Business"
     * catalogue entry, but HmrcApiCatalogue::routeFor() also sends the
     * "Business Details" catalogue entry to this same action (deliberately,
     * per its own docblock -- Business Details is the API that actually
     * backs this page now), and a NINO whose businesses are all
     * uk-property/foreign-property made that entry point look like it
     * "returned nothing". No filtering now -- every business type is
     * shown, with its type as its own column, so both catalogue entries
     * get a correct result.
     */
    public function selfEmploymentBusinesses(): Response
    {
        $nino        = $this->sR->getSetting('nino');
        $tokenString = (string) $this->session->get('hmrc_access_token');
        $otpReference = (string) $this->session->get('otpRef');

        if ($nino === '' || strlen($tokenString) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.business.missing.nino.or.token')
            );
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }

        $request = $this->createRequest(
            'GET',
            $this->resolveHmrcApiBaseUrl() . '/individuals/business/details/'
                . urlencode($nino) . '/list',
        );

        $request = RequestUtil::addHeaders($request, array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.2.0+json',
                'Authorization' => 'Bearer ' . $tokenString,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        ));

        $apiResponse = $this->sendRequest($request);
        /** @var array<string, mixed> $parsed */
        $parsed = (array) json_decode($apiResponse->getBody()->getContents(), true);
        /** @var list<array<string, mixed>> $allBusinesses */
        $allBusinesses = $parsed['listOfBusinesses'] ?? [];

        return $this->webViewRenderer->render('selfEmploymentBusinesses', [
            'alert'         => $this->alert(),
            'nino'          => $nino,
            'statusCode'    => $apiResponse->getStatusCode(),
            'businesses'    => $allBusinesses,
            'raw'           => $parsed,
        ]);
    }

    /**
     * Retrieve every MTD obligation for the configured NINO across all
     * income sources -- quarterly-update/EOPS deadlines via
     * income-and-expenditure, plus the once-per-NINO final declaration
     * deadline via crystallisation. Confirmed live against HMRC's real
     * obligations-api/3.0 OAS spec (fetched 2026-09-10, not guessed):
     * GET .../obligations/details/{nino}/income-and-expenditure and
     * GET .../obligations/details/{nino}/crystallisation, both needing
     * only read:self-assessment -- the same NINO/token every other
     * ITSA-family action here already uses.
     * https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/obligations-api/3.0
     */
    public function incomeTaxObligations(): Response
    {
        $nino         = $this->sR->getSetting('nino');
        $tokenString  = (string) $this->session->get('hmrc_access_token');
        $otpReference = (string) $this->session->get('otpRef');

        if ($nino === '' || strlen($tokenString) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.business.missing.nino.or.token'),
            );
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }

        $headers = array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.3.0+json',
                'Authorization' => 'Bearer ' . $tokenString,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        );
        $base = $this->resolveHmrcApiBaseUrl() . '/obligations/details/'
            . urlencode($nino);

        $incomeResponse = $this->sendRequest(RequestUtil::addHeaders(
            $this->createRequest('GET', $base . '/income-and-expenditure'),
            $headers,
        ));
        /** @var array<string, mixed> $incomeParsed */
        $incomeParsed = (array) json_decode(
            $incomeResponse->getBody()->getContents(),
            true,
        );

        $crystallisationResponse = $this->sendRequest(RequestUtil::addHeaders(
            $this->createRequest('GET', $base . '/crystallisation'),
            $headers,
        ));
        /** @var array<string, mixed> $crystallisationParsed */
        $crystallisationParsed = (array) json_decode(
            $crystallisationResponse->getBody()->getContents(),
            true,
        );

        return $this->webViewRenderer->render('incomeTaxObligations', [
            'alert'                      => $this->alert(),
            'nino'                       => $nino,
            'incomeStatusCode'           => $incomeResponse->getStatusCode(),
            'incomeObligations'          =>
                $this->flattenIncomeExpenditureObligations($incomeParsed),
            'crystallisationStatusCode'  =>
                $crystallisationResponse->getStatusCode(),
            'crystallisationObligations' =>
                $this->normalizeCrystallisationObligations($crystallisationParsed),
        ]);
    }

    /**
     * Flattens the income-and-expenditure endpoint's per-business
     * grouping (obligations[].obligationDetails[]) into one flat list
     * for the view -- see incomeTaxObligations()'s own docblock for the
     * real response shape this mirrors.
     *
     * @param array<string, mixed> $parsed
     * @return list<array{
     *     typeOfBusiness: string,
     *     businessId: string,
     *     periodStartDate: string,
     *     periodEndDate: string,
     *     dueDate: string,
     *     status: string,
     *     receivedDate: string,
     * }>
     */
    private function flattenIncomeExpenditureObligations(array $parsed): array
    {
        /** @var list<array<string, mixed>> $groups */
        $groups    = (array) ($parsed['obligations'] ?? []);
        $flattened = [];
        foreach ($groups as $group) {
            $typeOfBusiness = isset($group['typeOfBusiness'])
                ? (string) $group['typeOfBusiness']
                : '';
            $businessId = isset($group['businessId'])
                ? (string) $group['businessId']
                : '';
            /** @var list<array<string, mixed>> $details */
            $details = (array) ($group['obligationDetails'] ?? []);
            foreach ($details as $detail) {
                $flattened[] = [
                    'typeOfBusiness'  => $typeOfBusiness,
                    'businessId'      => $businessId,
                    'periodStartDate' =>
                        $this->stringOrEmpty($detail, 'periodStartDate'),
                    'periodEndDate' =>
                        $this->stringOrEmpty($detail, 'periodEndDate'),
                    'dueDate' => $this->stringOrEmpty($detail, 'dueDate'),
                    'status' => $this->stringOrEmpty($detail, 'status'),
                    'receivedDate' =>
                        $this->stringOrEmpty($detail, 'receivedDate'),
                ];
            }
        }
        return $flattened;
    }

    /**
     * Normalizes the crystallisation endpoint's already-flat obligations
     * list -- no per-business grouping there, unlike
     * income-and-expenditure (it's once per NINO, not per business).
     *
     * @param array<string, mixed> $parsed
     * @return list<array{
     *     periodStartDate: string,
     *     periodEndDate: string,
     *     dueDate: string,
     *     status: string,
     *     receivedDate: string,
     * }>
     */
    private function normalizeCrystallisationObligations(array $parsed): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows       = (array) ($parsed['obligations'] ?? []);
        $normalized = [];
        foreach ($rows as $row) {
            $normalized[] = [
                'periodStartDate' => $this->stringOrEmpty($row, 'periodStartDate'),
                'periodEndDate'   => $this->stringOrEmpty($row, 'periodEndDate'),
                'dueDate'         => $this->stringOrEmpty($row, 'dueDate'),
                'status'          => $this->stringOrEmpty($row, 'status'),
                'receivedDate'    => $this->stringOrEmpty($row, 'receivedDate'),
            ];
        }
        return $normalized;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function stringOrEmpty(array $row, string $key): string
    {
        return isset($row[$key]) ? (string) $row[$key] : '';
    }

    /**
     * Retrieve the MTD ITSA status for the configured NINO and a tax
     * year (defaults to the current UK tax year, overridable via
     * ?taxYear=YYYY-YY). Confirmed live against HMRC's real
     * self-assessment-api/3.0 OAS spec (fetched 2026-09-11, not
     * guessed): GET .../individuals/person/itsa-status/{nino}/{taxYear},
     * needing only read:self-assessment -- already correctly requested
     * via the existing itsaEntry() bundle, no catalogue fix needed for
     * this one.
     * https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/self-assessment-api/3.0
     */
    public function itsaStatus(ServerRequest $request): Response
    {
        $nino         = $this->sR->getSetting('nino');
        $tokenString  = (string) $this->session->get('hmrc_access_token');
        $otpReference = (string) $this->session->get('otpRef');

        if ($nino === '' || strlen($tokenString) === 0) {
            $this->flashMessage(
                'warning',
                $this->translator->translate('mtd.business.missing.nino.or.token'),
            );
            return $this->webService->getRedirectResponse(self::INDEX_ROUTE);
        }

        $queryParams = $request->getQueryParams();
        $taxYear = (string) ($queryParams['taxYear'] ?? $this->currentUkTaxYear());

        $apiRequest = $this->createRequest(
            'GET',
            $this->resolveHmrcApiBaseUrl()
                . '/individuals/person/itsa-status/' . urlencode($nino)
                . '/' . urlencode($taxYear),
        );

        $apiRequest = RequestUtil::addHeaders($apiRequest, array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.3.0+json',
                'Authorization' => 'Bearer ' . $tokenString,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        ));

        $apiResponse = $this->sendRequest($apiRequest);
        /** @var array<string, mixed> $parsed */
        $parsed = (array) json_decode(
            $apiResponse->getBody()->getContents(),
            true,
        );

        return $this->webViewRenderer->render('itsaStatus', [
            'alert'       => $this->alert(),
            'nino'        => $nino,
            'taxYear'     => $taxYear,
            'statusCode'  => $apiResponse->getStatusCode(),
            'itsaStatuses' => $this->normalizeItsaStatuses($parsed),
        ]);
    }

    /**
     * Normalizes the itsa-status endpoint's per-tax-year grouping
     * (itsaStatuses[].itsaStatusDetails[]) into one flat list for the
     * view -- see itsaStatus()'s own docblock for the real response
     * shape this mirrors.
     *
     * @param array<string, mixed> $parsed
     * @return list<array{
     *     taxYear: string,
     *     submittedOn: string,
     *     status: string,
     *     statusReason: string,
     *     businessIncome2YearsPrior: string,
     * }>
     */
    private function normalizeItsaStatuses(array $parsed): array
    {
        /** @var list<array<string, mixed>> $groups */
        $groups     = (array) ($parsed['itsaStatuses'] ?? []);
        $normalized = [];
        foreach ($groups as $group) {
            $taxYear = isset($group['taxYear']) ? (string) $group['taxYear'] : '';
            /** @var list<array<string, mixed>> $details */
            $details = (array) ($group['itsaStatusDetails'] ?? []);
            foreach ($details as $detail) {
                $businessIncome = isset($detail['businessIncome2YearsPrior'])
                    ? (string) $detail['businessIncome2YearsPrior']
                    : '';
                $normalized[] = [
                    'taxYear'      => $taxYear,
                    'submittedOn'  => $this->stringOrEmpty($detail, 'submittedOn'),
                    'status'       => $this->stringOrEmpty($detail, 'status'),
                    'statusReason' =>
                        $this->stringOrEmpty($detail, 'statusReason'),
                    'businessIncome2YearsPrior' => $businessIncome,
                ];
            }
        }
        return $normalized;
    }

    /**
     * The current UK tax year in HMRC's own "YYYY-YY" format (e.g.
     * "2026-27"), used as itsaStatus()'s default when no ?taxYear=
     * query parameter is given. The UK tax year runs 6 April to 5
     * April; deliberately a fresh calculation from today's date rather
     * than DateHelper::taxYearToImmutable() -- that method reads a
     * configurable "this_tax_year_from_date" setting used for internal
     * sales-by-year reporting, not necessarily 6 April, whereas HMRC's
     * own tax year boundary is fixed and not user-configurable.
     */
    private function currentUkTaxYear(): string
    {
        $today = new \DateTimeImmutable('today');
        $year = (int) $today->format('Y');
        $boundary = $today->setDate($year, 4, 6);
        $startYear = $today < $boundary ? $year - 1 : $year;
        return $startYear . '-' . substr((string) ($startYear + 1), 2, 2);
    }

    /**
     * List, and trigger, MTD Self Assessment Tax Calculations for the
     * configured NINO and a tax year (defaults to the current UK tax
     * year, overridable via ?taxYear=YYYY-YY). Confirmed live against
     * HMRC's real individual-calculations-api/8.0 OAS spec (fetched
     * 2026-09-11, not guessed):
     * GET .../individuals/calculations/{nino}/self-assessment/{taxYear}
     * (list, read:self-assessment) and
     * POST .../trigger/{calculationType} (write:self-assessment) --
     * both already correctly requested via the existing itsaEntry()
     * bundle, no catalogue scope fix needed (unlike Obligations/
     * National Insurance earlier this session) -- though the
     * catalogue's own version field WAS stale, see that entry's own
     * fix note in HmrcApiCatalogue::all().
     *
     * Deliberately doesn't render the "retrieve a single calculation"
     * endpoint's full response here -- its real shape is a large
     * nested object (metadata + inputs + calculation breakdown) far
     * beyond what a lightweight test page needs; viewCalculation()
     * below shows it as raw JSON instead, same reasoning
     * selfEmploymentBusinesses()'s own view already uses for an
     * unexpected/error response.
     * https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/individual-calculations-api/8.0
     */
    public function individualCalculations(ServerRequest $request): Response
    {
        $guard = $this->ensureNinoAndToken();
        if ($guard instanceof Response) {
            return $guard;
        }
        [$nino, $tokenString] = $guard;
        $otpReference = (string) $this->session->get('otpRef');

        $queryParams = $request->getQueryParams();
        $taxYear = (string) ($queryParams['taxYear'] ?? $this->currentUkTaxYear());

        if ($request->getMethod() === Method::POST) {
            return $this->triggerCalculation(
                $request,
                $nino,
                $taxYear,
                $tokenString,
                $otpReference,
            );
        }

        $listRequest = $this->createRequest(
            'GET',
            $this->resolveHmrcApiBaseUrl()
                . '/individuals/calculations/' . urlencode($nino)
                . '/self-assessment/' . urlencode($taxYear),
        );
        $listRequest = RequestUtil::addHeaders($listRequest, array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.8.0+json',
                'Authorization' => 'Bearer ' . $tokenString,
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        ));

        $listResponse = $this->sendRequest($listRequest);
        /** @var array<string, mixed> $listParsed */
        $listParsed = (array) json_decode(
            $listResponse->getBody()->getContents(),
            true,
        );
        /** @var list<array<string, mixed>> $calculations */
        $calculations = $listParsed['calculations'] ?? [];

        return $this->webViewRenderer->render('individualCalculations', [
            'alert'        => $this->alert(),
            'nino'         => $nino,
            'taxYear'      => $taxYear,
            'statusCode'   => $listResponse->getStatusCode(),
            'calculations' => $calculations,
        ]);
    }

    /**
     * POST half of individualCalculations() -- separated out purely to
     * keep that method's GET/POST branches each readable on their own,
     * same split incomeTaxObligations()'s own helpers use for a
     * different reason (response-shape normalization there, request-
     * building here).
     */
    private function triggerCalculation(
        ServerRequest $request,
        string $nino,
        string $taxYear,
        string $tokenString,
        string $otpReference,
    ): Response {
        $body = $request->getParsedBody();
        /** @var array<string, string> $body */
        $body = is_array($body) ? $body : [];
        $calculationType = $body['calculationType'] ?? 'in-year';

        $triggerRequest = $this->createRequest(
            'POST',
            $this->resolveHmrcApiBaseUrl()
                . '/individuals/calculations/' . urlencode($nino)
                . '/self-assessment/' . urlencode($taxYear)
                . '/trigger/' . urlencode($calculationType),
        );
        $triggerRequest = RequestUtil::addHeaders($triggerRequest, array_merge(
            [
                'Accept'        => 'application/vnd.hmrc.8.0+json',
                'Authorization' => 'Bearer ' . $tokenString,
                'Content-Type'  => 'application/json',
            ],
            $this->getWebAppViaServerHeaders($otpReference),
        ));
        $triggerRequest = $triggerRequest->withBody(
            \GuzzleHttp\Psr7\Utils::streamFor('{}'),
        );

        $triggerResponse = $this->sendRequest($triggerRequest);
        /** @var array<string, mixed> $triggerParsed */
        $triggerParsed = (array) json_decode(
            $triggerResponse->getBody()->getContents(),
            true,
        );

        if ($triggerResponse->getStatusCode() === 202) {
            $this->flashMessage('success', $this->translator->translate(
                'mtd.individual.calculations.triggered',
                [
                    'calculationId' =>
                        (string) ($triggerParsed['calculationId'] ?? ''),
                ],
            ));
        } else {
            $this->flashMessage('danger', $this->translator->translate(
                'mtd.individual.calculations.trigger.error',
                [
                    'code'    => (string) ($triggerParsed['code'] ?? ''),
                    'message' => (string) ($triggerParsed['message'] ?? ''),
                ],
            ));
        }

        return $this->webService->getRedirectResponse(
            'backend/hmrc/individualCalculations',
            [],
            ['taxYear' => $taxYear],
        );
    }

    /**
     * Per-category metadata for the eight MTD "Income Received"
     * replacement APIs -- see HmrcApiCatalogue::all()'s own 2026-09-11
     * addendum for the live-confirmed finding that the old single
     * "Income Received" API was deprecated and split into these eight
     * independently-versioned APIs. Every one of them shares
     * read:self-assessment/write:self-assessment (confirmed live
     * against each one's own OAS spec) -- already correctly requested
     * via the existing itsaEntry() bundle -- so this table only needs
     * to record what actually differs per category: the URL path
     * segment, display label, Accept header version, whether the tax
     * year is part of the URL (savings' list endpoint is keyed by NINO
     * alone -- taxYear only appears on its per-account detail
     * endpoint, which this lightweight test page doesn't drill into),
     * and any extra path suffix (partner-income's list endpoint needs
     * a trailing /partnership).
     *
     * Built via a loop over a compact tuple table rather than eight
     * repeated 'key' => [...] array literals -- that shape (tried
     * first) is exactly what SonarCloud's own new_duplicated_lines_density
     * flagged as real duplication, the same CPD mechanism documented
     * in HmrcApiCatalogue::incomeReceivedReplacementEntries()'s own
     * docblock, generalized here to a table with more columns.
     *
     * @return array<string, array{
     *     segment: string,
     *     label: string,
     *     version: string,
     *     needsTaxYear: bool,
     *     suffix: string,
     * }>
     */
    private static function incomeCategories(): array
    {
        // [slug, segment, label, version, needsTaxYear, suffix]
        $categories = [
            ['dividends', 'dividends-income', 'Dividends Income', '2.0', true, ''],
            [
                'employments', 'employments-income',
                'Employments Income', '2.0', true, '',
            ],
            ['foreign', 'foreign-income', 'Foreign Income', '2.0', true, ''],
            [
                'insurance-policies', 'insurance-policies-income',
                'Insurance Policies Income', '2.0', true, '',
            ],
            ['other', 'other-income', 'Other Income', '2.0', true, ''],
            // Partner is the only one of the eight on its own version
            // track (1.0, not 2.0) -- confirmed live, not assumed to
            // match its siblings.
            [
                'partner', 'partner-income', 'Partner Income', '1.0',
                true, '/partnership',
            ],
            ['pensions', 'pensions-income', 'Pensions Income', '2.0', true, ''],
            // Savings' list endpoint is keyed by NINO alone -- taxYear
            // only appears on its per-account detail endpoint, which
            // this lightweight test page doesn't drill into.
            [
                'savings', 'savings-income/uk-accounts',
                'Savings Income (UK Accounts)', '2.0', false, '',
            ],
        ];

        $result = [];
        foreach (
            $categories as
            [$slug, $segment, $label, $version, $needsTaxYear, $suffix]
        ) {
            $result[$slug] = [
                'segment'      => $segment,
                'label'        => $label,
                'version'      => $version,
                'needsTaxYear' => $needsTaxYear,
                'suffix'       => $suffix,
            ];
        }
        return $result;
    }
}
