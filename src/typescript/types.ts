// Type definitions for Invoice Application

// Global Bootstrap types
declare global {
  interface Window {
    bootstrap?: {
      Tooltip: new (element: Element, options?: any) => any;
      Modal: new (element: Element, options?: any) => {
        show(): void;
        hide(): void;
      };
    };
    lastTaggableClicked?: Element;
    TomSelect?: any;
  }
  const bootstrap: {
    Tooltip: new (element: Element, options?: any) => any;
    Modal: new (element: Element, options?: any) => {
      show(): void;
      hide(): void;
    };
  } | undefined;
}

export interface ApiResponse {
  success: 0 | 1;
  flash_message?: string;
  message?: string;
  data?: any;
  errors?: Record<string, string[]>;
  validation_errors?: Record<string, string[]>;
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