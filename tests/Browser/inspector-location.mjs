import { createRequire } from 'node:module';
import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';
const require = createRequire(process.env.VIGIA_BROWSER_TOOLING || import.meta.url);
const { chromium } = require('playwright');
const base = process.env.VIGIA_BASE_URL || 'http://127.0.0.1:8000';
const manifest = JSON.parse(await readFile('public/build/manifest.json', 'utf8'));
const browser = await chromium.launch({ channel: 'chrome', headless: true });
try {
    const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/__inspector-location-test', route => route.fulfill({ contentType: 'text/html', body: `
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="/build/${manifest['resources/css/app.css'].file}">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <form id="audit-form"><button type="button" data-capture-gps>Capturar GPS</button>
        <p data-gps-status></p><input type="hidden" data-lat><input type="hidden" data-lng>
        <div class="inspector-location-map" data-inspector-map hidden></div></form>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script type="module" src="/build/${manifest['resources/js/app.js'].file}"></script>` }));
    await page.goto(`${base}/__inspector-location-test`);
    await page.locator('[data-gps-status][role=status]').waitFor({ state: 'attached' });
    await page.evaluate(() => Object.defineProperty(navigator, 'geolocation', { configurable: true, value: {
        getCurrentPosition: ok => ok({ coords: { latitude: 3.0095, longitude: -76.4849, accuracy: 20 } }),
    } }));
    await page.locator('[data-capture-gps]').click();
    await page.locator('.leaflet-marker-icon').waitFor();
    assert.equal(await page.locator('[data-lat]').inputValue(), '3.0095000');
    assert.equal(await page.locator('[data-lng]').inputValue(), '-76.4849000');
    assert.equal(await page.locator('[data-lat]').isVisible(), false);
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    await page.evaluate(() => Object.defineProperty(navigator, 'geolocation', { configurable: true, value: {
        getCurrentPosition: ok => ok({ coords: { latitude: 4.6115, longitude: -74.0832, accuracy: 100000 } }),
    } }));
    await page.locator('[data-capture-gps]').click();
    assert.match(await page.locator('[data-gps-status]').innerText(), /poco precisa/);
    assert.equal(await page.locator('[data-lat]').inputValue(), '4.6115000');
    assert.equal(await page.locator('.leaflet-marker-icon').count(), 1);
    assert.deepEqual(errors, []);
    console.log('PASS: inspector GPS map, marker, hidden coordinates, recapture and accuracy warning.');
} finally { await browser.close(); }
