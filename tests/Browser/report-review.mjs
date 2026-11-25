import { createRequire } from 'node:module';
import assert from 'node:assert/strict';

const require = createRequire(process.env.VIGIA_BROWSER_TOOLING || import.meta.url);
const { chromium } = require('playwright');
const browser = await chromium.launch({ channel: 'chrome', headless: true });
try {
    const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(`${process.env.VIGIA_BASE_URL || 'http://127.0.0.1:8000'}/reportes/crear`);
    await page.locator('[data-step-status]').waitFor({ state: 'visible' });
    const next = step => page.locator(`[data-report-step="${step}"] [data-step-next]`).click();
    await next(0);
    assert.equal(await page.locator('#category_id-error').isVisible(), true);
    await page.locator('[data-category]').first().check();
    await page.locator('#description').fill('Hay un hueco grande que dificulta el paso por el parque.');
    await page.evaluate(async () => {
        const canvas = document.createElement('canvas');
        canvas.width = 6000; canvas.height = 4000;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#1264ab'; ctx.fillRect(0, 0, canvas.width, canvas.height);
        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
        const file = new File([blob, new Uint8Array(6 * 1024 * 1024)], 'foto-grande.png', { type: 'image/png' });
        const transfer = new DataTransfer(); transfer.items.add(file);
        const input = document.querySelector('[data-photo-input]');
        input.files = transfer.files; input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await page.waitForFunction(() => document.querySelector('[data-photo-input]').files[0]?.name === 'foto-reporte.jpg');
    const prepared = await page.locator('[data-photo-input]').evaluate(async input => {
        const file = input.files[0];
        const image = await createImageBitmap(file);
        const result = { bytes: file.size, width: image.width, height: image.height, type: file.type };
        image.close(); return result;
    });
    assert.ok(prepared.bytes <= 1024 * 1024);
    assert.ok(prepared.width <= 1920 && prepared.height <= 1920);
    assert.equal(prepared.type, 'image/jpeg');
    await page.locator('[data-remove-photo]').click();
    assert.equal(await page.locator('[data-photo-input]').evaluate(input => input.files.length), 0);
    await next(0);
    assert.equal(await page.locator('[data-report-step="0"]').isVisible(), true);
    assert.equal(await page.locator('#damage_photo-error').isVisible(), true);
    await page.locator('[data-photo-input]').setInputFiles({ name: 'foto.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64') });
    await page.waitForFunction(() => document.querySelector('[data-photo-input]').files[0]?.name === 'foto-reporte.jpg');
    await next(0);
    assert.equal(await page.locator('[data-address-search]').isVisible(), false);
    assert.equal(await page.locator('[data-map-section]').isVisible(), false);
    assert.equal(await page.locator('[data-location-options]').count(), 0);
    await page.evaluate(() => Object.defineProperty(navigator, 'geolocation', { configurable: true, value: {
        getCurrentPosition: ok => ok({ coords: { latitude: 3.0095, longitude: -76.4849, accuracy: 20 } }),
    } }));
    await page.locator('[data-geolocate]').click();
    await next(1);
    assert.equal(await page.locator('[data-report-step="1"]').isVisible(), true);
    await page.locator('[data-confirm-location]').click();
    await next(1);
    assert.equal(await page.locator('[data-review-description]').innerText(), 'Hay un hueco grande que dificulta el paso por el parque.');
    assert.equal(await page.locator('[data-review-location]').innerText(), 'Tu ubicación');
    assert.equal(await page.locator('[data-report-step]:visible').count(), 1);
    await page.locator('[data-edit-step="0"]').click();
    assert.equal(await page.locator('#description').inputValue(), 'Hay un hueco grande que dificulta el paso por el parque.');
    await page.locator('#description').fill('Hay un hueco grande frente al parque que necesita reparación.');
    await next(0);
    assert.equal(await page.locator('#latitude').inputValue(), '3.009500');
    await next(1);
    assert.equal(await page.locator('[data-review-description]').innerText(), 'Hay un hueco grande frente al parque que necesita reparación.');
    assert.notEqual(await page.locator('#damage_photo').getAttribute('required'), null);
    await page.locator('[data-edit-step="1"]').click();
    await page.evaluate(() => {
        Object.defineProperty(navigator, 'geolocation', { configurable: true, value: { getCurrentPosition: (_ok, fail) => fail({ code: 1 }) } });
    });
    await page.locator('[data-geolocate]').click();
    assert.equal(await page.locator('[data-address-search]').isVisible(), false);
    assert.match(await page.locator('[data-location-status]').innerText(), /permiso de ubicaci/);
    await page.evaluate(() => {
        let attempts = 0;
        Object.defineProperty(navigator, 'geolocation', { configurable: true, value: {
            getCurrentPosition: (ok, fail, options) => {
                if (!options.enableHighAccuracy || options.maximumAge !== 0) throw new Error('Se requiere una lectura nueva de alta precisión');
                attempts++;
                if (attempts === 1) fail({ code: 3 });
                else ok({ coords: { latitude: 3.011, longitude: -76.481, accuracy: 20 } });
            },
        } });
    });
    await page.locator('[data-geolocate]').click();
    assert.equal(await page.locator('#latitude').inputValue(), '3.011000');
    assert.equal(await page.locator('#longitude').inputValue(), '-76.481000');
    await page.locator('[data-confirm-location]').click();
    await next(1);
    assert.equal(await page.locator('[data-report-step="2"]').isVisible(), true);
    await page.locator('[data-edit-step="1"]').click();
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    assert.deepEqual(errors, []);
    await page.goto(`${process.env.VIGIA_BASE_URL || 'http://127.0.0.1:8000'}/reportes/crear`);
    await page.locator('[data-category]').first().check();
    await page.locator('#description').fill('Hay un hueco grande que dificulta el paso.');
    await page.locator('[data-photo-input]').setInputFiles({ name: 'foto.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64') });
    await page.waitForFunction(() => document.querySelector('[data-photo-input]').files[0]?.name === 'foto-reporte.jpg');
    await next(0);
    await page.evaluate(() => Object.defineProperty(navigator, 'geolocation', { configurable: true, value: {
        getCurrentPosition: ok => ok({ coords: { latitude: 3.011, longitude: -76.481, accuracy: 900 } }),
    } }));
    await page.locator('[data-geolocate]').click();
    await page.getByText('La ubicación es aproximada', { exact: false }).waitFor();
    assert.equal(await page.locator('#latitude').inputValue(), '');
    assert.equal(await page.locator('[data-confirm-location]').isVisible(), false);
    await page.locator('[data-use-map-center]').click();
    await page.getByRole('heading', { name: '¿El problema está aquí?' }).waitFor();
    await page.locator('.leaflet-marker-icon').waitFor();
    assert.equal(await page.locator('[data-map-section]').isVisible(), true);
    assert.equal(await page.locator('[data-map-section]').evaluate(el => el.open), true);
    assert.equal(await page.locator('[data-confirm-location]').isVisible(), true);
    await next(1);
    assert.equal(await page.locator('[data-report-step="1"]').isVisible(), true);
    await page.locator('[data-confirm-location]').click();
    await next(1);
    assert.equal(await page.locator('[data-report-step="2"]').isVisible(), true);
    console.log('PASS: required photo, automatic resizing, report steps, GPS and mobile layout.');
} finally {
    await browser.close();
}
