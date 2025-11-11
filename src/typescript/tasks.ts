import { parsedata } from './utils.js';

// Task-specific interfaces
interface Task {
    id: string;
    name: string;
    description: string;
    price: string;
    tax_rate_id: string;
}

interface TaskSelectionResponse {
    [key: string]: Task;
}

// Task handler class
export class TaskHandler {
    constructor() {
        this.bindEventListeners();
        this.initializeComponents();
    }

    private bindEventListeners(): void {
        // Delegated event handling for clicks
        document.addEventListener('click', this.handleClick.bind(this), true);
        
        // Delegated change handling for checkboxes
        document.addEventListener('change', this.handleChange.bind(this), true);
        
        // Handle Enter key for search
        document.addEventListener('keydown', this.handleKeydown.bind(this), true);
        
        // Handle modal events to reinitialize components
        document.addEventListener('shown.bs.modal', this.handleModalShown.bind(this), true);
    }

    private handleClick(event: Event): void {
        const target = event.target as HTMLElement;

        // Row toggle
        const taskRow = target.closest('#tasks_table tr, .task, .task-row');
        if (taskRow) {
            this.rowClickToggle(event);
            return;
        }

        // Confirm select tasks
        const confirmTask = target.closest('.select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote');
        if (confirmTask) {
            this.handleSelectItemsConfirmTask(confirmTask as HTMLElement);
            return;
        }

        // Reset / load actions
        const resetButton = target.closest('#task-reset-button-inv');
        if (resetButton) {
            this.handleTaskReset();
            return;
        }
    }

    private handleChange(event: Event): void {
        const target = event.target as HTMLElement;
        
        if (target.matches("input[name='task_ids[]']")) {
            const container = target.closest('#tasks_table') || document;
            this.updateSelectTaskButtonState(container);
        }
    }

    private handleKeydown(event: KeyboardEvent): void {
        if (event.key === 'Enter') {
            const active = document.activeElement as HTMLElement;
            if (active && active.id === 'filter_task_inv') {
                const btn = document.getElementById('filter-button-inv');
                if (btn) {
                    btn.click();
                    event.preventDefault();
                }
            }
        }
    }

    private initializeComponents(): void {
        this.hideSelectedTasks();
        this.updateSelectTaskButtonState(document);
    }

    /**
     * Handle modal shown event to reinitialize components
     */
    private handleModalShown(event: Event): void {
        const target = event.target as HTMLElement;
        
        // Check if this is a task modal
        if (target.id === 'modal-choose-tasks' || target.id === 'modal-choose-tasks-inv') {
            console.log('Task modal shown, reinitializing components...');
            
            // Reinitialize components for the new modal content
            this.hideSelectedTasks();
            this.updateSelectTaskButtonState(document);
        }
    }

    /**
     * Hide already-selected tasks (based on .item-task-id values)
     */
    private hideSelectedTasks(): void {
        const selectedTasks: number[] = [];
        
        document.querySelectorAll('.item-task-id').forEach((el: Element) => {
            const input = el as HTMLInputElement;
            const currentVal = input.value || '';
            if (currentVal.length) {
                const taskId = parseInt(currentVal, 10);
                if (!isNaN(taskId)) {
                    selectedTasks.push(taskId);
                }
            }
        });

        let hiddenTasks = 0;
        document.querySelectorAll('.modal-task-id').forEach((el: Element) => {
            const idAttr = el.id || '';
            const idNum = parseInt(idAttr.replace('task-id-', ''), 10);
            
            if (!Number.isNaN(idNum) && selectedTasks.includes(idNum)) {
                // Hide the row containing this modal-task-id
                const row = el.closest('tr') || 
                           (el.parentElement && el.parentElement.parentElement);
                if (row) {
                    (row as HTMLElement).style.display = 'none';
                    hiddenTasks++;
                }
            }
        });

        const taskRows = document.querySelectorAll('.task-row');
        if (hiddenTasks >= taskRows.length) {
            const submitBtn = document.getElementById('task-modal-submit') || document.getElementById('task-modal-submit-quote');
            if (submitBtn) {
                submitBtn.style.display = 'none';
            }
        }
    }

    /**
     * Toggle checkbox when clicking on row (unless click was on checkbox)
     */
    private rowClickToggle(event: Event): void {
        const row = (event.target as HTMLElement).closest('#tasks_table tr, .task-row, .task');
        if (!row) return;
        
        const target = event.target as HTMLInputElement;
        if (target.type !== 'checkbox') {
            const checkbox = row.querySelector('input[type="checkbox"]') as HTMLInputElement;
            if (checkbox) {
                // Toggle and dispatch change
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    /**
     * Enable/disable select button based on checked tasks
     */
    private updateSelectTaskButtonState(root: Document | Element): void {
        const ctx = root || document;
        const checkboxes = ctx.querySelectorAll("input[name='task_ids[]']");
        const checkedBoxes = ctx.querySelectorAll("input[name='task_ids[]']:checked");
        const anyChecked = checkedBoxes.length > 0;
        
        console.log(`🔍 Task button state update: ${checkboxes.length} total checkboxes, ${checkedBoxes.length} checked, anyChecked: ${anyChecked}`);
        
        // Multiple strategies to find the submit button
        let buttons: NodeListOf<Element>;
        
        // Strategy 1: Look in the same context as checkboxes
        buttons = ctx.querySelectorAll('.select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote');
        
        // Strategy 2: Fall back to document-wide search
        if (buttons.length === 0) {
            buttons = document.querySelectorAll('.select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote');
        }
        
        // Strategy 3: Try finding by ID as fallback
        if (buttons.length === 0) {
            const buttonById = document.getElementById('task-modal-submit') || document.getElementById('task-modal-submit-quote');
            if (buttonById) {
                buttons = [buttonById] as any;
                console.log('🔍 Found button by ID fallback');
            }
        }
        
        // Strategy 4: Search in any open modal
        if (buttons.length === 0) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                const modalButtons = modal.querySelectorAll('.select-items-confirm-task, .select-items-confirm-task-inv, .select-items-confirm-task-quote, #task-modal-submit, #task-modal-submit-quote');
                if (modalButtons.length > 0) {
                    buttons = modalButtons;
                    console.log('🔍 Found button in modal fallback');
                }
            });
        }
        
        console.log(`🔍 Found ${buttons.length} task submit buttons`);
        
        buttons.forEach((btn: Element) => {
            const button = btn as HTMLButtonElement;
            if (anyChecked) {
                button.removeAttribute('disabled');
                button.removeAttribute('aria-disabled');
                button.disabled = false;
                console.log('✅ Task submit button enabled');
            } else {
                button.setAttribute('disabled', 'true');
                button.setAttribute('aria-disabled', 'true');
                button.disabled = true;
                console.log('❌ Task submit button disabled');
            }
        });
    }

    /**
     * Handle confirm click: collect selected task ids and send to server, then populate items and reload
     */
    private async handleSelectItemsConfirmTask(btn: HTMLElement): Promise<void> {
        const absoluteUrl = new URL(location.href);
        const entityId = absoluteUrl.pathname.split('/').at(-1) || '';
        
        // Detect if we're on a quote or invoice page
        const isQuotePage = absoluteUrl.pathname.includes('/quote/');
        const isInvoicePage = absoluteUrl.pathname.includes('/inv/');

        const taskIds = Array.from(
            document.querySelectorAll("input[name='task_ids[]']:checked")
        )
            .map((el: Element) => parseInt((el as HTMLInputElement).value, 10))
            .filter((id: number) => !isNaN(id));

        if (taskIds.length === 0) return;

        // ES2024: Sort task IDs for consistent processing order
        const sortedTaskIds = taskIds.toSorted((a, b) => a - b);
        console.log('Processing tasks in sorted order:', sortedTaskIds);

        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';
        (btn as HTMLButtonElement).disabled = true;

        const params = new URLSearchParams();
        sortedTaskIds.forEach((id: number) => {
            params.append('task_ids[]', id.toString());
        });
        
        // Use appropriate parameter name and endpoint
        if (isQuotePage) {
            params.append('quote_id', entityId);
        } else {
            params.append('inv_id', entityId);
        }

        const endpoint = isQuotePage ? 'selection_quote' : 'selection_inv';

        try {
            const response = await fetch(`/invoice/task/${endpoint}?${params.toString()}`, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`Network response not ok: ${response.status}`);
            }

            const text = await response.text();
            let data: any;
            try {
                data = JSON.parse(text);
            } catch (e) {
                data = text;
            }

            const tasks = parsedata(data) as TaskSelectionResponse;
            this.processTasks(tasks);

            btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
            
            // Reload as original code does to sync state
            location.reload();
        } catch (error) {
            console.error('selection_inv failed', error);
            btn.innerHTML = originalHtml;
            (btn as HTMLButtonElement).disabled = false;
            
            const userError = new Error('An error occurred while adding tasks to invoice. See console for details.', { 
                cause: error 
            });
            alert(userError.message);
        }
    }

    /**
     * Process tasks and populate form fields
     */
    private processTasks(tasks: TaskSelectionResponse): void {
        let productDefaultTaxRateId: string | null = null;

        // ES2024: Group tasks by tax rate for batch processing
        const tasksByTaxRate = Object.groupBy(
            Object.entries(tasks).map(([key, task]) => ({ key, ...task })),
            (task) => task.tax_rate_id || 'default'
        );
        
        console.log('Tasks grouped by tax rate:', Object.keys(tasksByTaxRate));

        // ES2024: Process tasks in reverse order for better form population strategy
        Object.entries(tasks).toReversed().forEach(([key, task]) => {
            const currentTaxRateId = task.tax_rate_id;
            
            if (!currentTaxRateId) {
                const defaultTaxEl = document.getElementById('default_item_tax_rate') as HTMLInputElement ||
                                   document.querySelector('#default_item_tax_rate') as HTMLInputElement;
                productDefaultTaxRateId = defaultTaxEl ? defaultTaxEl.getAttribute('value') : '';
            } else {
                productDefaultTaxRateId = currentTaxRateId;
            }

            // Find last item row (matching original behaviour)
            const lastTbody = document.querySelector('#item_table tbody:last-of-type') ||
                             document.querySelector('#item_table tbody');
            if (!lastTbody) return;

            const nameEl = lastTbody.querySelector('input[name="item_name"]') as HTMLInputElement;
            const descEl = lastTbody.querySelector('textarea[name="item_description"]') as HTMLTextAreaElement;
            const priceEl = lastTbody.querySelector('input[name="item_price"]') as HTMLInputElement;
            const qtyEl = lastTbody.querySelector('input[name="item_quantity"]') as HTMLInputElement;
            const taxEl = lastTbody.querySelector('select[name="item_tax_rate_id"]') as HTMLSelectElement;
            const taskIdEl = lastTbody.querySelector('input[name="item_task_id"]') as HTMLInputElement;

            if (nameEl) nameEl.value = task.name || '';
            if (descEl) descEl.value = task.description || '';
            if (priceEl) priceEl.value = task.price || '';
            if (qtyEl) qtyEl.value = '1';
            if (taxEl) taxEl.value = productDefaultTaxRateId || '';
            if (taskIdEl) taskIdEl.value = task.id || '';
        });
    }

    /**
     * Handle task reset button
     */
    private async handleTaskReset(): Promise<void> {
        const tasksTable = document.querySelector('#tasks_table') as HTMLElement;
        if (!tasksTable) return;

        const lookupUrl = `${location.origin}/invoice/task/lookup?rt=true`;
        tasksTable.innerHTML = '<h2 class="text-center"><i class="fa fa-spin fa-spinner"></i></h2>';

        // ES2024: Use Promise.withResolvers for better async control
        const { promise, resolve, reject } = Promise.withResolvers<void>();
        
        const timeoutId = setTimeout(() => {
            reject(new Error('Task lookup timeout', {
                cause: 'Server did not respond within expected timeframe'
            }));
        }, 10000); // 10 second timeout

        try {
            const response = await fetch(lookupUrl, {
                cache: 'no-store',
                credentials: 'same-origin'
            });

            const html = await response.text();
            
            // Secure HTML insertion using DOMParser to prevent XSS
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const fragment = document.createDocumentFragment();
            
            // ES2024: Process DOM children in reverse order for better performance
            Array.from(doc.body.children).toReversed().forEach(child => {
                fragment.insertBefore(child, fragment.firstChild);
            });
            
            tasksTable.innerHTML = '';
            tasksTable.appendChild(fragment);
            
            this.updateSelectTaskButtonState(tasksTable);
            clearTimeout(timeoutId);
            resolve();
        } catch (error) {
            clearTimeout(timeoutId);
            console.error('task lookup load failed', error);
            reject(error);
        }

        // Use the promise to handle completion or errors
        promise.then(() => {
            // Enable button after successful reset
            document.querySelectorAll('.select-items-confirm-task').forEach((btn: Element) => {
                (btn as HTMLButtonElement).removeAttribute('disabled');
            });
        }).catch((error) => {
            console.error('Task reset failed:', error);
            // Still enable buttons even if reset failed
            document.querySelectorAll('.select-items-confirm-task').forEach((btn: Element) => {
                (btn as HTMLButtonElement).removeAttribute('disabled');
            });
        });
    }
}