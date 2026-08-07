// Type definitions for Invoice Application

// Global Bootstrap types
declare global {
    // var (not `interface Window`, not `const`) — TypeScript only extends
    // globalThis's own type for ambient `var` declarations; `const`/`let`
    // ambient globals type-check as bare identifiers but never as
    // globalThis properties (this mirrors real JS: a top-level `const` at
    // script scope doesn't become a globalThis property either, only
    // `var` does). This app's own convention is globalThis, never window
    // (typescript:S7764), so both mismatches needed fixing — this was
    // previously declared twice (as `interface Window` here, and
    // separately as `const` right below), neither of which actually
    // covered a plain globalThis.bootstrap read; merged into one.
    var bootstrap:
        | {
              Tooltip: (new (element: Element, options?: any) => any) & {
                  getOrCreateInstance(element: Element, options?: any): any;
              };
              Modal: new (
                  element: Element,
                  options?: any
              ) => {
                  show(): void;
                  hide(): void;
              };
          }
        | undefined;
    var lastTaggableClicked: Element | undefined;
    // TomSelect's canonical declaration lives in product.ts (a more
    // specific constructor type; this file previously duplicated it as a
    // loose `any`, which TypeScript correctly flagged as a conflicting
    // declaration — TS2687/TS2717 — once it started fully type-checking
    // this file again).
}

export interface ApiResponse {
    success: 0 | 1;
    flash_message?: string;
    message?: string;
    data?: any;
    errors?: Record<string, string[]>;
    validation_errors?: Record<string, string[]>;
    new_invoice_id?: string;
    redirect_url?: string;
}

export interface RequestParams {
    [key: string]: string | number | boolean | (string | number | boolean)[] | null | undefined;
}

export interface InvoiceFormData extends RequestParams {
    inv_id?: string;
    client_id?: string;
    inv_date_created?: string;
    group_id?: string;
    password?: string;
    user_id?: string;
}

export interface OriginalStyles {
    readonly fontSize: string;
    readonly fontWeight: string;
    readonly backgroundColor: string;
    readonly border: string;
    readonly borderRadius: string;
    readonly padding: string;
    readonly zIndex: string;
    readonly position: string;
    readonly transform: string;
    readonly boxShadow: string;
}

export interface FetchOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
    credentials?: RequestCredentials;
    cache?: RequestCache;
    headers?: Record<string, string>;
}

// Utility type for DOM element getters
export type SafeElement<T extends Element = Element> = T | null;

// Event handler types
export type ClickHandler = (event: MouseEvent) => void | Promise<void>;
export type SubmitHandler = (event: SubmitEvent) => void | Promise<void>;
