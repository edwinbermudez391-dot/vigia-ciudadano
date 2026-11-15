import './bootstrap';
import './citizen';
import './inspector-location';

const publicMenu = document.querySelector('[data-public-menu]');
const publicNavigation = document.getElementById('public-navigation');
const publicDesktop = window.matchMedia('(min-width: 64rem)');
if (publicMenu && publicNavigation) {
    document.body.classList.add('navigation-ready');
    const setPublicMenu = (open, restoreFocus = false) => {
        publicNavigation.classList.toggle('is-open', open);
        publicMenu.setAttribute('aria-expanded', String(open));
        publicMenu.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
        if (restoreFocus) publicMenu.focus();
    };
    publicMenu.addEventListener('click', () => setPublicMenu(publicMenu.getAttribute('aria-expanded') !== 'true'));
    publicNavigation.addEventListener('click', (event) => {
        if (event.target.closest('a') && !publicDesktop.matches) setPublicMenu(false, true);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && publicNavigation.classList.contains('is-open')) setPublicMenu(false, true);
    });
    publicDesktop.addEventListener('change', () => {
        const active = document.activeElement;
        setPublicMenu(false);
        if (!publicDesktop.matches && publicNavigation.contains(active)) publicMenu.focus();
        if (publicDesktop.matches && active === publicMenu) publicNavigation.querySelector('a')?.focus();
    });
}

const sidebarCollapse = document.querySelector('[data-sidebar-collapse]');
const sidebarMobile = document.querySelector('[data-sidebar-mobile]');
if (sidebarMobile) document.body.classList.add('sidebar-ready');
const compactNavigation = window.matchMedia('(width < 64rem)');
const sidebarRail = window.matchMedia('(min-width: 80rem)');
function setSidebarCollapsed(collapsed) {
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    sidebarCollapse?.setAttribute('aria-expanded', String(!collapsed));
    const label = collapsed ? 'Ampliar menú lateral' : 'Contraer menú lateral';
    sidebarCollapse?.setAttribute('aria-label', label);
    sidebarCollapse?.setAttribute('title', label);
    const caption = sidebarCollapse?.querySelector('.sidebar-caption');
    if (caption) caption.textContent = collapsed ? 'Ampliar menú' : 'Contraer menú';
}
try { setSidebarCollapsed(localStorage.getItem('vigia.sidebar.collapsed') === 'true'); }
catch (error) { console.info('La preferencia del menú no se puede guardar en este navegador.'); }
sidebarCollapse?.addEventListener('click', () => {
    const collapsed = !document.body.classList.contains('sidebar-collapsed');
    setSidebarCollapsed(collapsed);
    try { localStorage.setItem('vigia.sidebar.collapsed', String(collapsed)); }
    catch (error) { console.info('La preferencia del menú solo se aplicará durante esta visita.'); }
});
sidebarMobile?.addEventListener('click', () => {
    const open = document.body.classList.toggle('sidebar-open');
    sidebarMobile.setAttribute('aria-expanded', String(open));
});
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !document.body.classList.contains('sidebar-open')) return;
    document.body.classList.remove('sidebar-open');
    sidebarMobile?.setAttribute('aria-expanded', 'false');
    sidebarMobile?.focus();
});
compactNavigation.addEventListener('change', () => {
    const active = document.activeElement;
    document.body.classList.remove('sidebar-open');
    sidebarMobile?.setAttribute('aria-expanded', 'false');
    if (compactNavigation.matches && active?.closest('#primary-sidebar')) sidebarMobile?.focus();
    if (!compactNavigation.matches && active === sidebarMobile) document.querySelector('.sidebar-nav a')?.focus();
});
sidebarRail.addEventListener('change', () => {
    if (!sidebarRail.matches && document.activeElement === sidebarCollapse) {
        document.querySelector('.sidebar-nav a')?.focus();
    }
});

document.querySelectorAll('[data-list-search]').forEach((search) => {
    const list = document.getElementById(search.dataset.listSearch);
    if (!list) return;
    const input = search.querySelector('input[type="search"]');
    const items = [...list.querySelectorAll('[data-search-item]')];
    const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
    function filter() {
        const query = normalize(input.value.trim());
        let matches = 0;
        items.forEach((item) => {
            const match = normalize(item.textContent).includes(query);
            item.hidden = !match;
            if (match) matches++;
        });
        search.querySelector('[data-search-count]').textContent = `${matches} de ${items.length} elementos visibles.`;
        const empty = list.querySelector('[data-search-empty]');
        if (empty) empty.hidden = matches > 0;
    }
    input.addEventListener('input', filter);
    search.querySelector('[data-search-reset]').addEventListener('click', () => { input.value = ''; filter(); input.focus(); });
});

// Progressive enhancement only: forms, endpoints and permissions remain server-owned.
const dialogs = [...document.querySelectorAll('[role="dialog"][aria-modal="true"]')];
let activeDialog = null;
let returnFocus = null;
let lastOutsideFocus = document.activeElement;
let savedOverflow = '';
const inertStates = new Map();
const focusable = (root) => [...root.querySelectorAll(
    'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]',
)].filter((element) => element.getClientRects().length && !element.closest('[inert]'));

document.addEventListener('focusin', (event) => {
    if (!event.target.closest('[role="dialog"]')) lastOutsideFocus = event.target;
});

function syncDialogs() {
    const visible = dialogs.find((dialog) => !dialog.classList.contains('hidden')) ?? null;
    if (visible === activeDialog) return;
    inertStates.forEach((value, element) => { element.inert = value; });
    inertStates.clear();
    if (activeDialog) {
        document.body.style.overflow = savedOverflow;
        if (returnFocus?.isConnected) returnFocus.focus();
    }
    activeDialog = visible;
    if (!visible) return;
    returnFocus = lastOutsideFocus;
    savedOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    // Inert only siblings along the ancestor chain: works for dialogs inside forms.
    let branch = visible;
    while (branch.parentElement && branch !== document.body) {
        [...branch.parentElement.children].forEach((sibling) => {
            if (sibling !== branch && !['SCRIPT', 'STYLE', 'LINK'].includes(sibling.tagName)) {
                inertStates.set(sibling, sibling.inert);
                sibling.inert = true;
            }
        });
        branch = branch.parentElement;
    }
    const target = focusable(visible)[0];
    if (target) target.focus();
    else { visible.tabIndex = -1; visible.focus(); }
}

dialogs.forEach((dialog) => new MutationObserver(syncDialogs).observe(dialog, { attributes: true, attributeFilter: ['class'] }));
syncDialogs();
document.addEventListener('keydown', (event) => {
    if (!activeDialog) return;
    if (event.key === 'Escape') {
        const close = activeDialog.querySelector('[data-assign_close], [data-register_close], [data-close-ticket-modal], #cancel-category-btn, [data-close-duplicates]');
        close?.click();
    }
    if (event.key !== 'Tab') return;
    const items = focusable(activeDialog);
    const first = items[0];
    const last = items.at(-1);
    if (!first) { event.preventDefault(); return; }
    if (event.shiftKey && (document.activeElement === first || !activeDialog.contains(document.activeElement))) {
        event.preventDefault(); last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !activeDialog.contains(document.activeElement))) {
        event.preventDefault(); first.focus();
    }
});

// Preserve native browser validation before asking to confirm important operations.
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

const summary = document.querySelector('[data-error-summary]');
if (summary && !activeDialog) summary.focus();
