/**
 * Safe JSON parser that always returns an object
 * @param data - Data to parse (can be string, object, or any type)
 * @returns Parsed object or empty object if parsing fails
 */
export function parsedata(data) {
    if (!data)
        return {};
    if (typeof data === 'object' && data !== null)
        return data;
    if (typeof data === 'string') {
        try {
            return JSON.parse(data);
        }
        catch (e) {
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
export async function getJson(url, params, options = {}) {
    let requestUrl = url;
    if (params) {
        const searchParams = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                // Append as key[] so server parses it as an array (matches jQuery behavior)
                value.forEach((item) => {
                    if (item !== null && item !== undefined) {
                        searchParams.append(`${key}[]`, String(item));
                    }
                });
            }
            else if (value !== undefined && value !== null) {
                searchParams.append(key, String(value));
            }
        });
        const separator = url.includes('?') ? '&' : '?';
        requestUrl = `${url}${separator}${searchParams.toString()}`;
    }
    const defaultOptions = {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'Accept': 'application/json' },
        ...options
    };
    const response = await fetch(requestUrl, defaultOptions);
    if (!response.ok) {
        throw new Error(`Network response not ok: ${response.status}`);
    }
    const text = await response.text();
    try {
        return JSON.parse(text);
    }
    catch (e) {
        return text;
    }
}
/**
 * Safe closest element finder with fallback for older browsers
 * @param element - Starting element
 * @param selector - CSS selector to match
 * @returns Matching ancestor element or null
 */
export function closestSafe(element, selector) {
    try {
        if (!element)
            return null;
        if (typeof element.closest === 'function') {
            return element.closest(selector);
        }
        // Fallback: walk up parents manually
        let node = element;
        while (node) {
            if (node.matches && node.matches(selector)) {
                return node;
            }
            node = node.parentElement;
        }
    }
    catch (e) {
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
export function getElementById(id) {
    return document.getElementById(id);
}
/**
 * Safe DOM element selector with type safety
 * @param selector - CSS selector
 * @returns Element or null
 */
export function querySelector(selector) {
    return document.querySelector(selector);
}
/**
 * Safe DOM elements selector with type safety
 * @param selector - CSS selector
 * @returns NodeList of elements
 */
export function querySelectorAll(selector) {
    return document.querySelectorAll(selector);
}
/**
 * Get form field value safely
 * @param id - Element ID
 * @returns Value or empty string
 */
export function getInputValue(id) {
    const element = getElementById(id);
    return element?.value || '';
}
