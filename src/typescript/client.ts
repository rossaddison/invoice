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
        button.innerHTML = originalHtml || '<h6 class="text-center"><i class="fa fa-check"></i></h6>';
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
    }

    private async handleClientCreateConfirm(createBtn: HTMLElement): Promise<void> {
        const url = `${location.origin}/invoice/client/create_confirm`;
        const btn = document.querySelector('.client_create_confirm') as HTMLElement || createBtn;
        const currentUrl = new URL(location.href);

        // Set loading state
        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const payload: ClientFormData = {
                client_name: getFieldValue('client_name'),
                client_surname: getFieldValue('client_surname'),
                client_email: getFieldValue('client_email')
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
        const btn = document.querySelector('.save_client_note') as HTMLElement || saveNoteBtn;
        const currentUrl = new URL(location.href);

        if (btn) {
            setButtonLoading(btn, true);
        }

        try {
            const payload: ClientNoteData = {
                client_id: getFieldValue('client_id'),
                client_note: getFieldValue('client_note')
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
                            credentials: 'same-origin' 
                        });
                        const html = await notesResponse.text();
                        notesList.innerHTML = html;
                        console.log(html);
                    } catch (loadError) {
                        console.error('load_client_notes failed', loadError);
                    }
                }

                // Navigate and reload as per original behavior
                window.location.href = currentUrl.href;
                window.location.reload();
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
}