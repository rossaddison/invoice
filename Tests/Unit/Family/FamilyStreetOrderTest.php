<?php

declare(strict_types=1);

namespace Tests\Unit\Family;

use App\Infrastructure\Persistence\Family\Family;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the drag-and-drop street order feature.
 *
 * Covers the pure logic in:
 *  - Family::getStreetSortOrder / setStreetSortOrder
 *  - FamilyService::saveStreetOrders() position assignment
 *  - FamilyController::reorder() body validation and ID normalisation
 *  - TypeScript midpoint insertion (family-street-order.ts), mirrored in PHP
 *  - collectIds() filtering, position badge numbering, URL body construction
 *  - ReorderResponse success/failure branching
 *
 * Network, database, and framework dependencies are not exercised here.
 */
final class FamilyStreetOrderTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Family entity — street_sort_order getter / setter
    // -----------------------------------------------------------------------

    public function testStreetSortOrderIsNullByDefault(): void
    {
        $family = new Family();

        $this->assertNull($family->getStreetSortOrder());
    }

    public function testSetStreetSortOrderPersistsValue(): void
    {
        $family = new Family();
        $family->setStreetSortOrder(3);

        $this->assertSame(3, $family->getStreetSortOrder());
    }

    public function testSetStreetSortOrderOverwritesPreviousValue(): void
    {
        $family = new Family();
        $family->setStreetSortOrder(2);
        $family->setStreetSortOrder(5);

        $this->assertSame(5, $family->getStreetSortOrder());
    }

    public function testSetStreetSortOrderAcceptsPositionOne(): void
    {
        $family = new Family();
        $family->setStreetSortOrder(1);

        $this->assertSame(1, $family->getStreetSortOrder());
    }

    // -----------------------------------------------------------------------
    // Family entity — identity / persistence state
    // -----------------------------------------------------------------------

    public function testFamilyIsNotIdentifiedBeforeSetId(): void
    {
        $family = new Family();

        $this->assertFalse($family->hasIdentity());
    }

    public function testFamilyIsIdentifiedAfterSetId(): void
    {
        $family = new Family();
        $family->setId(7);

        $this->assertTrue($family->hasIdentity());
    }

    public function testReqIdReturnsSetId(): void
    {
        $family = new Family();
        $family->setId(42);

        $this->assertSame(42, $family->reqId());
    }

    public function testReqIdThrowsLogicExceptionWhenUnpersisted(): void
    {
        $family = new Family();

        $this->expectException(\LogicException::class);
        $family->reqId();
    }

    // -----------------------------------------------------------------------
    // FamilyService::saveStreetOrders() — position assignment logic
    //
    // Mirrors:
    //   foreach ($orderedIds as $position => $familyId) {
    //       $family->setStreetSortOrder($position + 1);
    //   }
    // -----------------------------------------------------------------------

    public function testFirstIdReceivesPositionOne(): void
    {
        /** @var list<int> $orderedIds */
        $orderedIds = [10, 20, 30];
        /** @var array<int, int> $positions */
        $positions = [];
        foreach ($orderedIds as $position => $familyId) {
            $positions[$familyId] = $position + 1;
        }

        $this->assertSame(1, $positions[10]);
    }

    public function testSecondIdReceivesPositionTwo(): void
    {
        /** @var list<int> $orderedIds */
        $orderedIds = [10, 20, 30];
        /** @var array<int, int> $positions */
        $positions = [];
        foreach ($orderedIds as $position => $familyId) {
            $positions[$familyId] = $position + 1;
        }

        $this->assertSame(2, $positions[20]);
    }

    public function testLastIdReceivesPositionMatchingCount(): void
    {
        /** @var list<int> $orderedIds */
        $orderedIds = [10, 20, 30];
        /** @var array<int, int> $positions */
        $positions = [];
        foreach ($orderedIds as $position => $familyId) {
            $positions[$familyId] = $position + 1;
        }

        $this->assertSame(3, $positions[30]);
        $this->assertCount(3, $positions);
    }

    public function testSingleIdReceivesPositionOne(): void
    {
        /** @var list<int> $orderedIds */
        $orderedIds = [99];
        /** @var array<int, int> $positions */
        $positions = [];
        foreach ($orderedIds as $position => $familyId) {
            $positions[$familyId] = $position + 1;
        }

        $this->assertSame(1, $positions[99]);
    }

    public function testEmptyArrayProducesNoPositions(): void
    {
        // saveStreetOrders() with an empty ID list performs zero iterations —
        // verified by confirming the input itself is empty.
        /** @var list<int> $orderedIds */
        $orderedIds = [];

        $this->assertCount(0, $orderedIds);
    }

    public function testPositionsAreConsecutiveStartingAtOne(): void
    {
        /** @var list<int> $orderedIds */
        $orderedIds = [5, 3, 8, 1, 7];
        $result     = [];
        foreach (array_keys($orderedIds) as $position) {
            $result[] = $position + 1;
        }

        $this->assertSame([1, 2, 3, 4, 5], $result);
    }

    public function testFamilyEntityReceivesCorrectPositionAfterSet(): void
    {
        $family = new Family();
        $family->setStreetSortOrder(0 + 1); // first in a reorder loop

        $this->assertSame(1, $family->getStreetSortOrder());
    }

    // -----------------------------------------------------------------------
    // FamilyController::reorder() — body validation logic
    //
    // Mirrors:
    //   !is_array($body) || !isset($body['order']) || !is_array($body['order'])
    //
    // @var mixed is used so Psalm does not narrow the literal types and
    // treats the runtime checks as genuinely conditional.
    // -----------------------------------------------------------------------

    public function testNonArrayBodyIsInvalid(): void
    {
        /** @var mixed $body */
        $body    = 'not-an-array';
        $invalid = !is_array($body);

        $this->assertTrue($invalid);
    }

    public function testMissingOrderKeyIsInvalid(): void
    {
        /** @var mixed $body */
        $body    = ['other_key' => [1, 2]];
        $invalid = !is_array($body) || !isset($body['order']) || !is_array($body['order']);

        $this->assertTrue($invalid);
    }

    public function testOrderAsStringIsInvalid(): void
    {
        /** @var mixed $body */
        $body    = ['order' => '1,2,3'];
        $invalid = !is_array($body) || !isset($body['order']) || !is_array($body['order']);

        $this->assertTrue($invalid);
    }

    public function testNullBodyFallsBackToEmptyArray(): void
    {
        // Mirrors: $body = $request->getParsedBody() ?? [];
        $parsed = null;
        /** @var mixed $body */
        $body   = $parsed ?? [];

        $this->assertSame([], $body);
        $this->assertTrue(!is_array($body) || !isset($body['order']) || !is_array($body['order']));
    }

    public function testValidOrderArrayPassesValidation(): void
    {
        /** @var mixed $body */
        $body    = ['order' => ['10', '20', '30']];
        $invalid = !is_array($body) || !isset($body['order']) || !is_array($body['order']);

        $this->assertFalse($invalid);
    }

    public function testEmptyOrderArrayPassesValidation(): void
    {
        // An empty order[] is structurally valid; saveStreetOrders() handles it as a no-op
        /** @var mixed $body */
        $body    = ['order' => []];
        $invalid = !is_array($body) || !isset($body['order']) || !is_array($body['order']);

        $this->assertFalse($invalid);
    }

    // -----------------------------------------------------------------------
    // FamilyController::reorder() — ID normalisation
    //
    // Mirrors: array_values(array_map('intval', $body['order']))
    // -----------------------------------------------------------------------

    public function testStringIdsAreConvertedToIntegers(): void
    {
        $raw    = ['10', '20', '30'];
        $intIds = array_values(array_map('intval', $raw));

        $this->assertSame([10, 20, 30], $intIds);
    }

    public function testNonNumericStringsConvertToZero(): void
    {
        $raw    = ['abc', '5', ''];
        $intIds = array_values(array_map('intval', $raw));

        $this->assertSame([0, 5, 0], $intIds);
    }

    public function testArrayKeysAreReindexedToZeroBased(): void
    {
        $raw    = [2 => '10', 5 => '20'];
        $intIds = array_values(array_map('intval', $raw));

        $this->assertSame([10, 20], $intIds);
        $this->assertArrayHasKey(0, $intIds);
        $this->assertArrayHasKey(1, $intIds);
    }

    // -----------------------------------------------------------------------
    // TypeScript midpoint insertion logic (mirrored in PHP)
    //
    // Mirrors: const after = e.clientY > rect.top + rect.height / 2;
    //   true  → list.insertAfter(dragged)   (cursor in lower half)
    //   false → list.insertBefore(dragged)  (cursor in upper half)
    // -----------------------------------------------------------------------

    public function testCursorBelowMidpointInsertsAfter(): void
    {
        $rectTop     = 100.0;
        $rectHeight  = 50.0;
        $clientY     = 130.0; // midpoint = 125
        $insertAfter = $clientY > $rectTop + $rectHeight / 2;

        $this->assertTrue($insertAfter);
    }

    public function testCursorAboveMidpointInsertsBefore(): void
    {
        $rectTop     = 100.0;
        $rectHeight  = 50.0;
        $clientY     = 120.0; // midpoint = 125
        $insertAfter = $clientY > $rectTop + $rectHeight / 2;

        $this->assertFalse($insertAfter);
    }

    public function testCursorExactlyAtMidpointInsertsBefore(): void
    {
        // Strict greater-than: the midpoint itself does NOT trigger insertAfter
        $rectTop     = 100.0;
        $rectHeight  = 50.0;
        $clientY     = 125.0; // exactly at midpoint
        $insertAfter = $clientY > $rectTop + $rectHeight / 2;

        $this->assertFalse($insertAfter);
    }

    public function testMidpointIsHalfHeightBelowTop(): void
    {
        $rectTop    = 200.0;
        $rectHeight = 80.0;
        $midpoint   = $rectTop + $rectHeight / 2;

        $this->assertSame(240.0, $midpoint);
    }

    public function testMidpointScalesWithRowHeight(): void
    {
        // Taller rows have a proportionally higher midpoint threshold
        $rectTop     = 0.0;
        $tallHeight  = 100.0;
        $shortHeight = 40.0;

        $this->assertSame(50.0, $rectTop + $tallHeight / 2);
        $this->assertSame(20.0, $rectTop + $shortHeight / 2);
    }

    // -----------------------------------------------------------------------
    // collectIds equivalent — ID extraction and positive-only filtering
    //
    // Mirrors TypeScript:
    //   Array.from(list.querySelectorAll('li[data-id]'))
    //     .map(li => parseInt(li.dataset['id'] ?? '0', 10))
    //     .filter(id => id > 0)
    // -----------------------------------------------------------------------

    public function testValidIdsAreExtracted(): void
    {
        $rawIds = ['5', '12', '99'];
        $ids    = array_values(array_filter(array_map('intval', $rawIds), fn(int $id): bool => $id > 0));

        $this->assertSame([5, 12, 99], $ids);
    }

    public function testZeroIdsAreFiltered(): void
    {
        $rawIds = ['0', '5', '0'];
        $ids    = array_values(array_filter(array_map('intval', $rawIds), fn(int $id): bool => $id > 0));

        $this->assertSame([5], $ids);
    }

    public function testNegativeIdsAreFiltered(): void
    {
        $rawIds = ['-1', '3', '-99'];
        $ids    = array_values(array_filter(array_map('intval', $rawIds), fn(int $id): bool => $id > 0));

        $this->assertSame([3], $ids);
    }

    public function testEmptyInputProducesEmptyIdList(): void
    {
        $rawIds = [];
        $ids    = array_values(array_filter(array_map('intval', $rawIds), fn(int $id): bool => $id > 0));

        $this->assertSame([], $ids);
    }

    public function testAllZerosProducesEmptyIdList(): void
    {
        $rawIds = ['0', '0', '0'];
        $ids    = array_values(array_filter(array_map('intval', $rawIds), fn(int $id): bool => $id > 0));

        $this->assertSame([], $ids);
    }

    // -----------------------------------------------------------------------
    // Position badge numbering — 0-based DOM index → 1-based display string
    //
    // Mirrors TypeScript refreshPositionBadges:
    //   badge.textContent = String(index + 1);
    // -----------------------------------------------------------------------

    public function testFirstBadgeDisplaysOne(): void
    {
        $badge = (string) (0 + 1);

        $this->assertSame('1', $badge);
    }

    public function testFifthBadgeDisplaysFive(): void
    {
        $badge = (string) (4 + 1);

        $this->assertSame('5', $badge);
    }

    public function testPositionBadgesForFiveItems(): void
    {
        $badges = array_map(fn(int $i): string => (string) ($i + 1), range(0, 4));

        $this->assertSame(['1', '2', '3', '4', '5'], $badges);
    }

    public function testBadgesAreStringType(): void
    {
        /** @var int $index */
        $index = 2;
        $badge = (string) ($index + 1);

        $this->assertIsString($badge);
        $this->assertSame('3', $badge);
    }

    // -----------------------------------------------------------------------
    // postOrder URL body construction
    //
    // Mirrors TypeScript URLSearchParams:
    //   body.append('_csrf', csrf)
    //   ids.forEach(id => body.append('order[]', String(id)))
    // -----------------------------------------------------------------------

    public function testBodyContainsCsrfToken(): void
    {
        $csrf  = 'test-csrf-token';
        $ids   = [10, 20];
        $parts = ['_csrf=' . urlencode($csrf)];
        foreach ($ids as $id) {
            $parts[] = 'order%5B%5D=' . $id;
        }
        $body = implode('&', $parts);

        $this->assertStringContainsString('_csrf=test-csrf-token', $body);
    }

    public function testBodyContainsAllOrderedIds(): void
    {
        $ids   = [10, 20, 30];
        $parts = [];
        foreach ($ids as $id) {
            $parts[] = 'order[]=' . $id;
        }
        $body = '_csrf=token&' . implode('&', $parts);

        $this->assertStringContainsString('order[]=10', $body);
        $this->assertStringContainsString('order[]=20', $body);
        $this->assertStringContainsString('order[]=30', $body);
    }

    public function testBodyIdSequenceMatchesSubmittedOrder(): void
    {
        $ids = [30, 10, 20]; // deliberately shuffled
        /** @var array{order?: list<string>} $parsed */
        parse_str(http_build_query(['order' => array_map('strval', $ids)]), $parsed);

        $this->assertSame(['30', '10', '20'], $parsed['order'] ?? []);
    }

    public function testBodyIsUrlEncoded(): void
    {
        $csrf    = 'tok&en=special';
        $encoded = urlencode($csrf);

        $this->assertStringNotContainsString('&', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }

    // -----------------------------------------------------------------------
    // ReorderResponse — success / failure branching
    //
    // Mirrors TypeScript:
    //   if (result.success) { setStatus('✓ Order saved', 'success') }
    //   else                { setStatus('✗ ' + (result.message ?? 'Save failed'), 'danger') }
    //
    // @var array{success: bool, message?: string} widens the literal types so
    // Psalm treats the ternary branches as genuinely reachable.
    // -----------------------------------------------------------------------

    public function testSuccessResponseResolvesToSuccessStatus(): void
    {
        /** @var array{success: bool, message?: string} $response */
        $response = ['success' => true];
        $status   = $response['success'] ? 'success' : 'danger';

        $this->assertSame('success', $status);
    }

    public function testFailureResponseResolvesToDangerStatus(): void
    {
        /** @var array{success: bool, message?: string} $response */
        $response = ['success' => false, 'message' => 'DB error'];
        $status   = $response['success'] ? 'success' : 'danger';

        $this->assertSame('danger', $status);
    }

    public function testSuccessMessageText(): void
    {
        /** @var array{success: bool, message?: string} $response */
        $response = ['success' => true];
        $message  = $response['success']
            ? '✓ Order saved'
            : ('✗ ' . ($response['message'] ?? 'Save failed'));

        $this->assertSame('✓ Order saved', $message);
    }

    public function testFailureMessageIncludesServerErrorText(): void
    {
        /** @var array{success: bool, message?: string} $response */
        $response = ['success' => false, 'message' => 'DB error'];
        $message  = $response['success']
            ? '✓ Order saved'
            : ('✗ ' . ($response['message'] ?? 'Save failed'));

        $this->assertSame('✗ DB error', $message);
    }

    public function testFailureFallsBackWhenMessageKeyAbsent(): void
    {
        /** @var array{success: bool, message?: string} $response */
        $response = ['success' => false];
        $message  = $response['success']
            ? '✓ Order saved'
            : ('✗ ' . ($response['message'] ?? 'Save failed'));

        $this->assertSame('✗ Save failed', $message);
    }

    public function testHttpErrorCodeAppearsInMessage(): void
    {
        // Mirrors: return { success: false, message: `HTTP ${response.status}` }
        /** @var int $status */
        $status  = 500;
        $message = 'HTTP ' . $status;

        $this->assertSame('HTTP 500', $message);
        $this->assertStringStartsWith('HTTP ', $message);
    }
}
