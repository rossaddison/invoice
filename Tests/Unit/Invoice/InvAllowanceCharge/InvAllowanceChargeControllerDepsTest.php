<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\InvAllowanceCharge;

use App\Invoice\InvAllowanceCharge\InvAllowanceChargeControllerDeps;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeService;
use App\Service\WebControllerService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InvAllowanceChargeControllerDeps.
 *
 * InvAllowanceChargeControllerDeps is a parameter-object that groups the two
 * constructor dependencies needed by InvAllowanceChargeController, reducing the
 * controller's constructor parameter count (SonarQube S107).
 *
 * Both wrapped services are final readonly classes and cannot be stubbed via
 * createStub().  Bare instances created with newInstanceWithoutConstructor() are
 * used instead — their methods are never called, only their identity is tested.
 */
final class InvAllowanceChargeControllerDepsTest extends TestCase
{
    public function testConstructorAssignsWebService(): void
    {
        $webService = (new \ReflectionClass(WebControllerService::class))
            ->newInstanceWithoutConstructor();
        $invService = (new \ReflectionClass(InvAllowanceChargeService::class))
            ->newInstanceWithoutConstructor();

        $deps = new InvAllowanceChargeControllerDeps($webService, $invService);

        $this->assertSame($webService, $deps->webService);
    }

    public function testConstructorAssignsInvAllowanceChargeService(): void
    {
        $webService = (new \ReflectionClass(WebControllerService::class))
            ->newInstanceWithoutConstructor();
        $invService = (new \ReflectionClass(InvAllowanceChargeService::class))
            ->newInstanceWithoutConstructor();

        $deps = new InvAllowanceChargeControllerDeps($webService, $invService);

        $this->assertSame($invService, $deps->invallowancechargeService);
    }

    public function testBothPropertiesAreAccessibleAfterConstruction(): void
    {
        $webService = (new \ReflectionClass(WebControllerService::class))
            ->newInstanceWithoutConstructor();
        $invService = (new \ReflectionClass(InvAllowanceChargeService::class))
            ->newInstanceWithoutConstructor();

        $deps = new InvAllowanceChargeControllerDeps($webService, $invService);

        // Both properties are accessible (public) and immutable (readonly):
        // assigning would throw an Error; just verify identity round-trips correctly.
        $this->assertSame($webService, $deps->webService);
        $this->assertSame($invService, $deps->invallowancechargeService);
    }
}
