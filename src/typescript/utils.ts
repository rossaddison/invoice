import type { ApiResponse, RequestParams, FetchOptions, SafeElement } from './types.js';

// Re-export types for convenience
export type { ApiResponse, RequestParams, FetchOptions, SafeElement } from './types.js';

/**
 * Safe JSON parser that always returns an object
 * @param data - Data to parse (can be string, object, or any type)
 * @returns Parsed object or empty object if parsing fails
 */
export function parsedata(data: unknown): ApiResponse | Record<string, any> {
    if (!data) return {};
    if (typeof data === 'object' && data !== null) return data as Record<string, any>;
    if (typeof data === 'string') {
        try {
            return JSON.parse(data) as Record<string, any>;
        } catch (e) {
            return {};
        }
    }
    return {};
}

/**
 * HTTP GET helper that serializes arrays as bracketed keys (key[]=v1&key[]=v2)
 * @param url - Request URL
 * @param params - Parameters to send
 * @param options - Additional fetch options
 * @returns Promise resolving to parsed JSON or text
 */
export async function getJson<T = unknown>(
    url: string,
    params?: RequestParams,
    options: FetchOptions = {}
): Promise<T> {
    let requestUrl = url;

    if (params) {
        const searchParams = new URLSearchParams();

        Object.entries(params).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                // Append as key[] so server parses it as an array (matches jQuery behavior)
                value.forEach(item => {
                    if (item !== null && item !== undefined) {
                        searchParams.append(`${key}[]`, String(item));
                    }
                });
            } else if (value !== undefined && value !== null) {
                searchParams.append(key, String(value));
            }
        });

        const separator = url.includes('?') ? '&' : '?';
        requestUrl = `${url}${separator}${searchParams.toString()}`;
    }

    const defaultOptions: RequestInit = {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json' },
        ...options,
    };

    const response = await fetch(requestUrl, defaultOptions);

    if (!response.ok) {
        throw new Error(`Network response not ok: ${response.status}`);
    }

    const text = await response.text();

    try {
        return JSON.parse(text) as T;
    } catch (e) {
        return text as T;
    }
}

/**
 * Safe closest element finder with fallback for older browsers
 * @param element - Starting element
 * @param selector - CSS selector to match
 * @returns Matching ancestor element or null
 */
export function closestSafe<T extends Element = Element>(
    element: Element | null,
    selector: string
): SafeElement<T> {
    try {
        if (!element) return null;

        if (typeof element.closest === 'function') {
            return element.closest(selector) as SafeElement<T>;
        }

        // Fallback: walk up parents manually
        let node = element as Element | null;
        while (node) {
            if (node.matches && node.matches(selector)) {
                return node as SafeElement<T>;
            }
            node = node.parentElement;
        }
    } catch (e) {
        console.warn('closestSafe error:', e);
        return null;
    }

    return null;
}

/**
 * Safe DOM element getter with type safety
 * @param id - Element ID
 * @returns Element or null
 */
export function getElementById<T extends HTMLElement = HTMLElement>(id: string): SafeElement<T> {
    return document.getElementById(id) as SafeElement<T>;
}

/**
 * Safe DOM element selector with type safety
 * @param selector - CSS selector
 * @returns Element or null
 */
export function querySelector<T extends Element = Element>(selector: string): SafeElement<T> {
    return document.querySelector(selector) as SafeElement<T>;
}

/**
 * Safe DOM elements selector with type safety
 * @param selector - CSS selector
 * @returns NodeList of elements
 */
export function querySelectorAll<T extends Element = Element>(selector: string): NodeListOf<T> {
    return document.querySelectorAll(selector) as NodeListOf<T>;
}

/**
 * Get form field value safely
 * @param id - Element ID
 * @returns Value or empty string
 */
export function getInputValue(id: string): string {
    const element = getElementById<HTMLInputElement>(id);
    return element?.value || '';
}
