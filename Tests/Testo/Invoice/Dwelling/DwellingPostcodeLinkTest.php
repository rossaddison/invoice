<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Dwelling;

use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\Dwelling\Dwelling;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Dwelling\DwellingPostcodeLink;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\Inv\Widget\Trait\InvsDwellingPostcodeColumnTrait;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * The postcode cell shared by the inv/index and inv/guest grids: Inv → Client
 * → Dwelling → Google Maps search link.
 */
#[Test]
final class DwellingPostcodeLinkTest
{
    private function repository(?int $expectedId, ?Dwelling $dwelling): DwellingRepository
    {
        /** @var DwellingRepository&m\MockInterface $dwR */
        $dwR = m::mock(DwellingRepository::class);
        if ($expectedId === null) {
            $dwR->shouldNotReceive('repoDwellingQuery');
        } else {
            $dwR->shouldReceive('repoDwellingQuery')->once()->with($expectedId)->andReturn($dwelling);
        }
        return $dwR;
    }

    private function dwelling(string $postcode): Dwelling
    {
        return new Dwelling(family_id: 1, house_number_numeric: 12, postcode: $postcode);
    }

    private function invWithDwellingId(?int $dwellingId): Inv
    {
        $client = new Client();
        if ($dwellingId !== null) {
            $client->setDwellingId($dwellingId);
        }
        $inv = new Inv();
        $inv->setClient($client);
        return $inv;
    }

    public function linksThePostcodeToAGoogleMapsSearchInANewTab(): void
    {
        $html = DwellingPostcodeLink::html(5, $this->repository(5, $this->dwelling('SW1A1AA')));

        Assert::same(
            $html,
            '<a target="_blank" rel="noopener noreferrer" '
            . 'href="https://www.google.com/maps/search/?api=1&amp;query=SW1A1AA">SW1A1AA</a>'
        );
    }

    public function urlEncodesAndHtmlEscapesTheStoredPostcode(): void
    {
        $html = DwellingPostcodeLink::html(5, $this->repository(5, $this->dwelling('A B<&>')));

        Assert::true(str_contains($html, 'query=A%20B%3C%26%3E"'));
        Assert::true(str_contains($html, '>A B&lt;&amp;&gt;</a>'));
    }

    public function showsAnHourglassWithoutADwellingIdAndNeverQueries(): void
    {
        Assert::same(DwellingPostcodeLink::html(null, $this->repository(null, null), 'Pending'), '<span title="Pending" aria-label="Pending">⏳</span>');
    }

    public function showsAnHourglassWhenTheDwellingRowIsMissing(): void
    {
        Assert::same(DwellingPostcodeLink::html(9, $this->repository(9, null), 'Pending'), '<span title="Pending" aria-label="Pending">⏳</span>');
    }

    public function showsAnHourglassWhenTheDwellingHasNoPostcode(): void
    {
        Assert::same(DwellingPostcodeLink::html(5, $this->repository(5, $this->dwelling('')), 'Pending'), '<span title="Pending" aria-label="Pending">⏳</span>');
    }

    public function invIndexColumnRendersTheClientsDwellingPostcode(): void
    {
        $column = $this->column($this->repository(5, $this->dwelling('EC1A1BB')), true);

        Assert::true(str_contains($this->cell($column, $this->invWithDwellingId(5)), '>EC1A1BB</a>'));
    }

    public function invIndexColumnShowsAnHourglassForAClientWithoutADwelling(): void
    {
        $column = $this->column($this->repository(null, null), true);

        Assert::same(
            $this->cell($column, $this->invWithDwellingId(null)),
            '<span title="No Dwelling yet" aria-label="No Dwelling yet">⏳</span>'
        );
    }

    public function invIndexColumnIsOnlyVisibleWhenHomeCareIsEnabled(): void
    {
        Assert::true($this->column($this->repository(null, null), true)->isVisible());
        Assert::false($this->column($this->repository(null, null), false)->isVisible());
    }

    private function column(DwellingRepository $dwR, bool $homeCareEnabled): DataColumn
    {
        /** @var TranslatorInterface&m\MockInterface $translator */
        $translator = m::mock(TranslatorInterface::class);
        $translator->shouldReceive('translate')->with('dwelling.postcode')->andReturn('Postcode');
        $translator->shouldReceive('translate')->with('dwelling.postcode.pending')->andReturn('No Dwelling yet');

        $builder = new class ($translator) {
            use InvsDwellingPostcodeColumnTrait;

            public function __construct(private readonly TranslatorInterface $translator)
            {
            }

            public function build(DwellingRepository $dwR, bool $homeCareEnabled): DataColumn
            {
                return $this->buildDwellingPostcodeColumn($dwR, $homeCareEnabled);
            }
        };
        return $builder->build($dwR, $homeCareEnabled);
    }

    private function cell(DataColumn $column, Inv $inv): string
    {
        $content = $column->content;
        Assert::true($content instanceof \Closure);
        // The column closure only takes the Inv; invoke via reflection since the
        // declared callable type also expects a DataContext this test has no use for.
        return (string) new \ReflectionFunction($content)->invoke($inv);
    }
}
