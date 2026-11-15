import { initCitizenReport } from './citizen-report';
import './device-reports';

const citizenPage = document.querySelector('.citizen-ui');
if (citizenPage) {
    const textButton = document.querySelector('[data-text-size]');
    const applyTextSize = (large) => {
        citizenPage.classList.toggle('citizen-large-text', large);
        textButton?.setAttribute('aria-pressed', String(large));
        const label = textButton?.querySelector('[data-text-size-label]');
        if (label) label.textContent = large ? 'Restablecer texto' : 'Aumentar texto';
    };
    try { applyTextSize(localStorage.getItem('vigia.citizen.large-text') === 'true'); }
    catch { applyTextSize(false); }
    if (textButton) {
        textButton.hidden = false;
        textButton.addEventListener('click', () => {
            const large = !citizenPage.classList.contains('citizen-large-text');
            applyTextSize(large);
            try { localStorage.setItem('vigia.citizen.large-text', String(large)); }
            catch { textButton.title = 'El tamaño se conservará solo durante esta visita.'; }
        });
    }
}

document.querySelectorAll('[data-report-lookup]').forEach((form) => {
    const input = form.querySelector('input[name=radicado]');
    const error = form.querySelector('[data-lookup-error]');
    const status = form.querySelector('[data-lookup-status]');
    const submit = form.querySelector('button[type=submit]');
    form.noValidate = true;
    const showError = (message) => {
        error.textContent = message;
        error.hidden = false;
        input.setAttribute('aria-invalid', 'true');
        status.textContent = '';
        input.focus();
    };
    input.addEventListener('input', () => { error.hidden = true; input.setAttribute('aria-invalid', 'false'); });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submit.disabled) return;
        const code = input.value.trim().toUpperCase();
        if (!/^REP-\d{4}-\d{4,5}$/.test(code)) {
            showError('Revisa el número. Debe tener un formato como REP-2026-0148.');
            return;
        }
        input.value = code;
        input.setAttribute('aria-invalid', 'false');
        error.hidden = true;
        submit.disabled = true;
        status.textContent = 'Estamos buscando tu reporte…';
        const url = form.dataset.detailUrl.replace('__CODE__', encodeURIComponent(code));
        try {
            // Use the existing public HTML route, not the authenticated API.
            const response = await fetch(url, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
            if (response.status === 404) showError('No encontramos ese número. Revisa que esté escrito correctamente.');
            else if (response.ok && !response.redirected) window.location.assign(url);
            else showError('No pudimos consultar ahora. Espera un momento e inténtalo otra vez.');
        } catch {
            showError('No pudimos conectarnos. Revisa tu conexión e inténtalo otra vez.');
        } finally { submit.disabled = false; }
    });
});

document.querySelectorAll('[data-focus-lookup]').forEach((link) => link.addEventListener('click', () => {
    requestAnimationFrame(() => document.querySelector('[data-report-lookup] input')?.focus({ preventScroll: true }));
}));

const copyButton = document.querySelector('[data-copy-ticket]');
if (copyButton) {
    copyButton.hidden = false;
    copyButton.addEventListener('click', async () => {
        const code = document.getElementById('ticket-code').textContent.trim();
        const status = document.getElementById('copy-feedback');
        let copied = false;
        try {
            if (navigator.clipboard?.writeText) { await navigator.clipboard.writeText(code); copied = true; }
        } catch { status.textContent = 'Intentando copiar el número…'; }
        if (!copied) {
            const field = document.createElement('textarea');
            field.value = code;
            field.style.cssText = 'position:fixed;opacity:0;';
            document.body.append(field);
            field.select();
            try { copied = document.execCommand('copy'); }
            catch { copied = false; }
            field.remove();
            copyButton.focus();
        }
        status.textContent = copied ? 'Número copiado. Guárdalo para consultar tu reporte.' : 'No pudimos copiarlo. Selecciona el número y cópialo, o imprime el comprobante.';
    });
}
document.querySelectorAll('[data-print-receipt]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', () => window.print());
});

const shareReceipt = document.querySelector('[data-share-receipt]');
if (shareReceipt && navigator.share) {
    shareReceipt.hidden = false;
    shareReceipt.addEventListener('click', async () => {
        try {
            await navigator.share({ title: 'Mi reporte — Vigía Ciudadano', text: 'Consulta el estado de mi reporte.', url: shareReceipt.dataset.url });
        } catch (error) {
            if (error.name !== 'AbortError') document.getElementById('copy-feedback').textContent = 'No pudimos compartir. Vuelve a intentarlo.';
        }
    });
}

initCitizenReport(document.querySelector('[data-citizen-report]'));
