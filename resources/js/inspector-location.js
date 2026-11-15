const form = document.getElementById('audit-form');
const button = form?.querySelector('[data-capture-gps]');
if (button) {
    const status = form.querySelector('[data-gps-status]');
    status.setAttribute('role', 'status');
    const latitude = form.querySelector('[data-lat]');
    const longitude = form.querySelector('[data-lng]');
    const container = form.querySelector('[data-inspector-map]');
    let map, marker, circle;
    const valid = (lat, lng) => Number.isFinite(lat) && lat >= -90 && lat <= 90 && Number.isFinite(lng) && lng >= -180 && lng <= 180;
    function showMap(lat, lng, accuracy) {
        if (!window.L) {
            status.textContent = 'Ubicación capturada. El mapa no pudo cargar; revisa tu conexión.';
            return;
        }
        container.hidden = false;
        if (!map) {
            map = window.L.map(container, { scrollWheelZoom: false }).setView([lat, lng], 17);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
            marker = window.L.marker([lat, lng], { title: 'Ubicación del inspector', alt: 'Ubicación GPS capturada del inspector' }).addTo(map);
        }
        marker.setLatLng([lat, lng]);
        if (circle) { circle.remove(); circle = null; }
        if (Number.isFinite(accuracy) && accuracy > 0) {
            circle = window.L.circle([lat, lng], { radius: accuracy, color: '#1e40af', fillOpacity: 0.08 }).addTo(map);
        }
        requestAnimationFrame(() => {
            map.invalidateSize();
            if (circle) map.fitBounds(circle.getBounds(), { padding: [24, 24], maxZoom: 17 });
            else map.setView([lat, lng], 17);
        });
    }
    if (latitude.value && longitude.value && valid(Number(latitude.value), Number(longitude.value))) {
        showMap(Number(latitude.value), Number(longitude.value));
    }
    button.addEventListener('click', () => {
        if (!window.isSecureContext || !navigator.geolocation) {
            status.textContent = 'Abre el enlace HTTPS y permite la ubicación del dispositivo para capturar el GPS.';
            return;
        }
        button.disabled = true;
        status.textContent = 'Buscando tu ubicación… Permite el acceso si el dispositivo lo solicita.';
        navigator.geolocation.getCurrentPosition(position => {
            button.disabled = false;
            const { latitude: lat, longitude: lng, accuracy } = position.coords;
            if (!valid(lat, lng)) { status.textContent = 'No recibimos una ubicación válida. Vuelve a capturar el GPS.'; return; }
            latitude.value = lat.toFixed(7);
            longitude.value = lng.toFixed(7);
            status.textContent = accuracy > 100
                ? 'La señal es poco precisa. El círculo muestra el margen estimado; vuelve a capturar el GPS desde la obra.'
                : 'Ubicación capturada. El punto marca tu posición en el mapa.';
            showMap(lat, lng, accuracy);
        }, error => {
            button.disabled = false;
            status.textContent = error.code === 1
                ? 'Permite la ubicación en los ajustes de este sitio y vuelve a capturar el GPS.'
                : 'No pudimos obtener la ubicación. Activa el GPS y vuelve a intentarlo.';
        }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
    });
}
