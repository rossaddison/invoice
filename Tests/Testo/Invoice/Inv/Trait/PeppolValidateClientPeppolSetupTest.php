<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Trait;

use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Invoice\Inv\InvController;
use App\Service\WebControllerService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;

/**
 * validateClientPeppolSetup() (App\Invoice\Inv\Trait\Peppol) is only
 * exercised indirectly by PeppolResolveInvoiceAndDeliveryLocationTest's
 * happyPath/invalidClientPeppolSetup cases (both-ends-of-the-boolean, no
 * detail on *why*). This covers its own branches directly -- which
 * required field is reported missing, and the two ways
 * endpointidFormatProblem() can add a third kind of problem on top of the
 * required-fields loop -- plus, transitively, clientPeppolFieldLink()'s
 * URL construction (there's no separate entry point to reach it from).
 * Reflects into the real InvController, same technique as this
 * directory's other Peppol.php trait tests.
 */
#[Test]
final class PeppolValidateClientPeppolSetupTest
{
    private const int CLIENT_ID = 7;

    /**
     * A fully valid setup: every required field non-empty. Defaults to an
     * endpointid on a scheme (9999) with no registered checksum validator
     * and no '@' -- override both to test endpointidFormatProblem()'s own
     * branches while every other field stays valid.
     */
    private function fullyValidClientPeppol(
        string $endpointid = 'ABC123',
        string $endpointidSchemeid = '9999',
    ): ClientPeppol {
        return new ClientPeppol(
            client_id: self::CLIENT_ID,
            endpointid: $endpointid,
            endpointid_schemeid: $endpointidSchemeid,
            identificationid: 'ID1',
            identificationid_schemeid: '9999',
            taxschemecompanyid: 'TAX1',
            taxschemeid: 'VAT',
            legal_entity_registration_name: 'Acme Ltd',
            legal_entity_companyid: 'CO1',
            legal_entity_companyid_schemeid: '9999',
            legal_entity_company_legal_form: 'LTD',
            financial_institution_branchid: 'BR1',
            accounting_cost: 'AC1',
            supplier_assigned_accountid: 'SA1',
        );
    }

    /** @return array{0: bool, 1: string|null} [result, captured flash message HTML] */
    private function call(ClientPeppol $cp): array
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $id): string => $id);

        $captured = null;

        /** @var Flash&m\MockInterface $flash */
        $flash = m::mock(Flash::class);
        $flash->shouldReceive('has')->andReturn(false);
        $flash->shouldReceive('add')->andReturnUsing(function () use (&$captured): void {
            $args = func_get_args();
            $captured = (string) $args[1];
        });

        $psr17 = new Psr17Factory();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        $urlGenerator->shouldReceive('generate')->andReturnUsing(
            /** @param array<string, scalar> $args */
            static function (string $route, array $args = []): string {
                $clientId = $args['client_id'] ?? '';
                return '/' . $route . '/' . (string) $clientId;
            },
        );
        $webService = new WebControllerService($psr17, $psr17, $urlGenerator);

        $refClass = new ReflectionClass(InvController::class);
        $instance = $refClass->newInstanceWithoutConstructor();
        $refClass->getProperty('translator')->setValue($instance, $translator);
        $refClass->getProperty('flash')->setValue($instance, $flash);
        $refClass->getProperty('webService')->setValue($instance, $webService);

        $method = $refClass->getMethod('validateClientPeppolSetup');

        /** @var bool $result */
        $result = $method->invoke($instance, $cp);
        return [$result, $captured];
    }

    public function fullyValidSetupReturnsTrueAndFlashesNothing(): void
    {
        [$result, $captured] = $this->call($this->fullyValidClientPeppol());

        Assert::true($result);
        Assert::null($captured);
    }

    public function missingRequiredFieldReturnsFalseAndFlashesALinkToIt(): void
    {
        $cp = new ClientPeppol(client_id: self::CLIENT_ID);

        [$result, $captured] = $this->call($cp);

        Assert::false($result);
        Assert::notNull($captured);
        // clientPeppolFieldLink() links straight to clientpeppol/edit for
        // this client, via generateUrl -- confirms the link was actually
        // built, not just that *some* message was flashed.
        Assert::true(str_contains($captured, '/clientpeppol/edit/' . self::CLIENT_ID));
    }

    public function emailShapedEndpointidReturnsFalseEvenWhenEveryOtherFieldIsValid(): void
    {
        $cp = $this->fullyValidClientPeppol(endpointid: 'joe.bloggs@web.com', endpointidSchemeid: '0088');

        [$result, $captured] = $this->call($cp);

        Assert::false($result);
        Assert::notNull($captured);
        Assert::true(str_contains($captured, 'peppol.endpointid.looks.like.email'));
    }

    public function invalidChecksumEndpointidReturnsFalseEvenWhenEveryOtherFieldIsValid(): void
    {
        // Scheme 0088 (GLN) requires a checksum-valid identifier; this is
        // 13 digits but not GLN-valid.
        $cp = $this->fullyValidClientPeppol(endpointid: '1234567890123', endpointidSchemeid: '0088');

        [$result, $captured] = $this->call($cp);

        Assert::false($result);
        Assert::notNull($captured);
        Assert::true(str_contains($captured, 'peppol.endpointid.checksum.invalid'));
    }
}
