// Optional browser tooling is installed outside the application; see the protocol.
import { createRequire } from 'node:module';
import fs from 'node:fs/promises';
import path from 'node:path';
const require = createRequire(process.env.VIGIA_BROWSER_TOOLING || import.meta.url);
const { chromium } = require('playwright');
const axeSource = require('axe-core').source;
const base = (process.env.VIGIA_BASE_URL || 'http://127.0.0.1:8015').replace(/\/$/, '');
const artifacts = process.env.VIGIA_ARTIFACTS || path.resolve('storage/app/ui-checks/citizen');
await fs.mkdir(artifacts, { recursive: true });
const browser = await chromium.launch(process.env.VIGIA_BROWSER_PATH ? { executablePath: process.env.VIGIA_BROWSER_PATH, headless: true } : { channel: 'chrome', headless: true });
const checks = [], layouts = [], errors = [], accessibility = [];
const check = (name, ok) => checks.push({ name, ok: Boolean(ok) });
const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64');
const photo = { name: 'problema.png', mimeType: 'image/png', buffer: png };
const context = await browser.newContext({ viewport: { width: 1366, height: 900 }, permissions: ['clipboard-read', 'clipboard-write'] });
const page = await context.newPage();
page.on('pageerror', (error) => errors.push({ url: page.url(), message: error.message }));
const data = await (await page.request.get(`${base}/api/v1/reports/map-data`)).json();
const ticket = process.env.VIGIA_TICKET || data.data?.[0]?.ticket_code;

async function go(page, url) { return page.goto(`${base}${url}`, { waitUntil: 'networkidle' }); }
async function next(page, step) { await page.locator(`[data-report-step="${step}"] [data-step-next]`).click(); }
async function coordinates(page) {
    await page.locator('[data-coordinates]').evaluate((el) => { el.open = true; });
    await page.locator('#latitude').fill('3.0095'); await page.locator('#longitude').fill('-76.4849');
}
async function axe(page, name) {
    await page.evaluate(axeSource);
    const result = await page.evaluate(async () => window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'] } }));
    accessibility.push({ name, violations: result.violations.map((v) => ({ id: v.id, impact: v.impact, targets: v.nodes.map((n) => n.target) })) });
}
async function inspect(page, name, physicalWidth, zoom) {
    const metrics = await page.evaluate(() => ({ width: innerWidth, scroll: document.documentElement.scrollWidth,
        visibleSteps: [...document.querySelectorAll('[data-report-step]')].filter((el) => !el.hidden).length,
        offenders: [...document.querySelectorAll('main *, header *, footer *')].filter((el) => {
            const r = el.getBoundingClientRect(); return r.width && r.right > innerWidth + 1 && !el.closest('.leaflet-container,.table-scroll') && getComputedStyle(el).position !== 'absolute';
        }).slice(0, 8).map((el) => ({ tag: el.tagName, class: el.className, text: el.textContent.trim().slice(0, 60) })),
    }));
    layouts.push({ name, physicalWidth, zoom, ...metrics });
    if (zoom === 1 && [390, 1366].includes(physicalWidth)) await page.screenshot({ path: path.join(artifacts, `${name}-${physicalWidth}.png`), fullPage: true });
}

try {
    await go(page, '/');
    check('Dos acciones ciudadanas claras', await page.getByRole('link', { name: 'Reportar un problema', exact: true }).isVisible() && await page.getByRole('link', { name: 'Consultar mi reporte', exact: true }).isVisible());
    await page.locator('[data-focus-lookup]').click();
    check('Consultar lleva al campo, sin modal', await page.locator('[data-report-lookup] input').evaluate((el) => el === document.activeElement));
    await page.locator('[data-report-lookup] input').fill('123'); await page.locator('[data-report-lookup] button').click();
    check('Número mal escrito: error junto al campo', await page.locator('[data-lookup-error]').isVisible());
    await page.locator('[data-report-lookup] input').fill('REP-1900-99999'); await page.locator('[data-report-lookup] button').click();
    await page.waitForFunction(() => document.querySelector('[data-lookup-error]').textContent.includes('No encontramos'));
    check('Número inexistente: sin éxito falso', (await page.locator('[data-lookup-error]').innerText()).includes('No encontramos'));
    if (ticket) {
        await page.locator('[data-report-lookup] input').fill(ticket);
        await Promise.all([page.waitForURL((u) => u.pathname.endsWith(`/reportes/${ticket}`)), page.locator('[data-report-lookup] button').click()]);
        check('Consulta real sin registro', await page.getByRole('heading', { name: 'Así va tu reporte' }).isVisible());
    }
    await go(page, '/reportes/crear');
    check('Solo una tarea visible', await page.locator('[data-report-step]:visible').count() === 1);
    await next(page, 0);
    check('Categoría obligatoria y foco accesible', await page.locator('#category_id-error').isVisible() && await page.locator('[data-category]').first().evaluate((el) => el === document.activeElement));
    await page.locator('[data-category]').first().check(); await next(page, 0);
    await next(page, 1);
    check('Ubicación pendiente explica cómo continuar sin imponer GPS', await page.locator('#address-search').evaluate((el) => el === document.activeElement) && (await page.locator('[data-location-status]').innerText()).includes('Falta elegir el lugar'));
    await page.evaluate(() => Object.defineProperty(navigator, 'geolocation', { configurable: true, value: { getCurrentPosition: (_ok, fail) => fail({ code: 1 }) } }));
    await page.locator('[data-geolocate]').click();
    check('GPS rechazado: alternativa de dirección', (await page.locator('[data-location-status]').innerText()).includes('Puedes escribir la dirección'));
    check('GPS rechazado no inventa coordenadas', await page.locator('#latitude').inputValue() === '' && await page.locator('#longitude').inputValue() === '');
    await page.route('https://nominatim.openstreetmap.org/search?**', (route) => route.fulfill({ json: [{ lat: '3.0095', lon: '-76.4849', display_name: 'Parque de prueba, Santander de Quilichao' }] }));
    await page.locator('#address-search').fill('Parque de prueba, Santander de Quilichao');
    await page.locator('[data-find-address]').click();
    await page.locator('[data-address-results] button').waitFor();
    check('Dirección requiere confirmación explícita', await page.locator('#latitude').inputValue() === '');
    await page.locator('[data-address-results] button').click();
    check('Dirección elegida completa coordenadas reales del resultado', await page.locator('#latitude').inputValue() === '3.009500' && await page.locator('#longitude').inputValue() === '-76.484900');
    await page.locator('[data-map-section] summary').click();
    await page.waitForFunction(() => document.querySelector('#map').classList.contains('leaflet-container'));
    await page.locator('#map').focus(); await page.keyboard.press('ArrowRight'); await page.locator('[data-use-map-center]').click();
    check('Mapa utilizable sin arrastrar', (await page.locator('[data-location-status]').innerText()).includes('centro del mapa'));
    await next(page, 1);
    await page.locator('#title').fill('Hueco frente al parque'); await page.locator('#description').fill('Hay un hueco grande que dificulta el paso por el parque.');
    await page.locator('[data-photo-input]').setInputFiles(photo);
    check('Foto con vista previa y opción de cambio', await page.locator('[data-photo-preview]').isVisible() && (await page.locator('[data-photo-label]').innerText()) === 'Cambiar foto');
    await page.locator('[data-report-step="2"] [data-step-previous]').click();
    await page.locator('[data-report-step="1"] [data-step-previous]').click();
    check('Anterior conserva categoría', await page.locator('[data-category]').first().isChecked());
    await next(page, 0); await next(page, 1);
    check('Anterior conserva detalles y archivo', await page.locator('#title').inputValue() === 'Hueco frente al parque' && await page.locator('[data-photo-input]').evaluate((el) => el.files.length === 1));
    await page.locator('[data-photo-input]').setInputFiles({ name: 'archivo.txt', mimeType: 'text/plain', buffer: Buffer.from('No es una foto') });
    check('Foto inválida: mensaje claro', await page.locator('#damage_photo-error').isVisible());
    await page.locator('[data-photo-input]').setInputFiles(photo);
    await page.evaluate(() => document.querySelector('#report-form').addEventListener('submit', (event) => {
        event.preventDefault();
        window.testSubmission = [...new FormData(event.currentTarget)].map(([key, value]) => [key, value instanceof File ? value.name : value]);
    }));
    await page.locator('[data-submit]').click();
    check('Envío conserva el contrato multipart existente', await page.evaluate(() => ['category_id', 'latitude', 'longitude', 'title', 'description', 'damage_photo', '_token'].every((key) => window.testSubmission?.some(([name, value]) => name === key && value))));
    check('Sin confirmación inventada por JavaScript', !(await page.locator('body').innerText()).includes('¡Tu reporte fue recibido!'));
    // The browser deliberately prevents this POST. Real creation is covered by the SQLite PHP test.

    await go(page, '/'); await page.locator('[data-text-size]').click();
    check('Aumentar texto funciona', await page.locator('body').evaluate((el) => el.classList.contains('citizen-large-text')) && await page.locator('[data-report-lookup] input').evaluate((el) => getComputedStyle(el).fontSize === '20px'));
    await go(page, '/reportes/crear');
    check('Preferencia de texto se conserva', await page.locator('body').evaluate((el) => el.classList.contains('citizen-large-text')) && await page.locator('#title').evaluate((el) => getComputedStyle(el).fontSize === '20px'));
    await page.setViewportSize({ width: 390, height: 844 }); await inspect(page, 'paso-1-texto-grande', 390, 1);
    await page.setViewportSize({ width: 1366, height: 900 }); await go(page, '/'); await inspect(page, 'inicio-texto-grande', 1366, 1);
    await page.locator('[data-text-size]').click();
    if (ticket) {
        await go(page, `/reportes/exito/${ticket}`);
        await page.locator('[data-copy-ticket]').click();
        check('Copia el número real', (await page.evaluate(() => navigator.clipboard.readText())) === ticket);
        await page.evaluate(() => { window.print = () => { window.testPrint = true; }; });
        await page.locator('[data-print-receipt]').click(); check('Imprimir usa el navegador', await page.evaluate(() => window.testPrint));
        await page.emulateMedia({ media: 'print' });
        check('Comprobante imprimible sin navegación', !(await page.locator('.app-header').isVisible()) && await page.locator('#ticket-code').isVisible());
        await page.emulateMedia({ media: 'screen' });
    }

    for (const zoom of [1, 2]) {
        const responsiveContext = await browser.newContext({ deviceScaleFactor: zoom });
        const responsive = await responsiveContext.newPage();
        responsive.on('pageerror', (error) => errors.push({ url: responsive.url(), message: error.message }));
        const widths = process.env.VIGIA_WIDTHS ? process.env.VIGIA_WIDTHS.split(',').map(Number) : [320, 390, 768, 1024, 1366, 1440, 1920];
        for (const width of widths) {
            await responsive.setViewportSize({ width: Math.round(width / zoom), height: Math.round(900 / zoom) });
            await go(responsive, '/'); await inspect(responsive, 'inicio', width, zoom);
            await go(responsive, '/reportes/crear'); await inspect(responsive, 'paso-1', width, zoom);
            await responsive.locator('[data-category]').first().check(); await next(responsive, 0); await inspect(responsive, 'paso-2', width, zoom);
            await coordinates(responsive); await next(responsive, 1); await inspect(responsive, 'paso-3', width, zoom);
            for (const [name, url] of [['no-encontrado', '/reportes/REP-1900-99999'], ...(ticket ? [['detalle', `/reportes/${ticket}`], ['comprobante', `/reportes/exito/${ticket}`]] : [])]) {
                await go(responsive, url); await inspect(responsive, name, width, zoom);
            }
        }
        await responsiveContext.close();
    }

    await page.setViewportSize({ width: 390, height: 844 });
    await go(page, '/'); await axe(page, 'inicio');
    await page.locator('.citizen-extras > summary').click(); await axe(page, 'información adicional');
    await go(page, '/reportes/crear'); await axe(page, 'paso 1');
    await page.locator('[data-category]').first().check(); await next(page, 0); await axe(page, 'paso 2');
    await coordinates(page); await next(page, 1); await axe(page, 'paso 3');
    for (const [name, url] of [['404', '/reportes/REP-1900-99999'], ...(ticket ? [['detalle', `/reportes/${ticket}`], ['comprobante', `/reportes/exito/${ticket}`]] : [])]) { await go(page, url); await axe(page, name); }
    const noJs = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
    const fallback = await noJs.newPage(); await go(fallback, '/reportes/crear');
    check('Sin JavaScript no se ocultan tareas ni campos obligatorios', await fallback.locator('[data-report-step]:visible').count() === 3 && await fallback.locator('#latitude').isVisible() && await fallback.locator('[data-submit]').isVisible());
    if (ticket) { await go(fallback, `/?radicado=${ticket}#consultar`); check('Consulta sin JavaScript tiene enlace al reporte', await fallback.getByRole('link', { name: 'Abrir mi reporte' }).isVisible()); }
    await noJs.close();
    const touchContext = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
    const touch = await touchContext.newPage();
    await go(touch, '/reportes/crear');
    await touch.locator('[data-category]').first().check(); await next(touch, 0); await coordinates(touch); await next(touch, 1);
    check('Pantalla táctil ofrece cámara además de archivos', await touch.locator('[data-photo-camera]').isVisible());
    const camera = touch.waitForEvent('filechooser'); await touch.locator('[data-photo-camera]').click();
    const cameraChooser = await camera;
    check('Cámara solicita captura trasera al navegador', await touch.locator('[data-photo-input]').getAttribute('capture') === 'environment');
    await cameraChooser.setFiles(photo);
    const gallery = touch.waitForEvent('filechooser'); await touch.locator('[data-photo-label]').click();
    const galleryChooser = await gallery;
    check('Cambiar foto conserva acceso a la galería', await touch.locator('[data-photo-input]').getAttribute('capture') === null);
    await galleryChooser.setFiles(photo);
    await touchContext.close();
} finally {
    await fs.writeFile(path.join(artifacts, 'results.json'), JSON.stringify({ checks, layouts, errors, accessibility }, null, 2));
    const failed = checks.filter((item) => !item.ok);
    const overflow = layouts.filter((item) => item.scroll > item.width + 1);
    const violations = accessibility.filter((item) => item.violations.length);
    console.log(JSON.stringify({ checks: checks.length, layouts: layouts.length, failed, overflow, errors, accessibility: violations, testedAccessibility: accessibility.length, artifacts }, null, 2));
    await browser.close();
    if (failed.length || overflow.length || errors.length || violations.length) process.exitCode = 1;
}
