<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareAlias\HomeCareAlias;

/**
 * Works out which real run or worker a name written in the schedule means. In order: a match a
 * person has confirmed before (an alias), the same name once punctuation and capitals are ignored,
 * then a single unambiguous abbreviation ("Nor 2" for "North 2", "E. Town" for "East
 * Town", "Andy" for "Andrew"). Anything else is left unmatched for the preview to ask about,
 * never guessed.
 */
final readonly class ScheduleNameMatcher
{
    /** Common short forms of first names, each to the forms it may stand for. */
    private const array NICKNAMES = [
        'andy' => ['andrew'], 'dan' => ['daniel'], 'danny' => ['daniel'], 'dave' => ['david'], 'davie' => ['david'],
        'ben' => ['benjamin'], 'bill' => ['william'], 'will' => ['william'], 'billy' => ['william'],
        'bob' => ['robert'], 'rob' => ['robert'], 'robbie' => ['robert'], 'chris' => ['christopher'],
        'jim' => ['james'], 'jimmy' => ['james'], 'joe' => ['joseph'], 'matt' => ['matthew'],
        'mike' => ['michael'], 'nick' => ['nicholas'], 'pete' => ['peter'], 'sam' => ['samuel'],
        'steve' => ['stephen', 'steven'], 'tom' => ['thomas'], 'tommy' => ['thomas'], 'tony' => ['anthony'],
        'ally' => ['alistair', 'alasdair'], 'cal' => ['callum'], 'ross' => ['ross'],
    ];

    public function __construct(private HomeCareAliasRepositoryInterface $aliases)
    {
    }

    /**
     * Lower case, apostrophes and full stops removed, other punctuation as a space, single spaces.
     */
    public function normalise(string $name): string
    {
        $name = mb_strtolower($name);
        $name = str_replace(["'", '’', '.'], '', $name);
        $name = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);
        return trim((string) preg_replace('/\s+/', ' ', $name));
    }

    /**
     * @param array<int, string> $runs run id => name
     * @return int|null the run id, or null when it is not certain
     */
    public function matchRun(string $name, array $runs): ?int
    {
        return $this->match(HomeCareAlias::KIND_RUN, $name, $runs, false);
    }

    /**
     * @param array<int, string> $workers worker id => first name
     * @return int|null the worker id, or null when it is not certain
     */
    public function matchWorker(string $name, array $workers): ?int
    {
        return $this->match(HomeCareAlias::KIND_WORKER, $name, $workers, true);
    }

    /**
     * Remembers that this written name means that target, so next month's schedule maps by itself.
     */
    public function remember(string $kind, string $name, int $targetId): void
    {
        $alias = $this->normalise($name);
        if ('' === $alias) {
            return;
        }
        $existing = $this->aliases->repoLookupquery($kind, $alias);
        if (null === $existing) {
            $this->aliases->save(new HomeCareAlias($kind, $alias, $targetId));
            return;
        }
        if ($existing->getTargetId() !== $targetId) {
            $existing->pointTo($targetId);
            $this->aliases->save($existing);
        }
    }

    /**
     * @param array<int, string> $candidates id => name
     */
    private function match(string $kind, string $name, array $candidates, bool $nicknames): ?int
    {
        $query = $this->normalise($name);
        if ('' === $query) {
            return null;
        }
        $known = $this->aliases->repoLookupquery($kind, $query);
        if (null !== $known && isset($candidates[$known->getTargetId()])) {
            return $known->getTargetId();
        }
        $normalised = array_map($this->normalise(...), $candidates);
        $exact = array_keys(array_filter($normalised, static fn (string $c): bool => $c === $query));
        if (1 === count($exact)) {
            return $exact[0];
        }
        $abbreviated = array_keys(array_filter(
            $normalised,
            fn (string $c): bool => $this->abbreviates($query, $c, $nicknames)
        ));
        return 1 === count($abbreviated) ? $abbreviated[0] : null;
    }

    /**
     * True when each word of the query is the start of the candidate's word in the same place
     * ("e kilbride" of "east kilbride"). For a person's first name only a known short form counts
     * ("Andy" for "Andrew"): a bare prefix is too loose, since "Sam" starts "Samantha" and "Dan"
     * starts "Danielle", who are different people.
     */
    private function abbreviates(string $query, string $candidate, bool $nicknames): bool
    {
        if ($nicknames) {
            return in_array($candidate, self::NICKNAMES[$query] ?? [], true);
        }
        $q = explode(' ', $query);
        $c = explode(' ', $candidate);
        if (count($q) !== count($c)) {
            return false;
        }
        foreach ($q as $i => $word) {
            if (!str_starts_with($c[$i], $word)) {
                return false;
            }
        }
        return true;
    }
}
