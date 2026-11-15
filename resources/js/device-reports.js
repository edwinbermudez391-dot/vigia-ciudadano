const key = 'vigia.device-reports.v1';
const validCode = code => typeof code === 'string' && /^REP-\d{4}-\d{4,5}$/.test(code);

function readReports() {
    const value = JSON.parse(localStorage.getItem(key) || '[]');
    if (!Array.isArray(value)) return [];
    const seen = new Set();
    return value.filter(item => {
        if (!item || !validCode(item.code) || typeof item.createdAt !== 'string' || !Number.isFinite(Date.parse(item.createdAt)) || seen.has(item.code)) return false;
        seen.add(item.code);
        return true;
    }).map(({ code, createdAt }) => ({ code, createdAt }));
}

// Only a successful POST can provide this marker. Viewing someone else's
// public receipt never adds their report to this browser's list.
const receipt = document.querySelector('[data-device-receipt]');
if (receipt && validCode(receipt.dataset.code)) {
    const status = document.querySelector('[data-device-save-status]');
    try {
        let reports;
        try { reports = readReports(); } catch (error) {
            if (!(error instanceof SyntaxError)) throw error;
            reports = [];
        }
        const item = { code: receipt.dataset.code, createdAt: receipt.dataset.createdAt };
        reports = [item, ...reports.filter(report => report.code !== item.code)];
        localStorage.setItem(key, JSON.stringify(reports));
        status.textContent = 'Guardado en «Mis reportes en este dispositivo».';
    } catch {
        status.textContent = 'Este navegador no permite guardar la lista. Guarda el enlace desde «Consultar mi reporte» para volver después.';
    }
}

const container = document.querySelector('[data-device-reports]');
if (container) {
    const status = container.querySelector('[data-device-list-status]');
    const list = container.querySelector('[data-device-list]');
    try {
        const reports = readReports().sort((a, b) => Date.parse(b.createdAt) - Date.parse(a.createdAt));
        status.textContent = reports.length ? '' : 'Todavía no hay reportes guardados en este navegador. Los próximos que envíes aparecerán aquí.';
        status.hidden = reports.length > 0;
        list.hidden = reports.length === 0;
        reports.forEach(report => {
            const item = document.createElement('li');
            const number = document.createElement('strong');
            number.textContent = report.code;
            const date = document.createElement('p');
            date.className = 'citizen-help';
            date.textContent = `Enviado el ${new Date(report.createdAt).toLocaleDateString('es-CO')}`;
            const link = document.createElement('a');
            link.className = 'btn-secondary';
            link.href = container.dataset.detailUrl.replace('__CODE__', encodeURIComponent(report.code));
            link.textContent = 'Consultar reporte';
            link.setAttribute('aria-label', `Consultar reporte ${report.code}`);
            item.append(number, date, link);
            list.append(item);
        });
    } catch {
        status.textContent = 'No pudimos leer la lista de este navegador. Puedes consultar tu reporte desde el inicio usando su número.';
    }
}
