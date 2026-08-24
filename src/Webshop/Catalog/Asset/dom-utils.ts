/** Shared by price-range.ts and product-zoom.ts. */
export function clamp(value: number, min: number, max: number): number {
    return Math.min(Math.max(value, min), max);
}
