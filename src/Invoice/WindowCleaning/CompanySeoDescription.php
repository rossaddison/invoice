<?php

declare(strict_types=1);

namespace App\Invoice\WindowCleaning;

use App\Infrastructure\Persistence\Company\Company;
use App\Invoice\Company\CompanyRepository;
use App\Invoice\Enum\ServiceType;

/**
 * Fills the current company's SEO description (Company > SEO, the page's meta description) with the
 * business's speciality: the services it offers, for example "Window Cleaning, Bin Cleaning and Hedge
 * Cutting". Search engines and anyone sharing the link then see what the business does.
 *
 * Only the business's own wording is safe from this: the description is written when it is empty, or
 * when it still holds exactly what was written here for the services offered before, so ticking
 * different services keeps it up to date, while text the business wrote itself is never replaced.
 */
final readonly class CompanySeoDescription
{
    public function __construct(private CompanyRepository $cR)
    {
    }

    /**
     * @param list<ServiceType> $services the services offered now
     * @param list<ServiceType> $previous the services offered before this change
     * @return bool whether the description was written
     */
    public function fill(array $services, array $previous): bool
    {
        $company = $this->cR->repoCompanyActivequery();
        $description = self::describe($services);
        // nothing to write to, nothing to write, or the business's own wording to leave alone
        if (null === $company || '' === $description || !$this->mayBeReplaced($company, $previous)) {
            return false;
        }
        $company->setSeoDescription($description);
        $this->cR->save($company);
        return true;
    }

    /**
     * Whether the company's SEO description is empty or still exactly what was written for the
     * services offered before, rather than the business's own wording.
     *
     * @param list<ServiceType> $previous
     */
    private function mayBeReplaced(Company $company, array $previous): bool
    {
        $current = trim($company->getSeoDescription() ?? '');
        return '' === $current || $current === self::describe($previous);
    }

    /**
     * "Window Cleaning", "Window Cleaning and Bin Cleaning", "Window Cleaning, Bin Cleaning and Hedge
     * Cutting"; '' for no services.
     *
     * @param list<ServiceType> $services
     */
    public static function describe(array $services): string
    {
        $names = array_map(static fn (ServiceType $service): string => $service->productName(), $services);
        if (count($names) < 2) {
            return $names[0] ?? '';
        }
        $last = array_pop($names);
        return implode(', ', $names) . ' and ' . $last;
    }
}
