/**
 * Click-to-open behaviour for .dropdown-submenu flyouts (Settings menu's
 * Company/Email/Access/Preferences/More groups, and the Performance menu's
 * Prometheus Monitoring entry -- see App\Widget\SubMenu and
 * .dropdown-submenu/.dropdown-menu-submenu in components.css).
 *
 * components.css already opens a flyout on plain CSS :hover/:focus-within,
 * which costs nothing and works well with a mouse moving in a straight
 * line -- but real pointer movement isn't always a straight line, and
 * :hover alone gives touch users no way to open one at all. This adds an
 * explicit click toggle as the reliable primary mechanism; CSS hover
 * remains a (harmless, still-correct) bonus for desktop mouse users who
 * approach it that way.
 */
export function initNavFlyoutSubmenu(): void {
    document.addEventListener('click', (e: MouseEvent) => {
        const target = e.target as HTMLElement;
        const toggle = target.closest<HTMLElement>('.dropdown-submenu-toggle');

        if (toggle === null) {
            // Clicked outside any flyout toggle -- close whatever is open so
            // state doesn't linger once the parent Bootstrap dropdown itself
            // closes (its own outside-click handler runs independently of
            // this one).
            document.querySelectorAll('.dropdown-submenu.show').forEach((el) => {
                el.classList.remove('show');
            });
            return;
        }

        // A click inside this app's own .dropdown-menu never reaches
        // Bootstrap5's document-level outside-click listener in the first
        // place (Dropdown.js only auto-closes on clicks outside its own
        // .dropdown-menu), so no stopPropagation() is needed here to keep
        // the parent dropdown open -- this only prevents the toggle's own
        // click from being read as "outside" by the branch above.
        e.preventDefault();
        e.stopPropagation();

        const submenu = toggle.closest<HTMLElement>('.dropdown-submenu');
        if (submenu === null) return;

        const wasOpen = submenu.classList.contains('show');

        // Only one open at a time among siblings in the same menu level.
        const parentList = submenu.parentElement;
        parentList?.querySelectorAll('.dropdown-submenu.show').forEach((el) => {
            el.classList.remove('show');
        });

        if (!wasOpen) {
            submenu.classList.add('show');
        }
    });

    document.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.dropdown-submenu.show').forEach((el) => {
            el.classList.remove('show');
        });
    });
}
