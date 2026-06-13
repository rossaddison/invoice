<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

use App\Invoice\Setting\SettingRepository;
use Yiisoft\Aliases\Aliases;

/**
 * Provides Peppol code-list data for UI dropdowns and form selects.
 *
 * NOT the same as CodeList / CodeLists — those perform validation membership
 * tests ("is this value a permitted code?") using flat string[] arrays from
 * resources/peppol/*.php.  This class returns richer Id+Name+Description
 * structures loaded from VEFA XML files for rendering <select> elements.
 *
 * Data sources
 * ────────────
 * DownloadedXml/*.xml  (this directory)
 *   VEFA-format XML files downloaded from OpenPEPPOL GitHub / Peppol docs.
 *   Loaded at runtime by loadVefaCodeList() via DOMXPath.
 *   Used by: getIso6523Icd(), getUncl7143(), getUncl5305(), getChargesArray(),
 *            getChargesArrayAsAtAugust2023(), electronicAddressScheme()
 *
 * resources/peppol/*.php  (project root)
 *   Flat PHP arrays consumed by CodeList::contains() inside PeppolValidator.
 *   Not used here — validation only, no UI involvement.
 *
 * Overlap: eas.xml ↔ resources/peppol/eaid.php (EAS scheme IDs)
 *          UNCL7161.xml ↔ resources/peppol/uncl7161.php (charge reason codes)
 *   Both folders must be refreshed when the upstream code list changes.
 *   See DownloadedXml/README.md and resources/peppol/README.md for update guidance.
 */
final class PeppolArrays
{
    /**
     * August 2023
     * Related logic: see https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5189/
     * @return array
     */
    public function getAllowancesSubsetArray(): array
    {
        return [
            '41' => 'Bonus for works ahead of schedule',
            '42' => 'Other Bonus',
            '60' => 'Manufacturer’s consumer discount',
            '64' => 'Special agreement',
            '65' => 'Production error discount',
            '66' => 'New outlet discount',
            '67' => 'Sample discount',
            '68' => 'End-of-range discount',
            '70' => 'Incoterm discount',
            '71' => 'Point of sales threshold allowance',
            '88' => 'Material surcharge/deduction',
            '95' => 'Discount',
            '100' => 'Special rebate',
            '102' => 'Fixed long term',
            '103' => 'Temporary',
            '104' => 'Standard',
            '105' => 'Yearly turnover',
        ];
    }

    /**
     * Peppol-approved charge reason codes (UNCL7161 D.16B).
     * Loaded from UNCL7161.xml — canonical source and quarterly update guidance: resources/peppol/uncl7161.php
     * Upstream: https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL7161/
     * @return array<string, array{0: string, 1: string}>
     */
    public function getChargesArrayAsAtAugust2023(): array
    {
        return self::loadUncl7161();
    }

    /**
     * Full UNCL7161 charge reason code list.
     * Loaded from UNCL7161.xml — canonical source and quarterly update guidance: resources/peppol/uncl7161.php
     * Upstream: https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL7161/
     * @return array<string, array{0: string, 1: string}>
     */
    public function getChargesArray(): array
    {
        return self::loadUncl7161();
    }

    /**
     * @return list<array{Id: string, Name: string, Description: string}>
     */
    private static function loadVefaCodeList(string $filename): array
    {
        $aliases = new Aliases(['@peppol' => __DIR__ . '/DownloadedXml']);
        $dom = new \DOMDocument();
        if (!$dom->load($aliases->get('@peppol') . '/' . $filename)) {
            return [];
        }
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('cl', 'urn:fdc:difi.no:2017:vefa:structure:CodeList-1');
        /** @var \DOMNodeList<\DOMElement>|false $nodes */
        $nodes = $xpath->query('//cl:Code');
        if ($nodes === false) {
            return [];
        }
        $codes = [];
        foreach ($nodes as $node) {
            $idList   = $xpath->query('cl:Id', $node);
            $nameList = $xpath->query('cl:Name', $node);
            $descList = $xpath->query('cl:Description', $node);
            $codes[] = [
                'Id'          => $idList   !== false ? ($idList->item(0)?->textContent   ?? '') : '',
                'Name'        => $nameList !== false ? ($nameList->item(0)?->textContent ?? '') : '',
                'Description' => trim($descList !== false ? ($descList->item(0)?->textContent ?? '') : ''),
            ];
        }
        return $codes;
    }

    /** @return array<string, array{0: string, 1: string}> */
    private static function loadUncl7161(): array
    {
        $codes = [];
        foreach (self::loadVefaCodeList('UNCL7161.xml') as $entry) {
            if ($entry['Id'] === '') {
                continue;
            }
            $codes[$entry['Id']] = [$entry['Name'], $entry['Description']];
        }
        return $codes;
    }

    /**
     * Used with product/edit and clientpeppol/add and edit
     * Related logic: see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/icd.xml
     * @return list<array{Id: string, Name: string, Description: string}>
     */
    public function getIso6523Icd(): array
    {
        return self::loadVefaCodeList('icd.xml');
    }

    /**
     * Related logic: see https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/UNCL7143.xml
     * @return list<array{Id: string, Name: string, Description: string}>
     */
    public function getUncl7143(): array
    {
        return self::loadVefaCodeList('uncl7143.xml');
    }

    /**
     * Duty or tax or fee category code (Subset of UNCL5305, D.16B, OpenPEPPOL).
     * Loaded from UNCL5305.xml — upstream: https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5305/
     * @return list<array{Id: string, Name: string, Description: string}>
     */
    public function getUncl5305(): array
    {
        return self::loadVefaCodeList('UNCL5305.xml');
    }

    /**
     * The three UNCL2005 date/time codes permitted by Peppol BIS Billing 3.0 for
     * cbc:DescriptionCode inside cac:InvoicePeriod (business rule BR-CL-23).
     * Canonical source and quarterly update guidance: resources/peppol/uncl2005.php
     * Upstream: https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL2005/
     * @return array
     */
    public function getUncl2005subset(): array
    {
        return [
            0 => [
                '@id' => 'uncl2005:Invoice_document_issue_date_time',
                '@type' => 'uncefact:UNCL2005Code',
                'rdf:value' => '3',
                'rdfs:comment' => '[2377] Date of issue of an invoice.',
            ],
            1 => [
                '@id' => 'uncl2005:Delivery_date/time_actual',
                '@type' => 'uncefact:UNCL2005Code',
                'rdf:value' => '35',
                'rdfs:comment' => 'Date/time on which goods or consignment are delivered at their destination.',
            ],
            2 => [
                '@id' => 'uncl2005:Paid_to_date',
                '@type' => 'uncefact:UNCL2005Code',
                'rdf:value' => '432',
                'rdfs:comment' => 'Date to which payments have been paid.',
            ],
        ];
    }

    /**
     * Related logic: see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoicePeriod/cbc-DescriptionCode/
     * Related logic: see InvController/edit function;
     * @return string
     */
    public function getCurrentStandInCodeValue(SettingRepository $s): string
    {
        $uncl2005_subset = $this->getUncl2005subset();
        $current_stand_in_code_value = '';
        /**
         * @var array $value
         */
        foreach ($uncl2005_subset as $value) {
            /**
             * @var string $value['rdf:value']
             */
            $rdf_value = $value['rdf:value'];
            if ($s->getSetting('stand_in_code') == $rdf_value) {
                /**
                 * @var string $value['rdfs:comment']
                 */
                $current_stand_in_code_value = $value['rdfs:comment'];
            }
        }
        return $current_stand_in_code_value;
    }

    /**
     * Electronic Address Scheme (EAS).
     * Loaded from eas.xml — upstream: https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/
     * @return list<array{Id: string, Name: string, Description: string}>
     */
    public static function electronicAddressScheme(): array
    {
        return self::loadVefaCodeList('eas.xml');
    }
}
