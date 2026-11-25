import { createRequire } from 'node:module';
import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';
const require = createRequire(process.env.VIGIA_BROWSER_TOOLING || import.meta.url);
const { chromium } = require('playwright');
const base = process.env.VIGIA_BASE_URL || 'http://127.0.0.1:8000';
const manifest = JSON.parse(await readFile('public/build/manifest.json', 'utf8'));
const script = `/build/${manifest['resources/js/app.js'].file}`;
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
try {
    const page = await context.newPage();
    await page.goto(`${base}/reportes/mis-reportes`);
    await page.getByText('Todavía no hay reportes guardados', { exact: false }).waitFor();
    // Isolated browser fixture: no reports are inserted into the local database.
    await page.route('**/__device-receipt-test', route => route.fulfill({ contentType: 'text/html', body: `
        <span data-device-receipt data-code="REP-2099-1234" data-created-at="2099-01-02T10:00:00Z"></span>
        <p data-device-save-status></p><script type="module" src="${script}"></script>` }));
    await page.goto(`${base}/__device-receipt-test`);
    await page.getByText('Guardado en «Mis reportes en este dispositivo».').waitFor();
    await page.reload();
    await page.getByText('Guardado en «Mis reportes en este dispositivo».').waitFor();
    await page.goto(`${base}/reportes/mis-reportes`);
    await page.getByRole('link', { name: 'Consultar reporte REP-2099-1234' }).waitFor();
    assert.equal(await page.locator('[data-device-list] li').count(), 1);
    assert.equal(await page.getByRole('link', { name: 'Consultar reporte REP-2099-1234' }).getAttribute('href'), `${base}/reportes/REP-2099-1234`);
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    const otherContext = await browser.newContext();
    const otherPage = await otherContext.newPage();
    await otherPage.goto(`${base}/reportes/mis-reportes`);
    await otherPage.getByText('Todavía no hay reportes guardados', { exact: false }).waitFor();
    await otherContext.close();
    await page.evaluate(() => localStorage.setItem('vigia.device-reports.v1', JSON.stringify([{ code: 'javascript:alert(1)', createdAt: '2099-01-01' }])));
    await page.reload();
    await page.getByText('Todavía no hay reportes guardados', { exact: false }).waitFor();
    await page.addInitScript(() => Object.defineProperty(window, 'localStorage', { get() { throw new Error('Storage blocked'); } }));
    await page.reload();
    await page.getByText('No pudimos leer la copia', { exact: false }).waitFor();
    await page.goto(`${base}/__device-receipt-test`);
    await page.getByText('No se pudo guardar una copia en el navegador.', { exact: false }).waitFor();
    console.log('PASS: device list, automatic saving, persistence, deduplication, isolation and blocked storage.');
} finally { await browser.close(); }
