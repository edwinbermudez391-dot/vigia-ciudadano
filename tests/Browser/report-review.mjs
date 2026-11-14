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
    await page.locator('[data-report-progress]').waitFor({ state: 'visible' });
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
    await page.getByText('Foto lista. Ajustamos el tamaño automáticamente.').waitFor();
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
    await page.locator('[data-coordinates]').evaluate(el => { el.open = true; });
    await page.locator('#latitude').fill('3.0095');
    await page.locator('#longitude').fill('-76.4849');
    await page.locator('#address_reference').fill('Frente al parque');
    await next(1);
    assert.equal(await page.locator('[data-report-step="1"]').isVisible(), true);
    await page.locator('[data-confirm-location]').click();
    await next(1);
    assert.equal(await page.locator('[data-review-description]').innerText(), 'Hay un hueco grande que dificulta el paso por el parque.');
    assert.match(await page.locator('[data-review-location]').innerText(), /Frente al parque/);
    assert.equal(await page.locator('[data-report-step]:visible').count(), 1);
    await page.locator('[data-edit-step="0"]').click();
    assert.equal(await page.locator('#description').inputValue(), 'Hay un hueco grande que dificulta el paso por el parque.');
    await page.locator('#description').fill('Hay un hueco grande frente al parque que necesita reparación.');
    await next(0);
    assert.equal(await page.locator('#address_reference').inputValue(), 'Frente al parque');
    await next(1);
    assert.equal(await page.locator('[data-review-description]').innerText(), 'Hay un hueco grande frente al parque que necesita reparación.');
    assert.equal(await page.locator('#damage_photo').getAttribute('required'), null);
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
            getCurrentPosition: (ok, fail) => {
                attempts++;
                if (attempts === 1) fail({ code: 3 });
                else ok({ coords: { latitude: 3.011, longitude: -76.481, accuracy: 20 } });
            },
        } });
    });
    await page.locator('[data-geolocate]').click();
    assert.equal(await page.locator('#latitude').inputValue(), '3.011000');
    assert.equal(await page.locator('#longitude').inputValue(), '-76.481000');
    await next(1);
    assert.equal(await page.locator('[data-report-step="2"]').isVisible(), true);
    await page.locator('[data-edit-step="1"]').click();
    await page.locator('[data-choose-address]').click();
    await page.route('https://nominatim.openstreetmap.org/search?**', route => route.fulfill({ json: [
        { display_name: 'Parque principal, Santander de Quilichao', lat: '3.01', lon: '-76.48' },
    ] }));
    await page.locator('#address-search').fill('Parque principal, Santander de Quilichao');
    await page.locator('[data-find-address]').click();
    await page.locator('[data-address-results] button').click();
    assert.equal(await page.locator('[data-location-label]').innerText(), 'Parque principal, Santander de Quilichao');
    await next(1);
    assert.equal(await page.locator('[data-report-step="1"]').isVisible(), true);
    await page.locator('[data-confirm-location]').click();
    await next(1);
    assert.match(await page.locator('[data-review-location]').innerText(), /Parque principal, Santander de Quilichao/);
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    assert.deepEqual(errors, []);
    console.log('PASS: validaciÃ³n, tres pasos, resumen, correcciones, foto opcional y ancho mÃ³vil.');
} finally {
    await browser.close();
}
