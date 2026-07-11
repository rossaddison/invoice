<?php

declare(strict_types=1);

namespace App\Invoice\Peppol;

use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Invoice\Inv\InvPeppolChargeDeps;
use App\Invoice\Inv\InvPeppolCoreDeps;
use App\Invoice\Inv\InvPeppolInvDeps;
use App\Invoice\Inv\InvPeppolNetworkDeps;
use App\Invoice\Helpers\Peppol\PeppolHelper;
use App\Invoice\Helpers\Peppol\PeppolHelperChargeDeps;
use App\Invoice\Helpers\Peppol\PeppolHelperInvDeps;
use App\Invoice\Helpers\Peppol\PeppolHelperNetDeps;
use App\Invoice\Setting\SettingRepository;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Generates the UBL 2.4 XML for an invoice outside of the HTTP request
 * lifecycle (e.g. for the MCP `invoice_send_peppol` tool), reusing the same
 * `PeppolHelper` assembly that `App\Invoice\Inv\Trait\Peppol` drives from
 * the web controller.
 */
final class UblXmlGeneratorService
{
    public function __construct(
        private readonly SettingRepository $sR,
        private readonly TranslatorInterface $translator,
        private readonly InvPeppolCoreDeps $core,
        private readonly InvPeppolNetworkDeps $net,
        private readonly InvPeppolChargeDeps $charge,
        private readonly InvPeppolInvDeps $inv,
    ) {
    }

    /**
     * @throws \RuntimeException if the invoice, its client's Peppol setup,
     *     or its delivery location are missing, or if XML generation fails.
     */
    public function generate(int $invId): string
    {
        $invoice = $this->core->invRepo->repoInvLoadInvAmountquery($invId);
        if ($invoice === null) {
            throw new \RuntimeException("Invoice #{$invId} not found.");
        }

        $clientId = $invoice->getClient()?->reqId() ?? 0;
        if ($clientId <= 0 || !$this->clientFullySetupForPeppol($clientId)) {
            throw new \RuntimeException(
                $this->translator->translate('peppol.client.check'),
            );
        }

        $delloc = $this->core->dlR->repoDeliveryLocationquery(
            (int) $invoice->getDeliveryLocationId(),
        );
        if ($delloc === null) {
            throw new \RuntimeException(
                $this->translator->translate('delivery.location.peppol.output'),
            );
        }

        $peppolHelper = new PeppolHelper(
            $this->sR,
            $this->net->delRepo,
            $invoice->getInvAmount(),
            $delloc,
            $this->translator,
        );

        $xmlPath = $peppolHelper->generateInvoicePeppolUblXmlTempFile(
            $invoice,
            new PeppolHelperInvDeps(
                $this->core->soR, $this->inv->iaR, $this->core->iiaR,
                $this->inv->iiR, $this->core->paR, $this->core->cpR,
            ),
            new PeppolHelperNetDeps(
                $this->net->contractRepo, $this->net->delRepo,
                $this->net->delPartyRepo, $this->net->unpR, $this->net->upR,
            ),
            new PeppolHelperChargeDeps(
                $this->charge->aciR, $this->charge->aciiR,
                $this->charge->soiR, $this->charge->trR,
            ),
        );

        $ublXml = file_get_contents($xmlPath);
        if ($ublXml === false || strlen($ublXml) === 0) {
            throw new \RuntimeException(
                $this->translator->translate('peppol.xml.generation.failed'),
            );
        }

        return $ublXml;
    }

    private function clientFullySetupForPeppol(int $clientId): bool
    {
        if ($this->core->cpR->repoClientCount($clientId) !== 1) {
            return false;
        }
        $cp = $this->core->cpR->repoClientPeppolLoadedquery($clientId);
        return $cp !== null && $this->hasRequiredPeppolFields($cp);
    }

    private function hasRequiredPeppolFields(ClientPeppol $cp): bool
    {
        return $cp->getEndpointid() !== ''
            && $cp->getEndpointidSchemeid() !== ''
            && $cp->getIdentificationid() !== ''
            && $cp->getIdentificationidSchemeid() !== ''
            && $cp->getTaxschemecompanyid() !== ''
            && $cp->getTaxschemeid() !== ''
            && $cp->getLegalEntityRegistrationName() !== ''
            && $cp->getLegalEntityCompanyid() !== ''
            && $cp->getLegalEntityCompanyidSchemeid() !== ''
            && $cp->getLegalEntityCompanyLegalForm() !== ''
            && $cp->getFinancialInstitutionBranchid() !== ''
            && $cp->getAccountingCost() !== ''
            && $cp->getSupplierAssignedAccountId() !== '';
    }
}
