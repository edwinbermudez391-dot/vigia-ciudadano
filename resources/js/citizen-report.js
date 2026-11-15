import { prepareReportPhoto } from './report-photo';

export function initCitizenReport(form) {
    if (!form) return;
    const steps = [...form.querySelectorAll('[data-report-step]')];
    const progress = document.querySelector('[data-report-progress]');
    const stepStatus = document.querySelector('[data-step-status]');
    const latInput = form.querySelector('[data-lat-input]');
    const lngInput = form.querySelector('[data-lng-input]');
    const locationStatus = form.querySelector('[data-location-status]');
    const photoInput = form.querySelector('[data-photo-input]');
    let currentStep = 0;
    let map = null;
    let marker = null;
    let photoUrl = null;
    let photoProblem = '';
    let preparingPhoto = false;
    let photoVersion = 0;
    let submitting = false;
    let nearbyController = null;
    let nearbyTimer = null;
    let pointVersion = 0;
    let confirmedVersion = -1;
    let locationLabel = form.querySelector('[data-selected-place]').value;
    let choosingLocation = false;
    const confirmation = form.querySelector('[data-location-confirmation]');
    const confirmLocation = form.querySelector('[data-confirm-location]');

    function revealAncestors(element) {
        let parent = element.parentElement;
        while (parent && parent !== form) {
            if (parent.tagName === 'DETAILS') parent.open = true;
            parent = parent.parentElement;
        }
    }
    function showStep(index, focus = true) {
        if (index === 2) updateReview();
        currentStep = index;
        steps.forEach((step, i) => { step.hidden = i !== index; });
        [...progress.children].forEach((item, i) => {
            item.dataset.state = i < index ? 'complete' : i === index ? 'current' : 'pending';
            item.querySelector('span').textContent = i < index ? '✓' : String(i + 1);
            if (i === index) item.setAttribute('aria-current', 'step');
            else item.removeAttribute('aria-current');
        });
        stepStatus.textContent = `Paso ${index + 1} de ${steps.length} · ${progress.children[index].querySelector('strong').textContent}`;
        stepStatus.style.setProperty('--step-progress', `${((index + 1) / steps.length) * 100}%`);
        if (index === 1 && (latInput.value || choosingLocation)) requestAnimationFrame(initMap);
        if (focus) steps[index].querySelector('h2').focus();
    }
    function updateReview() {
        const selected = form.querySelector('[data-category]:checked');
        form.querySelector('[data-review-category]').textContent = selected?.closest('label').querySelector('strong')?.textContent || 'Sin elegir';
        form.querySelector('[data-review-description]').textContent = form.querySelector('#description').value;
        form.querySelector('[data-review-location]').textContent = locationLabel;
        const image = form.querySelector('[data-review-photo]');
        image.hidden = !photoUrl || Boolean(photoProblem);
        if (!image.hidden) image.src = photoUrl;
        else image.removeAttribute('src');
    }
    function errorFor(field, message) {
        const error = form.querySelector(`[data-field-error="${field.name}"]`);
        if (error) { error.textContent = message; error.hidden = !message; }
        const controls = field.type === 'radio' ? form.querySelectorAll('input[name=category_id]') : [field];
        controls.forEach((control) => control.setAttribute('aria-invalid', String(Boolean(message))));
    }
    function validationMessage(field) {
        if (field === photoInput && preparingPhoto) return 'Estamos preparando la foto. Espera un momento.';
        if (field === photoInput && photoProblem) return photoProblem;
        if (['title', 'description'].includes(field.name) && field.value.trim().length < field.minLength) {
            return field.value.trim() ? `Escribe al menos ${field.minLength} caracteres.` : (field.name === 'title' ? 'Escribe el problema en pocas palabras.' : 'Cuéntanos qué pasó antes de enviar.');
        }
        if (field.validity.valid) return '';
        if (field.name === 'category_id') return 'Elige el tipo de problema antes de continuar.';
        if (['latitude', 'longitude'].includes(field.name)) return 'Elige un lugar válido usando la dirección, el mapa o las coordenadas.';
        if (field.name === 'damage_photo') return 'Agrega una foto del problema antes de enviar.';
        if (field.validity.typeMismatch && field.type === 'email') return 'Revisa el correo. Debe tener un formato como nombre@correo.com.';
        if (field.validity.valueMissing) return field.name === 'title' ? 'Escribe el problema en pocas palabras.' : 'Cuéntanos qué pasó antes de enviar.';
        if (field.validity.tooShort) return `Escribe al menos ${field.minLength} caracteres.`;
        if (field.validity.tooLong) return `Usa como máximo ${field.maxLength} caracteres.`;
        return 'Revisa este dato antes de continuar.';
    }
    function validateStep(index) {
        if (index === 1 && (!latInput.value || !lngInput.value || !validPoint(Number(latInput.value), Number(lngInput.value)))) {
            showStep(1, false);
            locationStatus.hidden = false;
            locationStatus.textContent = 'Toca «Usar mi ubicación» para obtener la posición de tu dispositivo.';
            form.querySelector('[data-geolocate]').focus();
            return false;
        }
        if (index === 0 && !form.querySelector('[data-category]')) {
            showStep(0, false);
            form.querySelector('#category-options').focus();
            return false;
        }
        let firstInvalid = null;
        const seen = new Set();
        steps[index].querySelectorAll('input[name], textarea[name]').forEach((field) => {
            if (field.type === 'hidden' || field.disabled || seen.has(field.name)) return;
            seen.add(field.name);
            const message = validationMessage(field);
            errorFor(field, message);
            if (message && !firstInvalid) firstInvalid = field;
        });
        if (!firstInvalid && index === 1 && (choosingLocation || confirmedVersion !== pointVersion)) {
            showStep(1, false);
            locationStatus.textContent = choosingLocation ? 'Vuelve a intentar obtener la ubicación del dispositivo.' : 'Comprueba el punto en el mapa y confirma si el problema está ahí.';
            if (choosingLocation) form.querySelector('[data-geolocate]').focus();
            else confirmLocation.focus();
            return false;
        }
        if (!firstInvalid) return true;
        showStep(index, false);
        revealAncestors(firstInvalid);
        firstInvalid.focus();
        return false;
    }

    form.noValidate = true;
    const firstContinue = steps[0].querySelector('[data-step-next]');
    firstContinue.textContent = 'Continuar';
    form.querySelectorAll('[data-edit-step]').forEach((button) => {
        button.hidden = false;
        button.addEventListener('click', () => showStep(Number(button.dataset.editStep)));
    });
    confirmLocation.addEventListener('click', () => {
        if (choosingLocation || !latInput.value || !lngInput.value || !validPoint(Number(latInput.value), Number(lngInput.value))) return;
        confirmedVersion = pointVersion;
        locationStatus.textContent = 'Lugar confirmado. Puedes continuar.';
        confirmation.hidden = true;
        steps[1].querySelector('[data-step-next]').focus();
    });
    form.querySelector('[data-change-location]').addEventListener('click', () => {
        form.querySelector('[data-geolocate]').click();
    });
    progress.hidden = false;
    stepStatus.hidden = false;
    form.querySelectorAll('[data-step-actions], [data-step-previous], [data-geolocate]').forEach((element) => { element.hidden = false; });
    locationStatus.hidden = true;
    steps[1].querySelector('[data-step-next]').hidden = !latInput.value || !lngInput.value;
    form.querySelectorAll('[data-step-next]').forEach((button) => button.addEventListener('click', () => {
        if (validateStep(currentStep)) showStep(currentStep + 1);
    }));
    form.querySelectorAll('[data-step-previous]').forEach((button) => button.addEventListener('click', () => showStep(currentStep - 1)));
    form.addEventListener('input', (event) => {
        if (event.target.name) errorFor(event.target, '');
    });
    form.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.target.tagName !== 'INPUT' || event.target.type === 'file') return;
        event.preventDefault();
        if (currentStep < 2 && validateStep(currentStep)) showStep(currentStep + 1);
    });

    function updateCategory() {
        const selected = form.querySelector('[data-category]:checked');
        if (!selected) return;
        errorFor(selected, '');
        scheduleNearby();
    }
    form.querySelectorAll('[data-category]').forEach((radio) => radio.addEventListener('change', updateCategory));

    function validPoint(lat, lng) {
        return Number.isFinite(lat) && lat >= -90 && lat <= 90 && Number.isFinite(lng) && lng >= -180 && lng <= 180;
    }
    function setPoint(lat, lng, message, pan = true, label = 'Punto elegido en el mapa') {
        if (!validPoint(lat, lng)) return;
        pointVersion++;
        choosingLocation = false;
        confirmedVersion = -1;
        locationLabel = label;
        form.querySelector('[data-selected-place]').value = label.slice(0, 255);
        confirmation.hidden = false;
        locationStatus.hidden = false;
        steps[1].querySelector('[data-step-next]').hidden = false;
        confirmLocation.hidden = false;
        form.querySelector('[data-confirm-title]').textContent = '¿El problema está aquí?';
        form.querySelector('[data-location-label]').textContent = label;
        confirmLocation.textContent = 'Sí, este es el lugar';
        const mapSection = form.querySelector('[data-map-section]');
        mapSection.hidden = false;
        mapSection.open = true;
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
        errorFor(latInput, ''); errorFor(lngInput, '');
        locationStatus.textContent = message;
        locationStatus.classList.remove('citizen-error');
        if (map) {
            if (!marker) {
                marker = window.L.marker([lat, lng], { draggable: false, title: 'Lugar del problema', alt: 'Lugar elegido para el reporte' }).addTo(map);
            } else marker.setLatLng([lat, lng]);
            if (pan) map.setView([lat, lng], 17);
        }
        scheduleNearby();
        if (map) requestAnimationFrame(() => map.invalidateSize());
    }
    function initMap() {
        if (map) { map.invalidateSize(); return; }
        const section = form.querySelector('[data-map-section]');
        if (!window.L) {
            locationStatus.hidden = false;
            locationStatus.textContent = 'El mapa no está disponible. La ubicación del dispositivo se conserva; comprueba tu conexión para verlo.';
            return;
        }
        map = window.L.map('map', { scrollWheelZoom: false }).setView([3.0095, -76.4849], 15);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
        if (latInput.value !== '' && lngInput.value !== '' && validPoint(Number(latInput.value), Number(lngInput.value))) {
            const point = [Number(latInput.value), Number(lngInput.value)];
            marker = window.L.marker(point, { draggable: false, title: 'Lugar del problema', alt: 'Lugar elegido para el reporte' }).addTo(map);
            map.setView(point, 17);
            if (!locationLabel) setPoint(...point, 'Comprueba el lugar que elegiste.', true, 'Lugar conservado del formulario');
        }
        requestAnimationFrame(() => map.invalidateSize());
    }
    form.querySelector('[data-map-section]').addEventListener('toggle', (event) => {
        if (event.currentTarget.open) requestAnimationFrame(initMap);
    });
    [latInput, lngInput].forEach((input) => input.addEventListener('input', () => {
        const lat = Number(latInput.value), lng = Number(lngInput.value);
        if (latInput.value !== '' && lngInput.value !== '' && validPoint(lat, lng)) setPoint(lat, lng, 'Coordenadas guardadas. Comprueba que corresponden al problema.', false);
        else { pointVersion++; confirmation.hidden = true; nearbyController?.abort(); form.querySelector('[data-duplicate-banner]').hidden = true; locationStatus.textContent = 'Falta completar una ubicación válida.'; }
    }));

    const geoButton = form.querySelector('[data-geolocate]');
    geoButton.addEventListener('click', () => {
        locationStatus.hidden = false;
        if (!window.isSecureContext) {
            locationStatus.textContent = 'Para usar el GPS, abre la página desde el enlace HTTPS de Cloudflare.';
            return;
        }
        if (!navigator.geolocation) {
            locationStatus.textContent = 'Este navegador no permite usar el GPS. Abre el enlace en Chrome o Safari.';
            return;
        }
        pointVersion++;
        const requestedAt = pointVersion;
        confirmation.hidden = true;
        choosingLocation = true;
        geoButton.disabled = true;
        geoButton.querySelector('span').textContent = 'Buscando tu ubicación…';
        steps[1].querySelector('[data-step-next]').hidden = true;
        locationStatus.textContent = 'Buscando tu ubicación… Si el celular pide permiso, toca «Permitir».';
        const success = (position) => {
            geoButton.disabled = false;
            geoButton.querySelector('span').textContent = 'Volver a usar mi ubicación';
            if (requestedAt !== pointVersion) return;
            const { latitude, longitude } = position.coords;
            if (!validPoint(latitude, longitude)) { failure({ code: 2 }, false); return; }
            setPoint(latitude, longitude, 'Comprueba el punto marcado en el mapa.', true, 'Tu ubicación');
            initMap();
            form.querySelector('[data-map-section]').scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
        const failure = (error, retry = true) => {
            if (requestedAt !== pointVersion) { geoButton.disabled = false; return; }
            if (error.code !== 1 && retry) {
                locationStatus.textContent = 'El GPS está tardando. Estamos intentando otra forma de obtener tu ubicación…';
                navigator.geolocation.getCurrentPosition(success, error => failure(error, false), {
                    enableHighAccuracy: false, timeout: 15000, maximumAge: 60000,
                });
                return;
            }
            geoButton.disabled = false;
            geoButton.querySelector('span').textContent = 'Intentar de nuevo';
            locationStatus.textContent = error.code === 1
                ? 'El permiso de ubicación está bloqueado. En los permisos de este sitio, permite la ubicación y vuelve a tocar «Usar mi ubicación».'
                : error.code === 3
                    ? 'El celular no respondió a tiempo. Activa la ubicación del teléfono y toca de nuevo «Usar mi ubicación».'
                    : 'No hay señal de ubicación disponible. Comprueba que la ubicación del teléfono esté activada y vuelve a intentarlo.';
        };
        navigator.geolocation.getCurrentPosition(success, failure, { enableHighAccuracy: true, timeout: 20000, maximumAge: 15000 });
    });

    const preview = form.querySelector('[data-photo-preview]');
    // Keep the gallery available; capture is requested only for the camera action.
    photoInput.closest('label').addEventListener('click', (event) => {
        if (event.target !== photoInput) photoInput.removeAttribute('capture');
    });
    const cameraButton = form.querySelector('[data-photo-camera]');
    cameraButton.hidden = !window.matchMedia('(pointer: coarse)').matches;
    cameraButton.addEventListener('click', () => {
        photoInput.setAttribute('capture', 'environment');
        photoInput.click();
    });
    photoInput.addEventListener('change', async () => {
        const version = ++photoVersion;
        photoInput.removeAttribute('capture');
        let file = photoInput.files?.[0];
        firstContinue.textContent = 'Continuar';
        if (photoUrl) URL.revokeObjectURL(photoUrl);
        preview.hidden = true; preview.removeAttribute('src'); photoProblem = '';
        form.querySelector('[data-photo-label]').textContent = file ? 'Cambiar foto' : 'Agregar foto';
        errorFor(photoInput, '');
        form.querySelector('[data-remove-photo]').hidden = !file;
        preparingPhoto = Boolean(file);
        if (!file) { form.querySelector('[data-photo-status]').textContent = ''; return; }
        form.querySelector('[data-photo-status]').textContent = 'Preparando la foto…';
        try {
            file = await prepareReportPhoto(file);
            if (version !== photoVersion) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            photoInput.files = transfer.files;
            form.querySelector('[data-photo-status]').textContent = '';
        } catch (error) {
            if (version !== photoVersion) return;
            photoProblem = error.message || 'No pudimos preparar la foto. Elige otra imagen.';
            form.querySelector('[data-photo-status]').textContent = '';
            errorFor(photoInput, photoProblem);
            return;
        } finally {
            if (version === photoVersion) preparingPhoto = false;
        }
        photoUrl = URL.createObjectURL(file);
        preview.onload = () => {
            if (preview.naturalWidth > 4000 || preview.naturalHeight > 4000) photoProblem = 'La foto supera los 4000 × 4000 píxeles. Elige una más pequeña.';
            errorFor(photoInput, photoProblem);
        };
        preview.onerror = () => { photoProblem = 'No pudimos abrir esa foto. Elige otra imagen.'; preview.hidden = true; errorFor(photoInput, photoProblem); };
        preview.src = photoUrl; preview.hidden = false;
        errorFor(photoInput, '');
    });
    form.querySelector('[data-remove-photo]').addEventListener('click', () => {
        photoInput.value = '';
        photoInput.dispatchEvent(new Event('change', { bubbles: true }));
    });
    const description = form.querySelector('#description');
    const updateCounter = () => { form.querySelector('[data-counter-output]').textContent = `${description.value.length}/1000`; };
    description.addEventListener('input', updateCounter); updateCounter();

    function scheduleNearby() {
        clearTimeout(nearbyTimer);
        nearbyController?.abort();
        form.querySelector('[data-duplicate-banner]').hidden = true;
        if (form.dataset.canSubscribe !== 'true') return; // Preserve API permissions; no anonymous auth errors.
        nearbyTimer = setTimeout(checkNearby, 600);
    }
    async function checkNearby() {
        const category = form.querySelector('[data-category]:checked');
        const lat = Number(latInput.value), lng = Number(lngInput.value);
        if (!category || !latInput.value || !lngInput.value || !validPoint(lat, lng)) return;
        nearbyController = new AbortController();
        const controller = nearbyController;
        const url = new URL(form.dataset.nearbyUrl);
        url.search = new URLSearchParams({ category_id: category.value, latitude: lat, longitude: lng, radius_meters: '20' });
        try {
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                const warning = form.querySelector('[data-submit-error]');
                warning.textContent = 'No pudimos comprobar si hay reportes cercanos. Puedes enviar tu reporte.';
                warning.hidden = false;
                return;
            }
            const data = await response.json();
            if (controller.signal.aborted) return;
            const reports = Array.isArray(data.data) ? data.data : [];
            form.querySelector('[data-duplicate-count]').textContent = reports.length;
            form.querySelector('[data-duplicate-banner]').hidden = reports.length === 0;
            const list = form.querySelector('[data-duplicates-list]'); list.replaceChildren();
            reports.forEach((report) => {
                const li = document.createElement('li');
                const title = document.createElement('p'); title.textContent = `${report.title} · ${report.ticket_code}`;
                const link = document.createElement('a'); link.className = 'btn-secondary'; link.href = `${form.dataset.detailUrl}/${encodeURIComponent(report.ticket_code)}`; link.textContent = 'Consultar este reporte';
                const follow = document.createElement('button'); follow.type = 'button'; follow.className = 'btn-secondary'; follow.textContent = 'Recibir actualizaciones';
                follow.addEventListener('click', async () => {
                    follow.disabled = true;
                    try {
                        const response = await fetch(`${form.dataset.subscribeUrl}/${encodeURIComponent(report.id)}/subscribe`, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value } });
                        if (!response.ok) throw new Error('subscription-unavailable');
                        follow.textContent = 'Ya estás siguiendo este reporte';
                    } catch { follow.disabled = false; follow.textContent = 'No pudimos guardar. Intentar otra vez'; }
                });
                li.append(title, link, follow); list.append(li);
            });
        } catch (error) {
            if (error.name !== 'AbortError') locationStatus.textContent = 'La ubicación está guardada. No pudimos comprobar reportes cercanos, pero puedes continuar.';
        }
    }
    form.querySelector('[data-check-duplicates]').addEventListener('click', () => { form.querySelector('[data-duplicates-list]').hidden = !form.querySelector('[data-duplicates-list]').hidden; });

    form.addEventListener('submit', (event) => {
        if (submitting) { event.preventDefault(); return; }
        if (navigator.onLine === false) {
            event.preventDefault();
            const warning = form.querySelector('[data-submit-error]');
            warning.textContent = 'No se ha enviado. Revisa tu conexión y vuelve a intentarlo. Tus datos siguen aquí.';
            warning.hidden = false;
            warning.scrollIntoView({ block: 'center' });
            return;
        }
        for (let index = 0; index < steps.length; index++) {
            if (!validateStep(index)) { event.preventDefault(); return; }
        }
        if (currentStep !== steps.length - 1) { event.preventDefault(); showStep(steps.length - 1); return; }
        submitting = true;
        const submit = form.querySelector('[data-submit]');
        submit.disabled = true; submit.textContent = 'Enviando reporte…';
        // Native multipart POST: only the server may create a report and show success.
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        const submit = form.querySelector('[data-submit]'); submit.disabled = false; submit.textContent = 'Enviar reporte';
    });
    updateCategory();
    const serverError = form.querySelector('[data-field-error]:not([hidden])');
    const initialStep = serverError ? steps.findIndex((step) => step.contains(serverError)) : 0;
    showStep(Math.max(0, initialStep), false);
    const summary = document.querySelector('[data-citizen-errors]');
    if (summary) {
        summary.focus();
        summary.querySelectorAll('a[href^="#"]').forEach((link) => link.addEventListener('click', (event) => {
            const target = document.getElementById(link.hash.slice(1));
            if (!target) return;
            event.preventDefault();
            const index = steps.findIndex((step) => step.contains(target));
            if (index >= 0) showStep(index, false);
            revealAncestors(target); target.focus();
        }));
    }
}
