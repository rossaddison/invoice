/**
 * Family Commalist Number Picker
 * A vanilla JavaScript alternative to the Angular component
 */

class FamilyCommalistPicker {
    private container: HTMLElement;
    private textarea: HTMLTextAreaElement;
    private selectedNumbers: Set<number> = new Set();
    private currentPage: number = 1;
    private numbersPerPage: number = 50;
    private totalPages: number = 4; // 200 numbers / 50 per page

    constructor(containerId: string, textareaId: string) {
        this.container = document.getElementById(containerId)!;
        this.textarea = document.getElementById(textareaId) as HTMLTextAreaElement;
        
        if (!this.container || !this.textarea) {
            throw new Error('Required elements not found');
        }
        
        this.parseInitialValue();
        this.render();
        this.attachEventListeners();
    }

    private parseInitialValue(): void {
        if (this.textarea.value) {
            const existingNumbers = this.textarea.value
                .split(',')
                .map(n => parseInt(n.trim()))
                .filter(n => !isNaN(n) && n >= 1 && n <= 200);
            
            this.selectedNumbers = new Set(existingNumbers);
        }
    }

    private render(): void {
        this.container.innerHTML = this.getHTML();
    }

    private getHTML(): string {
        const paginatedNumbers = this.getPaginatedNumbers();
        const selectedCount = this.selectedNumbers.size;
        const pageInfo = this.getPageInfo();

        return `
            <div class="family-commalist-picker">
                <div class="picker-header mb-3">
                    <h5 class="mb-2">
                        <i class="bi bi-list-ol"></i> 
                        Select Numbers (1-200)
                    </h5>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="selected-info">
                            <span class="badge bg-primary me-2">
                                ${selectedCount} selected
                            </span>
                            <span class="text-muted small">${pageInfo}</span>
                        </div>
                        
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-success" onclick="picker.selectPage()" title="Select all numbers on current page">
                                <i class="bi bi-check-square"></i> Page
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="picker.deselectPage()" title="Deselect all numbers on current page">
                                <i class="bi bi-square"></i> Page
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="picker.clearAll()" title="Clear all selections">
                                <i class="bi bi-trash"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Range Selection -->
                <div class="quick-ranges mb-3">
                    <small class="text-muted d-block mb-2">Quick ranges:</small>
                    <div class="btn-group btn-group-sm flex-wrap" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(1, 10)" title="Select 1-10">1-10</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(11, 25)" title="Select 11-25">11-25</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(26, 50)" title="Select 26-50">26-50</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(51, 100)" title="Select 51-100">51-100</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(101, 150)" title="Select 101-150">101-150</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="picker.selectRange(151, 200)" title="Select 151-200">151-200</button>
                    </div>
                </div>

                <!-- Number Grid -->
                <div class="numbers-grid mb-3">
                    <div class="number-buttons">
                        ${paginatedNumbers.map(num => `
                            <button type="button" 
                                    class="btn number-btn ${this.selectedNumbers.has(num) ? 'btn-success' : 'btn-outline-secondary'}"
                                    onclick="picker.toggleNumber(${num})"
                                    title="Toggle number ${num}">
                                ${num}
                            </button>
                        `).join('')}
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination-controls d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="picker.prevPage()" ${this.currentPage === 1 ? 'disabled' : ''}>
                        <i class="bi bi-chevron-left"></i> Previous
                    </button>
                    
                    <div class="page-buttons">
                        ${[1, 2, 3, 4].map(page => `
                            <button type="button" 
                                    class="btn btn-sm me-1 ${page === this.currentPage ? 'btn-primary' : 'btn-outline-primary'}"
                                    onclick="picker.goToPage(${page})">
                                ${page}
                            </button>
                        `).join('')}
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="picker.nextPage()" ${this.currentPage === this.totalPages ? 'disabled' : ''}>
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                ${selectedCount > 0 ? `
                    <div class="selected-preview mt-3">
                        <small class="text-muted d-block mb-1">Selected numbers:</small>
                        <div class="selected-numbers-display">
                            <span class="text-muted small">${Array.from(this.selectedNumbers).sort((a, b) => a - b).join(', ')}</span>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    private getPaginatedNumbers(): number[] {
        const startIndex = (this.currentPage - 1) * this.numbersPerPage;
        const endIndex = startIndex + this.numbersPerPage;
        const allNumbers = Array.from({ length: 200 }, (_, i) => i + 1);
        return allNumbers.slice(startIndex, endIndex);
    }

    private getPageInfo(): string {
        const start = (this.currentPage - 1) * this.numbersPerPage + 1;
        const end = Math.min(this.currentPage * this.numbersPerPage, 200);
        return `${start}-${end} of 200`;
    }

    public toggleNumber(num: number): void {
        if (this.selectedNumbers.has(num)) {
            this.selectedNumbers.delete(num);
        } else {
            this.selectedNumbers.add(num);
        }
        this.updateTextarea();
        this.render();
    }

    public clearAll(): void {
        this.selectedNumbers.clear();
        this.updateTextarea();
        this.render();
    }

    public selectRange(start: number, end: number): void {
        for (let i = start; i <= end; i++) {
            if (i >= 1 && i <= 200) {
                this.selectedNumbers.add(i);
            }
        }
        this.updateTextarea();
        this.render();
    }

    public selectPage(): void {
        this.getPaginatedNumbers().forEach(num => {
            this.selectedNumbers.add(num);
        });
        this.updateTextarea();
        this.render();
    }

    public deselectPage(): void {
        this.getPaginatedNumbers().forEach(num => {
            this.selectedNumbers.delete(num);
        });
        this.updateTextarea();
        this.render();
    }

    public goToPage(page: number): void {
        if (page >= 1 && page <= this.totalPages) {
            this.currentPage = page;
            this.render();
        }
    }

    public nextPage(): void {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.render();
        }
    }

    public prevPage(): void {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.render();
        }
    }

    private updateTextarea(): void {
        const sortedNumbers = Array.from(this.selectedNumbers).sort((a, b) => a - b);
        const commalistValue = sortedNumbers.join(', ');
        this.textarea.value = commalistValue;
        
        // Trigger events for form validation
        this.textarea.dispatchEvent(new Event('change', { bubbles: true }));
        this.textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    private attachEventListeners(): void {
        // Listen for manual textarea changes
        this.textarea.addEventListener('input', () => {
            this.parseInitialValue();
            this.render();
        });
    }
}

// Global instance
let picker: FamilyCommalistPicker | null = null;

// Integration functions
declare global {
    interface Window {
        toggleCommalistPicker: () => void;
        picker: FamilyCommalistPicker | null;
    }
}

export function initializeCommalistPicker() {
    window.toggleCommalistPicker = toggleCommalistPicker;
    window.picker = null;
}

function toggleCommalistPicker() {
    const container = document.getElementById('commalist-picker-container');
    const button = document.getElementById('toggle-picker-btn');
    
    if (!container || !button) return;
    
    if (container.style.display === 'none') {
        // Show picker
        container.style.display = 'block';
        button.innerHTML = '<i class="bi bi-grid-3x3-gap-fill"></i> Hide Number Picker';
        
        // Initialize picker if not already done
        if (!picker) {
            // Create picker container
            const pickerDiv = document.createElement('div');
            pickerDiv.id = 'number-picker';
            
            const infoAlert = container.querySelector('.alert');
            if (infoAlert && infoAlert.nextSibling) {
                container.insertBefore(pickerDiv, infoAlert.nextSibling);
            } else {
                container.appendChild(pickerDiv);
            }
            
            picker = new FamilyCommalistPicker('number-picker', 'family_commalist');
            window.picker = picker;
        }
    } else {
        // Hide picker
        container.style.display = 'none';
        button.innerHTML = '<i class="bi bi-grid-3x3-gap"></i> Show Number Picker';
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCommalistPicker);
} else {
    initializeCommalistPicker();
}