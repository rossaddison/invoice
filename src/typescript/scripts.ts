// src/typescript/scripts.ts
// Converted from scripts.js - Global UI utilities

export function initTooltips(): void {
    const bootstrap = (window as any).bootstrap;
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) return;
    
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipElements.forEach((el) => {
        try { 
            new bootstrap.Tooltip(el); 
        } catch (e) { 
            // ignore init errors 
        }
    });
}

export function initSimpleSelects(root?: Document | Element): void {
    const TomSelect = (window as any).TomSelect;
    if (typeof TomSelect === 'undefined') return;
    
    const container = root || document;
    const selectElements = container.querySelectorAll('.simple-select') as NodeListOf<HTMLSelectElement>;
    
    selectElements.forEach((el) => {
        if (!(el as any)._tomselect) {
            new TomSelect(el, {});
            (el as any)._tomselect = true;
        }
    });
}

// Loader helpers
export function showFullpageLoader(): void {
    const loader = document.getElementById('fullpage-loader');
    const loaderError = document.getElementById('loader-error');
    const loaderIcon = document.getElementById('loader-icon');

    if (loader) loader.style.display = 'block';
    if (loaderError) loaderError.style.display = 'none';
    if (loaderIcon) {
        loaderIcon.classList.add('fa-spin');
        loaderIcon.classList.remove('text-danger');
    }

    // Set timeout for error display
    setTimeout(() => {
        if (loaderError) loaderError.style.display = 'block';
        if (loaderIcon) {
            loaderIcon.classList.remove('fa-spin');
            loaderIcon.classList.add('text-danger');
        }
    }, 10000);
}

export function hideFullpageLoader(): void {
    const loader = document.getElementById('fullpage-loader');
    const loaderError = document.getElementById('loader-error');
    const loaderIcon = document.getElementById('loader-icon');

    if (loader) loader.style.display = 'none';
    if (loaderError) loaderError.style.display = 'none';
    if (loaderIcon) {
        loaderIcon.classList.add('fa-spin');
        loaderIcon.classList.remove('text-danger');
    }
}

// Password strength meter
export function initPasswordMeter(): void {
    const passwordInput = document.querySelector('.passwordmeter-input') as HTMLInputElement;
    if (!passwordInput) return;

    passwordInput.addEventListener('input', () => {
        // Simple strength calculation (you can enhance this)
        const password = passwordInput.value;
        const strength = calculatePasswordStrength(password);
        
        const meter2 = document.querySelector('.passmeter-2') as HTMLElement;
        const meter3 = document.querySelector('.passmeter-3') as HTMLElement;
        
        if (meter2 && meter3) {
            meter2.style.display = 'none';
            meter3.style.display = 'none';
            
            if (strength >= 4) {
                meter2.style.display = 'block';
                meter3.style.display = 'block';
            } else if (strength >= 3) {
                meter2.style.display = 'block';
            }
        }
    });
}

function calculatePasswordStrength(password: string): number {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    return strength;
}

// Initialize everything when DOM is ready
export function initializeScripts(): void {
    document.addEventListener('DOMContentLoaded', () => {
        initTooltips();
        initSimpleSelects();
        initPasswordMeter();
        
        // Add event listeners for fullpage loader
        document.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            if (target.classList.contains('ajax-loader')) {
                showFullpageLoader();
            }
            if (target.classList.contains('fullpage-loader-close')) {
                hideFullpageLoader();
            }
        });
    });
}

// Auto-initialize if this module is loaded
initializeScripts();