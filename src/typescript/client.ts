import { parsedata, getJson, ApiResponse, RequestParams } from './utils.js';

// Client-specific interfaces
interface ClientFormData extends RequestParams {
    client_name: string;
    client_surname: string;
    client_email: string;
}

interface ClientNoteData extends RequestParams {
    client_id: string;
    client_note: string;
}

// Helper to get form field value safely
function getFieldValue(id: string): string {
    const element = document.getElementById(id) as HTMLInputElement | HTMLSelectElement | null;
    return element?.value || '';
}

// Helper to set button loading state
function setButtonLoading(button: HTMLElement, isLoading: boolean, originalHtml?: string): void {
    if (isLoading) {
        button.innerHTML = '<h6 class="text-center"><i class="fa fa-spin fa-spinner"></i></h6>';
        (button as HTMLButtonElement).disabled = true;
    } else {
        button.innerHTML =
            originalHtml || '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
        (button as HTMLButtonElement).disabled = false;
    }
}

// Client handler class
export class ClientHandler {
    constructor() {
        this.bindEventListeners();
    }

    private bindEventListeners(): void {
        document.addEventListener('click', this.handleClick.bind(this), true);
        document.addEventListener('submit', this.handleSubmit.bind(this), true);
    }

    private handleClick(event: Event): void {
        const target = event.target as HTMLElement;

        // Client create confirm
        const createBtn = target.closest('#client_create_confirm') as HTMLElement;
        if (createBtn) {
            this.handleClientCreateConfirm(createBtn);
            return;
        }

        // Save client note
        const saveNoteBtn = target.closest('#save_client_note_new') as HTMLElement;
        if (saveNoteBtn) {
            this.handleSaveClientNote(saveNoteBtn);
            return;
        }

        // Delete client note
        const deleteNoteBtn = target.closest('.client-note-delete-btn') as HTMLElement;
        if (deleteNoteBtn) {
            this.handleDeleteClientNote(deleteNoteBtn);
            return;
        }
    }

    private handleSubmit(event: Event): void {
        const target = event.target as HTMLFormElement;

        // Quote form submission
        if (target.id === 'QuoteForm') {
            this.handleQuoteFormSubmit(event as SubmitEvent);
            return;
        }

        // Invoice form submission
        if (target.id === 'InvForm') {
            this.handleInvoiceFormSubmit(event as SubmitEvent);
            return;
        }
    }

    private async handleClientCreateConfirm(createBtn: HTMLElement): Promise<void> {
        const url = `${location.origin}/invoice/client/create_confirm`;
        const btn = (document.querySelector('.client_create_confirm') as HTMLElement) || createBtn;
        const currentUrl = new URL(location.href);

        // Set loading state
        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const payload: ClientFormData = {
                client_name: getFieldValue('client_name'),
                client_surname: getFieldValue('client_surname'),
                client_email: getFieldValue('client_email'),
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                }
                // Navigate and reload as per original behavior
                window.location.href = currentUrl.href;
                window.location.reload();
            } else {
                if (btn) {
                    setButtonLoading(btn, false);
                }
                console.warn('create_confirm response', data);
            }
        } catch (error) {
            console.warn(error);
            if (btn) {
                setButtonLoading(btn, false);
            }
            alert('An error occurred while creating client. See console for details.');
        }
    }

    private async handleSaveClientNote(saveNoteBtn: HTMLElement): Promise<void> {
        const url = `${location.origin}/invoice/client/save_client_note_new`;
        const loadNotesUrl = `${location.origin}/invoice/client/load_client_notes`;
        const btn = (document.querySelector('.save_client_note') as HTMLElement) || saveNoteBtn;
        const currentUrl = new URL(location.href);

        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const payload: ClientNoteData = {
                client_id: getFieldValue('client_id'),
                client_note: getFieldValue('client_note'),
            };

            const response = await getJson<ApiResponse>(url, payload);
            const data = parsedata(response);

            if (data.success === 1) {
                if (btn) {
                    btn.innerHTML = '<h2 class="text-center"><i class="fa fa-check"></i></h2>';
                }

                // Clear the client note field
                const noteEl = document.getElementById('client_note') as HTMLInputElement;
                if (noteEl) noteEl.value = '';

                // Reload notes list (replacing jQuery .load behavior)
                const notesList = document.getElementById('notes_list');
                if (notesList) {
                    const loadUrl = `${loadNotesUrl}?client_id=${encodeURIComponent(payload.client_id)}`;
                    try {
                        const notesResponse = await fetch(loadUrl, {
                            cache: 'no-store',
                            credentials: 'same-origin',
                        });
                        const html = await notesResponse.text();
                        
                        // Only update if we get valid partial HTML (not a full page)
                        if (html && !html.includes('<!DOCTYPE') && !html.includes('<html')) {
                            notesList.innerHTML = html;
                        } else {
                            console.warn('Received full page HTML instead of notes fragment, reloading page');
                            window.location.reload();
                            return;
                        }
                    } catch (loadError) {
                        console.error('load_client_notes failed', loadError);
                        // Fallback to page reload on error
                        window.location.reload();
                        return;
                    }
                }

                // Reset button state without reloading
                setTimeout(() => {
                    if (btn) {
                        setButtonLoading(btn, false, '<h6 class="text-center"><i class="fa fa-save"></i></h6>');
                    }
                }, 1000);
            } else {
                // Handle validation errors
                this.clearValidationErrors();

                if (data.validation_errors) {
                    this.showValidationErrors(data.validation_errors);
                }

                if (btn) {
                    setButtonLoading(btn, false);
                }
            }
        } catch (error) {
            console.warn(error);
            if (btn) {
                setButtonLoading(btn, false);
            }
            alert('An error occurred while saving client note. See console for details.');
        }
    }

    private async handleDeleteClientNote(deleteBtn: HTMLElement): Promise<void> {
        const noteId = deleteBtn.getAttribute('data-note-id');
        if (!noteId) {
            console.error('No note ID found on delete button');
            return;
        }

        // Confirm deletion
        if (!confirm('Are you sure you want to delete this note?')) {
            return;
        }

        const url = `${location.origin}/invoice/client/delete_client_note`;
        const originalHtml = deleteBtn.innerHTML;

        try {
            // Set loading state
            deleteBtn.innerHTML = '<i class="fa fa-spin fa-spinner"></i>';
            (deleteBtn as HTMLButtonElement).disabled = true;

            // Use the same pattern as other client note operations
            const response = await fetch(`${url}?note_id=${encodeURIComponent(noteId)}`, {
                method: 'GET',
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success === 1) {
                    // Remove the note panel from the DOM
                    const notePanel = deleteBtn.closest('.panel');
                    if (notePanel) {
                        notePanel.remove();
                    }
                } else {
                    // Server returned error
                    deleteBtn.innerHTML = originalHtml;
                    (deleteBtn as HTMLButtonElement).disabled = false;
                    alert(data.message || 'Failed to delete note. Please try again.');
                }
            } else {
                // HTTP error
                const responseText = await response.text();
                console.error('Delete client note HTTP error:', {
                    status: response.status,
                    statusText: response.statusText,
                    body: responseText.substring(0, 500)
                });
                
                deleteBtn.innerHTML = originalHtml;
                (deleteBtn as HTMLButtonElement).disabled = false;
                alert('Failed to delete note. Please try again.');
            }
        } catch (error) {
            console.error('Delete client note error:', error);
            // Restore button state on error
            deleteBtn.innerHTML = originalHtml;
            (deleteBtn as HTMLButtonElement).disabled = false;
            alert('An error occurred while deleting the note. Please try again.');
        }
    }

    private clearValidationErrors(): void {
        document.querySelectorAll('.control-group').forEach(group => {
            group.classList.remove('error');
        });
    }

    private showValidationErrors(validationErrors: Record<string, string[]>): void {
        Object.keys(validationErrors).forEach(key => {
            const element = document.getElementById(key);
            if (element?.parentElement) {
                element.parentElement.classList.add('has-error');
            }
        });
    }

    private async handleQuoteFormSubmit(event: SubmitEvent): Promise<void> {
        event.preventDefault(); // Prevent default form submission
        
        const form = event.target as HTMLFormElement;
        const submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement;
        const originalHtml = submitButton?.innerHTML;
        
        if (submitButton) {
            setButtonLoading(submitButton, true);
        }

        // Show success indicator and close modal immediately
        if (submitButton) {
            submitButton.innerHTML = '<i class="fa fa-check"></i>';
        }
        
        // Close the modal if it exists
        const modal = document.getElementById('modal-add-quote') || document.getElementById('modal-add-client');
        if (modal) {
            const bootstrapModal = (window as any).bootstrap?.Modal?.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        }

        // Wait a moment for the modal to close, then submit the form normally
        // This allows the server redirect to work properly
        setTimeout(() => {
            form.submit();
        }, 300);
    }

    private async handleInvoiceFormSubmit(event: SubmitEvent): Promise<void> {
        event.preventDefault(); // Prevent default form submission
        
        const form = event.target as HTMLFormElement;
        const submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement;
        const originalHtml = submitButton?.innerHTML;
        
        if (submitButton) {
            setButtonLoading(submitButton, true);
        }

        // Show success indicator and close modal immediately
        if (submitButton) {
            submitButton.innerHTML = '<i class="fa fa-check"></i>';
        }
        
        // Close the modal if it exists
        const modal = document.getElementById('modal-add-inv') || document.getElementById('modal-add-client');
        if (modal) {
            const bootstrapModal = (window as any).bootstrap?.Modal?.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        }

        // Wait a moment for the modal to close, then submit the form normally
        // This allows the server redirect to work properly
        setTimeout(() => {
            form.submit();
        }, 300);
    }
}
