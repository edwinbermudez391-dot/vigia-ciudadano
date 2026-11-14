export function initCitizenReport(form) {
    if (!form) return;
    const steps = [...form.querySelectorAll('[data-report-step]')];
    const progress = document.querySelector('[data-report-progress]');
    const stepStatus = document.querySelector('[data-step-status]');
    const latInput = form.querySelector('[data-lat-input]');
    const lngInput = form.querySelector('[data-lng-input]');
    const locationStatus = form.querySelector('[data-location-status]');
    const coordinates = form.querySelector('[data-coordinates]');
    const photoInput = form.querySelector('[data-photo-input]');
    let currentStep = 0;
    let map = null;
    let marker = null;
    let photoUrl = null;
    let photoProblem = '';
    let submitting = false;
    let nearbyController = null;
    let nearbyTimer = null;
    let addressController = null;
    let pointVersion = 0;

    function revealAncestors(element) {
        let parent = element.parentElement;
        while (parent && parent !== form) {
            if (parent.tagName === 'DETAILS') parent.open = true;
            parent = parent.parentElement;
        }
    }
    function showStep(index, focus = true) {
        currentStep = index;
        steps.forEach((step, i) => { step.hidden = i !== index; });
        [...progress.children].forEach((item, i) => {
            if (i === index) item.setAttribute('aria-current', 'step');
            else item.removeAttribute('aria-current');
        });
        stepStatus.textContent = `Paso ${index + 1} de ${steps.length}. Tus datos se conservan al usar Anterior.`;
        if (index === 1) requestAnimationFrame(initMap);
        if (focus) steps[index].querySelector('h2').focus();
    }
    function errorFor(field, message) {
        const error = form.querySelector(`[data-field-error="${field.name}"]`);
        if (error) { error.textContent = message; error.hidden = !message; }
        const controls = field.type === 'radio' ? form.querySelectorAll('input[name=category_id]') : [field];
        controls.forEach((control) => control.setAttribute('aria-invalid', String(Boolean(message))));
    }
    function validationMessage(field) {
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
        if (!firstInvalid) return true;
        showStep(index, false);
        if (['latitude', 'longitude'].includes(firstInvalid.name) && !coordinates.open) {
            locationStatus.textContent = 'Falta elegir el lugar. Busca la dirección y elige un resultado, señala el mapa o escribe las coordenadas si las conoces.';
            locationStatus.classList.add('citizen-error');
            const search = form.querySelector('#address-search');
            search.setAttribute('aria-invalid', 'true');
            search.focus();
            return false;
        }
        revealAncestors(firstInvalid);
        firstInvalid.focus();
        return false;
    }

    form.noValidate = true;
    progress.hidden = false;
    stepStatus.hidden = false;
    coordinates.open = Boolean(form.querySelector('#latitude-error:not([hidden]), #longitude-error:not([hidden])'));
    form.querySelectorAll('[data-step-actions], [data-step-previous], [data-geolocate], [data-address-search]').forEach((element) => { element.hidden = false; });
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
        if (event.target.id === 'address-search') form.querySelector('[data-find-address]').click();
        else if (currentStep < 2 && validateStep(currentStep)) showStep(currentStep + 1);
    });

    function updateCategory() {
        const selected = form.querySelector('[data-category]:checked');
        const info = form.querySelector('[data-category-info]');
        info.hidden = !selected;
        if (!selected) return;
        form.querySelector('[data-category-description]').textContent = `${selected.dataset.categoryName}. ${selected.dataset.categoryDescription || ''}`;
        form.querySelector('[data-category-responsible]').textContent = `Responsable: ${selected.dataset.categoryDepartment}.`;
        form.querySelector('[data-category-time]').textContent = `Plazo para organizar e iniciar la atención: ${selected.dataset.categorySla} horas.`;
        errorFor(selected, '');
        scheduleNearby();
    }
    form.querySelectorAll('[data-category]').forEach((radio) => radio.addEventListener('change', updateCategory));

    function validPoint(lat, lng) {
        return Number.isFinite(lat) && lat >= -90 && lat <= 90 && Number.isFinite(lng) && lng >= -180 && lng <= 180;
    }
    function setPoint(lat, lng, message, pan = true) {
        if (!validPoint(lat, lng)) return;
        pointVersion++;
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
        errorFor(latInput, ''); errorFor(lngInput, '');
        locationStatus.textContent = message;
        locationStatus.classList.remove('citizen-error');
        form.querySelector('#address-search').setAttribute('aria-invalid', 'false');
        if (map) {
            if (!marker) {
                marker = window.L.marker([lat, lng], { draggable: true, title: 'Lugar del problema', alt: 'Lugar elegido para el reporte' }).addTo(map);
                marker.on('dragend', () => { const p = marker.getLatLng(); setPoint(p.lat, p.lng, 'Lugar actualizado. Puedes continuar.'); });
            } else marker.setLatLng([lat, lng]);
            if (pan) map.setView([lat, lng], 17);
        }
        scheduleNearby();
    }
    function initMap() {
        if (map) { map.invalidateSize(); return; }
        const section = form.querySelector('[data-map-section]');
        if (!window.L) {
            locationStatus.textContent = 'El mapa no está disponible. Puedes buscar la dirección, usar tu ubicación o escribir las coordenadas.';
            coordinates.open = true;
            return;
        }
        section.hidden = false;
        map = window.L.map('map', { scrollWheelZoom: false }).setView([3.0095, -76.4849], 15);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
        map.on('click', (event) => setPoint(event.latlng.lat, event.latlng.lng, 'Lugar elegido en el mapa. Puedes continuar.', false));
        if (latInput.value !== '' && lngInput.value !== '' && validPoint(Number(latInput.value), Number(lngInput.value))) {
            setPoint(Number(latInput.value), Number(lngInput.value), 'Conservamos la ubicación que elegiste.');
        }
        requestAnimationFrame(() => map.invalidateSize());
    }
    form.querySelector('[data-map-section]').addEventListener('toggle', (event) => {
        if (event.currentTarget.open) requestAnimationFrame(initMap);
    });
    form.querySelector('[data-use-map-center]').addEventListener('click', () => {
        if (!map) return;
        const point = map.getCenter();
        setPoint(point.lat, point.lng, 'Elegiste el centro del mapa. Comprueba que corresponde al problema.', false);
    });
    [latInput, lngInput].forEach((input) => input.addEventListener('input', () => {
        const lat = Number(latInput.value), lng = Number(lngInput.value);
        if (latInput.value !== '' && lngInput.value !== '' && validPoint(lat, lng)) setPoint(lat, lng, 'Coordenadas guardadas. Comprueba que corresponden al problema.', false);
        else { pointVersion++; nearbyController?.abort(); form.querySelector('[data-duplicate-banner]').hidden = true; locationStatus.textContent = 'Falta completar una ubicación válida.'; }
    }));

    const geoButton = form.querySelector('[data-geolocate]');
    geoButton.addEventListener('click', () => {
        if (!navigator.geolocation) { locationStatus.textContent = 'Este navegador no puede obtener tu ubicación. Busca la dirección o señala el lugar en el mapa.'; return; }
        const requestedAt = pointVersion;
        geoButton.disabled = true;
        locationStatus.textContent = 'Buscando tu ubicación. Puedes elegir otro método si lo prefieres.';
        navigator.geolocation.getCurrentPosition((position) => {
            geoButton.disabled = false;
            if (requestedAt !== pointVersion) return;
            setPoint(position.coords.latitude, position.coords.longitude, 'Ubicación obtenida. Comprueba en el mapa que el problema está aquí.');
        }, () => {
            geoButton.disabled = false;
            if (requestedAt === pointVersion) locationStatus.textContent = 'No pudimos acceder a tu ubicación. Puedes escribir la dirección y buscarla o señalar el lugar en el mapa.';
        }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
    });

    const addressButton = form.querySelector('[data-find-address]');
    const addressInput = form.querySelector('#address-search');
    addressButton.addEventListener('click', async () => {
        const query = addressInput.value.trim();
        const results = form.querySelector('[data-address-results]');
        results.replaceChildren();
        if (query.length < 5) { locationStatus.textContent = 'Escribe la dirección y el municipio para buscar el lugar.'; addressInput.focus(); return; }
        addressController?.abort();
        addressController = new AbortController();
        addressButton.disabled = true;
        locationStatus.textContent = 'Buscando la dirección…';
        try {
            const url = new URL('https://nominatim.openstreetmap.org/search');
            url.search = new URLSearchParams({ format: 'jsonv2', q: query, limit: '5', 'accept-language': 'es' });
            const response = await fetch(url, { signal: addressController.signal, credentials: 'omit', headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('address-service-unavailable');
            const data = await response.json();
            const matches = Array.isArray(data) ? data.filter((item) => typeof item.display_name === 'string' && item.lat != null && item.lon != null && item.lat !== '' && item.lon !== '' && validPoint(Number(item.lat), Number(item.lon))) : [];
            locationStatus.textContent = matches.length ? 'Elige la dirección que corresponde al problema. No se elige automáticamente.' : 'No encontramos esa dirección. Añade el municipio o señala el lugar en el mapa.';
            matches.forEach((item) => {
                const li = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button'; button.className = 'btn-secondary'; button.textContent = `Elegir: ${item.display_name}`;
                button.addEventListener('click', () => {
                    setPoint(Number(item.lat), Number(item.lon), 'Dirección elegida. Puedes continuar.');
                    const reference = form.querySelector('#address_reference');
                    if (!reference.value.trim()) reference.value = String(item.display_name).slice(0, 255);
                    results.replaceChildren();
                    addressInput.focus();
                });
                li.append(button); results.append(li);
            });
        } catch (error) {
            if (error.name !== 'AbortError') locationStatus.textContent = 'No pudimos buscar la dirección. Puedes señalar el lugar en el mapa, usar tu ubicación o escribir las coordenadas.';
        } finally { addressButton.disabled = false; }
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
    photoInput.addEventListener('change', () => {
        photoInput.removeAttribute('capture');
        const file = photoInput.files?.[0];
        if (photoUrl) URL.revokeObjectURL(photoUrl);
        preview.hidden = true; preview.removeAttribute('src'); photoProblem = '';
        form.querySelector('[data-photo-label]').textContent = file ? 'Cambiar foto' : 'Agregar foto';
        if (!file) return;
        if (file.size > 5120 * 1024) photoProblem = 'La foto pesa más de 5 MB. Elige una más pequeña.';
        else if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) photoProblem = 'Elige una foto JPEG, PNG o WebP.';
        if (photoProblem) { errorFor(photoInput, photoProblem); return; }
        photoUrl = URL.createObjectURL(file);
        preview.onload = () => {
            if (preview.naturalWidth > 4000 || preview.naturalHeight > 4000) photoProblem = 'La foto supera los 4000 × 4000 píxeles. Elige una más pequeña.';
            errorFor(photoInput, photoProblem);
        };
        preview.onerror = () => { photoProblem = 'No pudimos abrir esa foto. Elige otra imagen.'; preview.hidden = true; errorFor(photoInput, photoProblem); };
        preview.src = photoUrl; preview.hidden = false;
        errorFor(photoInput, '');
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
