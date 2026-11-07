import { describe, expect, it } from 'vitest';
import { renderSides } from './homecare-offline-sides.js';
import type { OfflineInvoiceSide } from './homecare-offline-db.js';

const side = (over: Partial<OfflineInvoiceSide> = {}): OfflineInvoiceSide => ({
    side: 'front',
    sideLabel: 'Fronts',
    workerName: 'Dave Smith',
    mine: false,
    status: 'allocated',
    statusLabel: 'Allocated',
    carded: false,
    ...over,
});

describe('renderSides', () => {
    it('shows nothing for no sides, or for a snapshot downloaded before sides existed', () => {
        expect(renderSides(undefined)).toBeNull();
        expect(renderSides([])).toBeNull();
    });

    it('shows one line per side with the picture, the words, the worker and the status', () => {
        const block = renderSides([side(), side({ side: 'back', sideLabel: 'Backs', workerName: 'Sam Jones' })])!;

        const lines = block.querySelectorAll('.offline-side');
        expect(lines).toHaveLength(2);
        expect(lines[0]!.querySelector('.symbol')!.textContent).toBe('🚪');
        expect(lines[0]!.querySelector('.label')!.textContent).toBe('Fronts');
        expect(lines[0]!.querySelector('.worker')!.textContent).toBe('Dave Smith');
        expect(lines[0]!.querySelector('.state')!.textContent).toBe('🕐 Allocated');
        expect(lines[1]!.querySelector('.symbol')!.textContent).toBe('🌳');
    });

    it('marks the sides this worker holds, so they stand out from a co-worker’s', () => {
        const block = renderSides([side({ mine: true }), side({ side: 'back', mine: false })])!;

        const lines = block.querySelectorAll('.offline-side');
        expect(lines[0]!.classList.contains('mine')).toBe(true);
        expect(lines[1]!.classList.contains('mine')).toBe(false);
    });

    it('shows the card mark only once a card has been left', () => {
        const block = renderSides([side({ carded: true }), side({ side: 'back', carded: false })])!;

        const lines = block.querySelectorAll('.offline-side');
        expect(lines[0]!.querySelector('.carded')).not.toBeNull();
        expect(lines[1]!.querySelector('.carded')).toBeNull();
    });

    it('shows a whole house with its own picture and each status with its own', () => {
        const block = renderSides([
            side({ side: 'both', sideLabel: 'Whole house', status: 'completed', statusLabel: 'Completed' }),
        ])!;

        expect(block.querySelector('.symbol')!.textContent).toBe('🏠');
        expect(block.querySelector('.state')!.textContent).toBe('✓ Completed');
    });

    it('treats a worker name as text, never as markup', () => {
        const block = renderSides([side({ workerName: '<img src=x onerror=alert(1)>' })])!;

        expect(block.querySelector('img')).toBeNull();
        expect(block.querySelector('.worker')!.textContent).toBe('<img src=x onerror=alert(1)>');
    });
});
