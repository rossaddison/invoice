import { chromium, type Page } from 'playwright';
import { generateSync } from 'otplib';

/**
 * Renders the actual invoice document (the same one mPDF converts, via
 * inv/pdfPlaywrightDocument) to PDF using a real Chromium engine instead of
 * mPDF's HTML-to-PDF conversion — mPDF does not reliably support Bootstrap5
 * (flexbox/grid, modern CSS), so this exists to produce the same
 * professional-looking document with better rendering fidelity. This is
 * NOT the admin inv/view screen — that has its own toolbar/breadcrumb/
 * edit-form UI and produces an unusable result if rendered to PDF directly.
 *
 * Every inv/* route requires an authenticated session AND (once a user has
 * 2FA enabled) a valid TOTP code, so this drives the real /login form and
 * the real 2FA setup/verify forms rather than minting a session directly.
 *
 * Usage:
 *   node --env-file=.env playwright/render-invoice.js <invoice-id> [output-path]
 *
 * Required environment variables (see .env.example):
 *   BASE_URL                    Defaults to http://localhost
 *   PLAYWRIGHT_TEST_EMAIL        Login of a real, working user account
 *   PLAYWRIGHT_TEST_PASSWORD     That user's password
 *   PLAYWRIGHT_TEST_TOTP_SECRET  Only needed once the account already has
 *                                2FA enabled (see console output on first
 *                                run, which walks through setup and prints
 *                                the secret to save here for next time).
 */

function requireEnv(name: string): string {
    const value = process.env[name];
    if (!value) {
        throw new Error(`Missing required environment variable: ${name}`);
    }
    return value;
}

/**
 * Handles the 2FA branch after a successful email/password login: either
 * first-time setup (auth/showSetup — scans the freshly generated secret
 * straight off the page) or a returning-user verify (auth/verifyLogin —
 * needs PLAYWRIGHT_TEST_TOTP_SECRET from a previous setup run).
 */
async function handleTwoFactorAuth(page: Page): Promise<void> {
    if (page.url().includes('/showSetup')) {
        const secret = await page.inputValue('#secretInput');
        console.error(
            `First-time 2FA setup for this account — add this to .env to skip ` +
            `setup on future runs:\nPLAYWRIGHT_TEST_TOTP_SECRET=${secret}`,
        );
        await page.fill('#code', generateSync({ secret }));
        await page.click('#code-button');
        await page.waitForURL(
            (url) => !url.pathname.includes('/showSetup') && !url.pathname.includes('/verifySetup'),
            { waitUntil: 'networkidle' },
        );
        return;
    }
    if (page.url().includes('/verifyLogin')) {
        const secret = requireEnv('PLAYWRIGHT_TEST_TOTP_SECRET');
        await page.fill('#code', generateSync({ secret }));
        await page.click('#code-button');
        await page.waitForURL((url) => !url.pathname.includes('/verifyLogin'), { waitUntil: 'networkidle' });
    }
}

async function login(page: Page, baseUrl: string, email: string, password: string): Promise<void> {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
    await page.fill('input[name="Login[login]"]', email);
    await page.fill('input[name="Login[password]"]', password);
    await page.click('#login-button');
    await page.waitForURL((url) => !url.pathname.includes('/login'), { waitUntil: 'networkidle' });
    await handleTwoFactorAuth(page);
}

async function main(): Promise<void> {
    const invoiceId = process.argv[2];
    if (!invoiceId || !/^\d+$/.test(invoiceId)) {
        console.error('Usage: node render-invoice.js <invoice-id> [output-path]');
        process.exit(1);
    }
    const outputPath = process.argv[3] ?? `playwright/output/invoice-${invoiceId}.pdf`;

    const baseUrl = process.env.BASE_URL || 'http://localhost';
    const email = requireEnv('PLAYWRIGHT_TEST_EMAIL');
    const password = requireEnv('PLAYWRIGHT_TEST_PASSWORD');

    const browser = await chromium.launch();
    try {
        const page = await browser.newPage();

        await login(page, baseUrl, email, password);

        // Route is /{_language}/invoice/inv/pdfPlaywrightDocument/{id} — the
        // /invoice/ segment is easy to miss and was missing here for a
        // while, which silently "succeeded" by rendering the app's own 404
        // page instead of the invoice. The status check below exists
        // specifically to catch that failure mode loudly instead of
        // producing a wrong-content PDF.
        const response = await page.goto(`${baseUrl}/invoice/inv/pdfPlaywrightDocument/${invoiceId}`, { waitUntil: 'networkidle' });
        if (!response || !response.ok()) {
            throw new Error(
                `Failed to load invoice ${invoiceId}: HTTP ${response?.status() ?? 'no response'} at ${page.url()} ` +
                `— check that PLAYWRIGHT_TEST_EMAIL is admin, or owns/is linked to this invoice as an observer.`,
            );
        }

        // page.pdf() defaults to emulating "print" media, not "screen" —
        // matters for any print-specific CSS this app has, so force screen
        // media to match what a user actually sees in-browser.
        await page.emulateMedia({ media: 'screen' });

        // Playwright defaults to zero margins on all sides — mPDF's own
        // defaults (MpdfHelper::$marginLeft/Right/Top/Bottom) are 15mm/
        // 15mm/16mm/16mm, matched here for visual parity with the existing
        // "Download PDF" output.
        await page.pdf({
            path: outputPath,
            format: 'A4',
            printBackground: true,
            margin: { left: '15mm', right: '15mm', top: '16mm', bottom: '16mm' },
        });
        console.log(`PDF written to ${outputPath}`);
    } finally {
        await browser.close();
    }
}

main().catch((error: unknown) => {
    console.error(error);
    process.exit(1);
});
