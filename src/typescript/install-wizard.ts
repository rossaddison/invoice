/**
 * /install setup wizard: "Test Connection" / "Save & Continue" (database step)
 * and "Build Tables" (build step) buttons. No-ops on every other page since
 * it's guarded by the presence of [data-install-wizard].
 */
interface InstallActionResponse {
    success: boolean;
    message: string;
}

function csrfValue(): string {
    return (document.getElementById('install-csrf') as HTMLInputElement | null)?.value ?? '';
}

function fieldValue(id: string): string {
    return (document.getElementById(id) as HTMLInputElement | null)?.value ?? '';
}

function showResult(containerId: string, response: InstallActionResponse): void {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.textContent = response.message;
    container.className = `alert ${response.success ? 'alert-success' : 'alert-danger'} py-2`;
}

async function postInstallAction(url: string): Promise<InstallActionResponse> {
    const body = new URLSearchParams();
    body.append('_csrf', csrfValue());
    body.append('Install[dbHost]', fieldValue('install-db-host'));
    body.append('Install[dbName]', fieldValue('install-db-name'));
    body.append('Install[dbUser]', fieldValue('install-db-user'));
    body.append('Install[dbPassword]', fieldValue('install-db-password'));

    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
    });
    return (await response.json()) as InstallActionResponse;
}

async function handleTestConnection(button: HTMLButtonElement): Promise<void> {
    button.disabled = true;
    try {
        const result = await postInstallAction('/install/test-connection');
        showResult('install-db-result', result);
        const saveButton = document.querySelector<HTMLButtonElement>('[data-install-action="save-database"]');
        if (saveButton) saveButton.disabled = !result.success;
    } finally {
        button.disabled = false;
    }
}

async function handleSaveDatabase(button: HTMLButtonElement): Promise<void> {
    button.disabled = true;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/install/database';
    for (const [name, value] of new URLSearchParams({
        _csrf: csrfValue(),
        'Install[dbHost]': fieldValue('install-db-host'),
        'Install[dbName]': fieldValue('install-db-name'),
        'Install[dbUser]': fieldValue('install-db-user'),
        'Install[dbPassword]': fieldValue('install-db-password'),
    })) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}

async function handleBuildTables(button: HTMLButtonElement): Promise<void> {
    button.disabled = true;
    button.textContent = 'Building tables — this can take a minute…';
    try {
        const result = await postInstallAction('/install/build-tables');
        showResult('install-build-result', result);
        if (result.success) {
            globalThis.location.href = '/install';
        } else {
            button.disabled = false;
            button.textContent = 'Build Tables';
        }
    } catch {
        button.disabled = false;
        button.textContent = 'Build Tables';
    }
}

export function initInstallWizard(): void {
    if (!document.querySelector('[data-install-wizard]')) return;

    document.addEventListener('click', (e: MouseEvent) => {
        const target = e.target as HTMLElement | null;
        const actionEl = target?.closest<HTMLButtonElement>('[data-install-action]');
        if (!actionEl) return;

        switch (actionEl.dataset['installAction']) {
            case 'test-connection':
                void handleTestConnection(actionEl);
                break;
            case 'save-database':
                void handleSaveDatabase(actionEl);
                break;
            case 'build-tables':
                void handleBuildTables(actionEl);
                break;
            default:
                break;
        }
    });
}
