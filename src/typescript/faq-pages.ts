/**
 * Small nav-highlighting behaviors for two static FAQ pages. Both no-op on
 * every other page since they're guarded by page-specific selectors.
 * Extracted from inline <script> blocks so script-src no longer needs
 * 'unsafe-inline'.
 */

/** resources/views/invoice/info/javascript_analysis.php — smooth-scroll nav */
export function initJavascriptAnalysisFaq(): void {
    const sections = document.querySelectorAll('section[id]');
    if (sections.length === 0) return;

    document.querySelectorAll('.nav-pills .nav-link').forEach(link => {
        link.addEventListener('click', function (this: Element, e: Event) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = targetId ? document.querySelector(targetId) : null;
            targetElement?.scrollIntoView({ behavior: 'smooth' });
            document.querySelectorAll('.nav-pills .nav-link').forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const navLinks = document.querySelectorAll('.nav-pills .nav-link');
    globalThis.addEventListener('scroll', () => {
        let currentSection = '';
        sections.forEach(section => {
            const sectionTop = (section as HTMLElement).offsetTop - 100;
            if (globalThis.pageYOffset >= sectionTop) {
                currentSection = `#${section.getAttribute('id')}`;
            }
        });
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === currentSection) {
                link.classList.add('active');
            }
        });
    });
}

/** resources/views/invoice/info/codeception_selectors_checklist.php — section show/hide nav */
export function initCodeceptionChecklistFaq(): void {
    const sections = document.querySelectorAll('.section');
    if (sections.length === 0) return;

    const navLinks = document.querySelectorAll('.nav-pills .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function (this: Element, e: Event) {
            e.preventDefault();
            navLinks.forEach(nl => nl.classList.remove('active'));
            this.classList.add('active');

            const targetId = this.getAttribute('href')?.substring(1);
            sections.forEach(section => {
                (section as HTMLElement).style.display = section.id === targetId ? 'block' : 'none';
            });
        });
    });

    sections.forEach((section, index) => {
        if (index !== 0) {
            (section as HTMLElement).style.display = 'none';
        }
    });
}
