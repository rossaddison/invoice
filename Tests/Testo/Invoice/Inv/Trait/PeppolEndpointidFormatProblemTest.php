<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Trait;

use App\Invoice\Inv\InvController;
use Mockery as m;
use ReflectionClass;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Translator\TranslatorInterface;

/**
 * endpointidFormatProblem() (App\Invoice\Inv\Trait\Peppol) is private,
 * only reachable in production through validateClientPeppolSetup() --
 * which needs a large DI graph (repositories, CurrentUser,
 * WebControllerService, ...) unrelated to this method's own pure
 * string-validation logic. Reflects into the real InvController --
 * already-valid production code -- rather than authoring a new class
 * that `use`s the trait directly: the trait's *other* methods reference
 * many properties (factory, webService, flash, ...) declared on
 * InvController/BaseController, and Psalm statically analyzes the whole
 * trait body once mixed in, regardless of which one method is actually
 * called, so a minimal hand-rolled stub class fails Psalm even though
 * nothing it doesn't declare is ever touched at runtime.
 *
 * newInstanceWithoutConstructor() skips InvController's full DI
 * constructor entirely; only the `translator` property (declared on
 * BaseController, which endpointidFormatProblem() actually reads) is set
 * via reflection. Every other typed property stays uninitialized, which
 * is safe here since nothing else on the object is ever touched.
 */
#[Test]
final class PeppolEndpointidFormatProblemTest
{
    private const string ENDPOINT_ID = '1234567890123';

    private function call(?string $endpointId, ?string $schemeId): ?string
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')
            ->andReturnUsing(static fn (string $id): string => $id);

        $refClass = new ReflectionClass(InvController::class);
        $instance = $refClass->newInstanceWithoutConstructor();

        $translatorProperty = $refClass->getProperty('translator');
        $translatorProperty->setValue($instance, $translator);

        $method = $refClass->getMethod('endpointidFormatProblem');

        /** @var string|null $result */
        $result = $method->invoke($instance, $endpointId, $schemeId);
        return $result;
    }

    public function returnsNullWhenEndpointIdIsNull(): void
    {
        Assert::null($this->call(null, '0088'));
    }

    public function returnsNullWhenEndpointIdIsEmpty(): void
    {
        Assert::null($this->call('', '0088'));
    }

    public function returnsNullWhenSchemeIdIsNull(): void
    {
        Assert::null($this->call(self::ENDPOINT_ID, null));
    }

    public function returnsNullWhenSchemeIdIsEmpty(): void
    {
        Assert::null($this->call(self::ENDPOINT_ID, ''));
    }

    public function flagsAnEmailLookingEndpointIdRegardlessOfScheme(): void
    {
        Assert::same(
            'peppol.endpointid.looks.like.email',
            $this->call('joe.bloggs@web.com', '0088'),
        );
    }

    public function flagsAnInvalidChecksumForAKnownScheme(): void
    {
        // Scheme 0088 (GLN) requires a checksum-valid identifier; this is
        // 13 digits but not GLN-valid.
        Assert::same(
            'peppol.endpointid.checksum.invalid (0088)',
            $this->call(self::ENDPOINT_ID, '0088'),
        );
    }

    public function returnsNullWhenSchemeHasNoKnownChecksum(): void
    {
        // No checksum validator registered for this scheme -- nothing to
        // flag beyond the '@' check, which this identifier also passes.
        Assert::null($this->call(self::ENDPOINT_ID, '9999'));
    }
}
