@extends('layouts.app')

@section('title', 'Reportar incidencia — Vigía Ciudadano')

@section('content')
@php
    // El JS del navegador recibe los endpoints ya resueltos (subcarpetas de
    // XAMPP incluidas): nada de rutas relativas armadas a mano en JS.
    $jsConfig = [
        'csrf' => csrf_token(),
        'nearby' => url('/api/v1/reports/nearby'),
        'subscribeBase' => url('/api/v1/reports'),
        'detailBase' => url('/reportes'),
    ];
@endphp

<div class="mx-auto max-w-xl space-y-6">
    <header>
        <h1 class="text-2xl font-semibold tracking-tight text-[#062e24]">Reportar una incidencia</h1>
        <p class="mt-1 text-sm text-[#1f5142]">
            Foto del daño, ubicación exacta y descripción. Menos de un minuto.
        </p>
    </header>

    <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data"
          id="report-form" class="space-y-6">
        @csrf

        {{-- ================= 1. CATEGORÍA ================= --}}
        <fieldset class="space-y-3">
            <legend class="text-sm font-semibold text-[#062e24]">¿Qué tipo de daño ves?</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($categories as $category)
                    <label class="cursor-pointer">
                        <input type="radio" name="category_id" value="{{ $category->id }}" class="peer sr-only"
                               @checked((int) old('category_id') === $category->id) 
                               data-category
                               data-category-id="{{ $category->id }}"
                               data-category-name="{{ $category->name }}"
                               data-category-sla="{{ $category->sla_hours }}"
                               data-category-department="{{ $category->department->name }}"
                               data-category-icon="{{ \App\Support\RoleUi::categoryIcon($category->name) }}"
                               data-category-description="{{ $category->description }}">
                        <div class="relative h-full min-h-[96px] flex items-center gap-3.5 p-4 rounded-2xl bg-white border border-[#d5e8e0] shadow-[0_4px_15px_rgba(6,46,36,0.03)] cursor-pointer transition-all duration-200 hover:border-[#087b5f]/50 hover:bg-[#f8fdfa] peer-checked:border-[#087b5f] peer-checked:bg-[#eaf7f1] peer-checked:ring-2 peer-checked:ring-[#087b5f]/20">
                            {{-- Caja de icono --}}
                            <div class="w-11 h-11 rounded-xl bg-[#f0faf5] flex items-center justify-center text-xl flex-shrink-0 border border-[#d5e8e0] peer-checked:bg-[#087b5f] peer-checked:text-white transition-colors">
                                {{ \App\Support\RoleUi::categoryIcon($category->name) }}
                            </div>

                            {{-- Contenido de texto --}}
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm text-[#062e24] leading-tight line-clamp-2">{{ $category->name }}</div>
                                <div class="text-xs text-[#597268] line-clamp-1 mt-0.5">{{ $category->department->name }}</div>
                                <div class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-[#e2f5eb] text-[#09654e] w-fit mt-1">
                                    SLA {{ $category->sla_hours }}h
                                </div>
                            </div>

                            {{-- Indicador de selección (check) --}}
                            <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-opacity text-[#087b5f]">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('category_id')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </fieldset>

        {{-- ================= 1.5 MODAL DE CONFIRMACIÓN DE CATEGORÍA ================= --}}
        <div id="category-modal-backdrop" class="fixed inset-0 z-50 bg-[#062e24]/40 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-200 hidden">
            <div id="category-modal-card" class="max-w-lg w-full bg-white rounded-3xl border border-[#d5e8e0] shadow-[0_25px_60px_rgba(6,46,36,0.2)] p-6 md:p-8 transform transition-all scale-95 opacity-0">
                {{-- Cabecera --}}
                <div class="mb-5">
                    <span class="inline-block bg-[#e2f5eb] text-[#09654e] text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-3">
                        Categoría seleccionada
                    </span>
                    <div class="flex items-center gap-4">
                        <div id="confirm-icon" class="w-12 h-12 rounded-xl bg-[#087b5f] flex items-center justify-center text-2xl text-white flex-shrink-0">
                            <!-- Icono dinámico -->
                        </div>
                        <h3 id="confirm-title" class="text-xl font-bold text-[#062e24]">
                            <!-- Título dinámico -->
                        </h3>
                    </div>
                </div>

                {{-- Cuerpo de información --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div class="rounded-2xl bg-[#f8fdfa] border border-[#d5e8e0] p-4">
                        <p class="text-xs font-semibold text-[#597268] uppercase tracking-wider mb-1">Dependencia responsable</p>
                        <p id="confirm-department" class="text-sm font-semibold text-[#062e24]">
                            <!-- Dependencia dinámica -->
                        </p>
                    </div>
                    <div class="rounded-2xl bg-[#f8fdfa] border border-[#d5e8e0] p-4">
                        <p class="text-xs font-semibold text-[#597268] uppercase tracking-wider mb-1">Compromiso de atención</p>
                        <p id="confirm-sla" class="text-sm font-semibold text-[#062e24]">
                            <!-- SLA dinámico -->
                        </p>
                        <p class="text-xs text-[#597268] mt-1">Tiempo reglamentario para despacho e inicio de labores</p>
                    </div>
                </div>

                {{-- Descripción --}}
                <div class="rounded-2xl bg-[#f0faf5] border border-[#d5e8e0] p-4 mb-6">
                    <p class="text-xs font-semibold text-[#597268] uppercase tracking-wider mb-2">Alcance de esta categoría</p>
                    <p id="confirm-description" class="text-sm text-[#1f5142] leading-relaxed">
                        <!-- Descripción dinámica -->
                    </p>
                </div>

                {{-- Botonera de decisión --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" id="confirm-category-btn"
                            class="flex-1 bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-6 py-3 rounded-xl shadow-md transition transform active:scale-95">
                        ✓ Confirmar este tipo de daño
                    </button>
                    <button type="button" id="cancel-category-btn"
                            class="flex-1 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 font-semibold px-5 py-3 rounded-xl transition">
                        ✕ Elegir otro daño
                    </button>
                </div>
            </div>
        </div>

        {{-- ================= 2. FOTO ================= --}}
        <section class="space-y-3">
            <h2 class="text-sm font-semibold text-[#062e24]">Foto del daño <span class="text-rose-500">*</span></h2>

            {{-- capture="environment": en móviles abre la cámara trasera de forma nativa;
                 en escritorio degrada a selector de archivos. --}}
            <label for="damage_photo"
                   class="block cursor-pointer overflow-hidden rounded-2xl border border-dashed border-[#b9daca] bg-white text-[#062e24] transition duration-200 hover:border-[#087b5f] hover:bg-[#f0faf5]">
                <input id="damage_photo" name="damage_photo" type="file"
                       accept="image/*" capture="environment"
                       class="hidden" data-photo-input required>
                <div class="flex flex-col items-center justify-center gap-2 p-6 text-center" data-photo-placeholder>
                    <span class="grid size-14 place-items-center rounded-2xl bg-[#e8f8f0] text-2xl">📷</span>
                    <span class="text-sm font-medium text-[#062e24]">Toma o selecciona una foto</span>
                    <span class="text-xs text-[#5d7d70]">JPEG, PNG o WebP · máx. 5 MB</span>
                </div>
                <img data-photo-preview class="hidden aspect-video w-full object-cover" alt="Vista previa de la foto">
            </label>

            @error('damage_photo')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </section>

        {{-- ================= 3. UBICACIÓN ================= --}}
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-[#062e24]">Ubicación <span class="text-rose-500">*</span></h2>
            </div>

            <div class="relative">
                <div id="map" class="h-64 w-full overflow-hidden rounded-2xl border-4 border-white shadow-md ring-1 ring-[#d5e8e0]"></div>
                
                {{-- Botón flotante de geolocalización GPS --}}
                <button type="button" data-geolocate
                        class="absolute top-3 right-3 z-[1000] bg-white hover:bg-[#f0faf5] text-[#087b5f] border border-[#d5e8e0] font-semibold text-xs px-3 py-2 rounded-xl shadow-md transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Usar mi ubicación</span>
                </button>
            </div>
            
            <p class="hidden text-xs text-amber-600" data-map-fallback>
                Mapa no disponible sin conexión: escribe las coordenadas manualmente abajo.
            </p>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="latitude" class="mb-1 block text-xs text-[#5d7d70]">Latitud</label>
                    <input id="latitude" name="latitude" type="number" step="0.000001" min="-90" max="90"
                           value="{{ old('latitude') }}" data-lat-input required
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-3 py-2 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    @error('latitude')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="longitude" class="mb-1 block text-xs text-[#5d7d70]">Longitud</label>
                    <input id="longitude" name="longitude" type="number" step="0.000001" min="-180" max="180"
                           value="{{ old('longitude') }}" data-lng-input required
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-3 py-2 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    @error('longitude')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p class="text-xs text-[#5d7d70]">
                💡 Puedes arrastrar el marcador en el mapa para ajustar el punto exacto del daño.
            </p>
        </section>

        {{-- ================= 4. DETALLES ================= --}}
        <section class="space-y-4">
            <div>
                <label for="title" class="mb-1 block text-sm font-semibold text-[#062e24]">Título del reporte</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" required
                       minlength="5" maxlength="150" placeholder="Ej: Bache profundo en Calle 80 con Carrera 11"
                       class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                @error('title')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-semibold text-[#062e24]">Descripción detallada</label>
                <textarea id="description" name="description" rows="4" required
                          minlength="15" maxlength="300"
                          placeholder="Cuenta qué pasa, desde cuándo y a quién afecta. Ej: el bache mide unos 40 cm y acumula agua; dos motos ya han patinado esta semana."
                          data-counter-textarea
                          class="w-full resize-y rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">{{ old('description') }}</textarea>
                <div class="mt-1 flex items-center justify-between text-xs">
                    <span class="text-[#5d7d70]">Entre 15 y 300 caracteres</span>
                    <span data-counter-output class="font-mono text-[#5d7d70]">0/300</span>
                </div>
                @error('description')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="address_reference" class="mb-1 block text-sm font-semibold text-[#062e24]">Referencia (opcional)</label>
                <input id="address_reference" name="address_reference" type="text" value="{{ old('address_reference') }}"
                       maxlength="255" placeholder="Ej: frente al parqueadero del Éxito"
                       class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                @error('address_reference')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- ================= 5. CONTACTO OPCIONAL ================= --}}
        <section class="space-y-4 rounded-2xl border border-[#d5e8e0] bg-[#f0faf5]/50 p-5">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#e8f8f0] text-lg">📬</span>
                <div class="flex-1">
                    <h2 class="text-sm font-semibold text-[#062e24]">¿Deseas recibir novedades de este reporte? (Opcional)</h2>
                    <p class="mt-1 text-xs text-[#5d7d70]">
                        Deja tus datos de contacto para recibir actualizaciones sobre el estado de tu reporte.
                        <span class="font-medium">Si iniciaste sesión, estos datos son opcionales.</span>
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="citizen_name" class="mb-1 block text-sm font-medium text-[#062e24]">Nombre</label>
                    <input id="citizen_name" name="citizen_name" type="text" value="{{ old('citizen_name') }}"
                           maxlength="120" placeholder="Tu nombre completo"
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    @error('citizen_name')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="citizen_phone" class="mb-1 block text-sm font-medium text-[#062e24]">Teléfono</label>
                    <input id="citizen_phone" name="citizen_phone" type="tel" value="{{ old('citizen_phone') }}"
                           maxlength="25" placeholder="300 000 0000"
                           class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                    @error('citizen_phone')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="citizen_email" class="mb-1 block text-sm font-medium text-[#062e24]">Correo electrónico</label>
                <input id="citizen_email" name="citizen_email" type="email" value="{{ old('citizen_email') }}"
                       maxlength="255" placeholder="tu@correo.gov.co"
                       class="w-full rounded-xl border border-[#b9daca] bg-[#fcfdfd] px-4 py-2.5 text-sm text-[#062e24] outline-none transition focus:border-[#087b5f] focus:ring-2 focus:ring-[#087b5f]/20">
                @error('citizen_email')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </section>

        {{-- ================= 6. ENVÍO ================= --}}
        <div class="space-y-3">
            {{-- Estado de la alerta de duplicados, visible junto al botón: el
                 ciudadano nunca se entera de la veeduría solo si abre el modal. --}}
            <p data-duplicate-banner
               class="hidden items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                <span>⚠️</span>
                <span>Hay <b data-duplicate-count>0</b> reporte(s) de la misma categoría a menos de 20 m.
                    ¿Ya existe? <a href="#" data-open-duplicates class="underline font-medium">Súmate a la veeduría</a> en vez de duplicarlo.</span>
            </p>

            <button type="submit"
                    class="w-full bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 py-4 text-base"
                    data-submit>
                Radicar reporte
            </button>
            <p class="text-center text-xs text-[#5d7d70]">
                Recibirás un número de radicado y podrás seguir el estado de la obra.
            </p>
        </div>
    </form>
</div>

{{-- ============ MODAL: reportes cercanos (veeduría) ============ --}}
<div id="duplicates-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="dup-title">
    <div class="absolute inset-0 bg-[#062e24]/40 backdrop-blur-sm" data-close-duplicates></div>

    <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 sm:inset-x-auto sm:left-1/2 sm:w-full sm:max-w-md sm:-translate-x-1/2">
        <div class="rounded-2xl border border-[#d5e8e0] bg-white p-5 text-[#062e24] shadow-2xl">
            <div class="mb-1 flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-100 text-xl">⚠️</span>
                <div>
                    <h3 id="dup-title" class="text-base font-semibold text-[#062e24]">Ya hay reportes aquí cerca</h3>
                    <p class="text-xs text-[#5d7d70]">A menos de 20 m de tu punto, misma categoría.</p>
                </div>
            </div>

            <ul class="my-4 max-h-64 space-y-2 overflow-y-auto pr-1" data-duplicates-list></ul>

            <div class="flex flex-col gap-2">
                <button type="button" data-close-duplicates
                        class="w-full bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98] py-3">
                    Súmate a la veeduría en vez de duplicar
                </button>
                <button type="button" data-close-duplicates
                        class="w-full bg-white hover:bg-[#f0faf5] text-[#062e24] border border-[#d5e8e0] font-semibold px-4 py-2.5 rounded-xl transition">
                    Crear reporte nuevo de todas formas
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- CDN de Leaflet SIN atributos SRI: si el hash no coincide (p. ej. el
         espejo cambia de bytes) el navegador bloquea el script y el mapa
         desaparece. El fallback propio ya cubre la ausencia de red. --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>

    <script>
        (function () {
            const VIGIA = @json($jsConfig);

            const form = document.getElementById('report-form');
            const latInput = form.querySelector('[data-lat-input]');
            const lngInput = form.querySelector('[data-lng-input]');
            const geolocateBtn = form.querySelector('[data-geolocate]');
            const photoInput = form.querySelector('[data-photo-input]');
            const photoPreview = form.querySelector('[data-photo-preview]');
            const photoPlaceholder = form.querySelector('[data-photo-placeholder]');
            const counterArea = form.querySelector('[data-counter-textarea]');
            const counterOut = form.querySelector('[data-counter-output]');

            const headers = { 'X-CSRF-TOKEN': VIGIA.csrf, 'Accept': 'application/json' };

            // ---------- Modal de confirmación de categoría ----------
            const modalBackdrop = document.getElementById('category-modal-backdrop');
            const modalCard = document.getElementById('category-modal-card');
            const confirmIcon = document.getElementById('confirm-icon');
            const confirmTitle = document.getElementById('confirm-title');
            const confirmDepartment = document.getElementById('confirm-department');
            const confirmSla = document.getElementById('confirm-sla');
            const confirmDescription = document.getElementById('confirm-description');
            const confirmBtn = document.getElementById('confirm-category-btn');
            const cancelBtn = document.getElementById('cancel-category-btn');
            let selectedCategoryInput = null;

            // Función para abrir el modal
            function openCategoryModal(input) {
                selectedCategoryInput = input;
                
                // Poblar datos en el modal
                confirmIcon.textContent = input.dataset.categoryIcon;
                confirmTitle.textContent = input.dataset.categoryName;
                confirmDepartment.textContent = input.dataset.categoryDepartment;
                confirmSla.textContent = input.dataset.categorySla + ' horas';
                confirmDescription.textContent = input.dataset.categoryDescription;
                
                // Mostrar modal con animación
                modalBackdrop.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Bloquear scroll
                
                requestAnimationFrame(() => {
                    modalCard.classList.remove('scale-95', 'opacity-0');
                    modalCard.classList.add('scale-100', 'opacity-100');
                });
            }

            // Función para cerrar el modal
            function closeCategoryModal(shouldKeepSelection) {
                // Animación de salida
                modalCard.classList.remove('scale-100', 'opacity-100');
                modalCard.classList.add('scale-95', 'opacity-0');
                
                setTimeout(() => {
                    modalBackdrop.classList.add('hidden');
                    document.body.style.overflow = ''; // Restaurar scroll
                    
                    if (!shouldKeepSelection && selectedCategoryInput) {
                        // Deseleccionar el radio button
                        selectedCategoryInput.checked = false;
                        selectedCategoryInput = null;
                    }
                }, 200);
            }

            // Mostrar modal al seleccionar una categoría
            form.querySelectorAll('[data-category]').forEach((input) => {
                input.addEventListener('change', function() {
                    if (this.checked) {
                        openCategoryModal(this);
                    }
                });
            });

            // Botón de confirmación
            confirmBtn.addEventListener('click', function() {
                closeCategoryModal(true); // Mantener selección
                
                // Scroll suave hacia el siguiente paso (foto)
                setTimeout(() => {
                    const photoSection = document.querySelector('[data-photo-input]');
                    if (photoSection) {
                        photoSection.closest('section').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 250);
            });

            // Botón de cancelación
            cancelBtn.addEventListener('click', function() {
                closeCategoryModal(false); // Deseleccionar
            });

            // Cerrar modal al hacer clic fuera (en el backdrop)
            modalBackdrop.addEventListener('click', function(e) {
                if (e.target === modalBackdrop) {
                    closeCategoryModal(false);
                }
            });

            // Cerrar modal con tecla Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !modalBackdrop.classList.contains('hidden')) {
                    closeCategoryModal(false);
                }
            });

            // ---------- Vista previa de foto ----------
            photoInput.addEventListener('change', () => {
                const file = photoInput.files && photoInput.files[0];
                if (!file) { return; }
                photoPreview.src = URL.createObjectURL(file);
                photoPreview.classList.remove('hidden');
                photoPlaceholder.classList.add('hidden');
            });

            // ---------- Contador 15–300 ----------
            function updateCounter() {
                const n = counterArea.value.trim().length;
                counterOut.textContent = n + '/300';
                const invalid = n > 0 && n < 15;
                counterOut.classList.toggle('text-rose-500', invalid);
                counterOut.classList.toggle('text-emerald-500', n >= 15);
            }
            counterArea.addEventListener('input', updateCounter);
            updateCounter();

            // ---------- Mapa (Leaflet) ----------
            let map = null;
            let marker = null;

            function setPoint(lat, lng, pan = false) {
                latInput.value = lat.toFixed(6);
                lngInput.value = lng.toFixed(6);
                if (marker) { marker.setLatLng([lat, lng]); }
                if (map && pan) { map.setView([lat, lng], Math.max(map.getZoom(), 17), { animate: true }); }
                scheduleNearbyCheck();
            }

            if (typeof L !== 'undefined') {
                // Coordenadas iniciales: Santander de Quilichao, Cauca
                map = L.map('map', { zoomControl: true }).setView([3.0095, -76.4849], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);

                function attachMarker(lat, lng) {
                    if (marker) { marker.setLatLng([lat, lng]); return; }
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    marker.on('dragend', () => {
                        const p = marker.getLatLng();
                        setPoint(p.lat, p.lng);
                    });
                }

                map.on('click', (e) => {
                    attachMarker(e.latlng.lat, e.latlng.lng);
                    setPoint(e.latlng.lat, e.latlng.lng);
                });

                // Estado previo (revalidación): mostrar el punto ya guardado.
                if (latInput.value && lngInput.value) {
                    const lat = parseFloat(latInput.value);
                    const lng = parseFloat(lngInput.value);
                    if (Number.isFinite(lat) && Number.isFinite(lng)) {
                        attachMarker(lat, lng);
                        map.setView([lat, lng], 17);
                    }
                }
            } else {
                const mapEl = document.getElementById('map');
                mapEl.classList.add('hidden');
                form.querySelector('[data-map-fallback]').classList.remove('hidden');
            }

            // ---------- Geolocalización automática con geocodificación inversa ----------
            geolocateBtn.addEventListener('click', () => {
                if (!('geolocation' in navigator)) {
                    alert('Tu navegador no permite geolocalización; escribe las coordenadas manualmente.');
                    return;
                }
                geolocateBtn.disabled = true;
                const btnText = geolocateBtn.querySelector('span:last-child');
                btnText.textContent = 'Buscando GPS…';

                navigator.geolocation.getCurrentPosition(
                    async (pos) => {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;
                        
                        // Actualizar mapa y marcador
                        if (map) {
                            map.setView([lat, lng], 17, { animate: true });
                            attachMarker(lat, lng);
                        }
                        setPoint(lat, lng);
                        
                        // Intentar geocodificación inversa (Nominatim)
                        try {
                            const response = await fetch(
                                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                                { headers: { 'Accept': 'application/json' } }
                            );
                            
                            if (response.ok) {
                                const data = await response.json();
                                if (data.display_name) {
                                    // Extraer dirección simplificada
                                    const addr = data.address;
                                    let address = '';
                                    
                                    if (addr.road) {
                                        address = addr.road;
                                        if (addr.house_number) address += ' ' + addr.house_number;
                                    } else if (addr.neighbourhood || addr.suburb) {
                                        address = addr.neighbourhood || addr.suburb;
                                    }
                                    
                                    if (addr.city || addr.town || addr.village) {
                                        address += ', ' + (addr.city || addr.town || addr.village);
                                    }
                                    
                                    // Actualizar campo de referencia si está vacío
                                    const addressRefInput = form.querySelector('[name="address_reference"]');
                                    if (addressRefInput && !addressRefInput.value.trim()) {
                                        addressRefInput.value = address || 'Ubicación detectada por GPS';
                                    }
                                }
                            }
                        } catch (err) {
                            // Si falla la geocodificación, usar mensaje genérico
                            const addressRefInput = form.querySelector('[name="address_reference"]');
                            if (addressRefInput && !addressRefInput.value.trim()) {
                                addressRefInput.value = 'Ubicación detectada por GPS';
                            }
                        }
                        
                        btnText.textContent = 'Usar mi ubicación';
                        geolocateBtn.disabled = false;
                    },
                    (err) => {
                        geolocateBtn.disabled = false;
                        btnText.textContent = 'Usar mi ubicación';
                        
                        // Mensaje más amigable según el tipo de error
                        let message = 'No se pudo obtener tu ubicación. ';
                        if (err.code === 1) { // PERMISSION_DENIED
                            message += 'Permitiste el acceso a tu ubicación en la configuración del navegador.';
                        } else if (err.code === 2) { // POSITION_UNAVAILABLE
                            message += 'La señal GPS no está disponible en este momento.';
                        } else if (err.code === 3) { // TIMEOUT
                            message += 'La solicitud de ubicación tardó demasiado.';
                        }
                        message += ' Puedes tocar el mapa directamente para marcar el punto.';
                        
                        alert(message);
                    },
                    { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
                );
            });

            // ---------- Alerta de duplicados: /api/v1/reports/nearby ----------
            const modal = document.getElementById('duplicates-modal');
            const listEl = modal.querySelector('[data-duplicates-list]');
            const banner = form.querySelector('[data-duplicate-banner]');
            const bannerCount = form.querySelector('[data-duplicate-count]');
            let lastCheckedKey = '';
            let dismissedKey = '';
            let debounce = null;

            function currentCategoryId() {
                const sel = form.querySelector('input[name="category_id"]:checked');
                return sel ? parseInt(sel.value, 10) : null;
            }

            function scheduleNearbyCheck() {
                clearTimeout(debounce);
                debounce = setTimeout(nearbyCheck, 500);
            }

            form.querySelectorAll('input[name="category_id"]').forEach((r) => r.addEventListener('change', scheduleNearbyCheck));

            async function nearbyCheck() {
                const categoryId = currentCategoryId();
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);

                if (!categoryId || !Number.isFinite(lat) || !Number.isFinite(lng)) { return; }

                const key = categoryId + ':' + lat.toFixed(4) + ':' + lng.toFixed(4);
                if (key === lastCheckedKey || key === dismissedKey) { return; }
                lastCheckedKey = key;

                const url = VIGIA.nearby + '?latitude=' + lat + '&longitude=' + lng
                    + '&category_id=' + categoryId + '&radius_meters=20';

                try {
                    const res = await fetch(url, { headers: headers, credentials: 'same-origin' });
                    if (!res.ok) { return; }
                    const json = await res.json();
                    const found = json.data || [];

                    if (found.length === 0) {
                        banner.classList.add('hidden');
                        banner.classList.remove('flex');
                        return;
                    }

                    renderDuplicates(found);
                    bannerCount.textContent = found.length;
                    banner.classList.remove('hidden');
                    banner.classList.add('flex');
                    openModal();
                } catch (e) {
                    /* Sin red o sesión expirada: no bloqueamos el reporte. */
                }
            }

            function renderDuplicates(items) {
                listEl.innerHTML = '';
                items.forEach((item) => {
                    const li = document.createElement('li');
                    li.className = 'rounded-xl border border-[#d5e8e0] bg-white p-3';
                    li.innerHTML =
                        '<div class="flex items-start justify-between gap-3">' +
                            '<div class="min-w-0">' +
                                '<p class="truncate text-sm font-medium text-[#062e24]">' + escapeHtml(item.title) + '</p>' +
                                '<p class="mt-0.5 text-xs text-[#5d7d70]">' + escapeHtml(item.ticket_code)
                                + ' · a ' + item.distance_m + ' m · ' + escapeHtml(item.status) + '</p>' +
                            '</div>' +
                            '<div class="flex shrink-0 gap-2">' +
                                '<a href="' + VIGIA.detailBase + '/' + encodeURIComponent(item.ticket_code) + '"' +
                                ' class="bg-white hover:bg-[#f0faf5] text-[#062e24] border border-[#d5e8e0] font-semibold px-4 py-2.5 rounded-xl transition text-xs">Ver</a>' +
                                '<button type="button" data-sub="' + item.id + '"' +
                                ' class="bg-[#087b5f] hover:bg-[#05664e] text-white font-bold px-4 py-2.5 rounded-xl shadow-sm transition active:scale-[0.98] text-xs">Sumarme</button>' +
                            '</div>' +
                        '</div>';
                    listEl.appendChild(li);
                });

                listEl.querySelectorAll('[data-sub]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        btn.disabled = true;
                        btn.textContent = '…';
                        try {
                            const res = await fetch(VIGIA.subscribeBase + '/' + btn.dataset.sub + '/subscribe', {
                                method: 'POST', headers: headers, credentials: 'same-origin',
                            });
                            if (res.ok) {
                                btn.textContent = 'Siguiendo ✓';
                                btn.className = 'rounded-lg bg-[#e8f8f0] px-2.5 py-1 text-xs font-semibold text-[#087b5f]';
                            } else {
                                btn.textContent = 'Error, reintenta';
                                btn.disabled = false;
                            }
                        } catch (e) {
                            btn.textContent = 'Sin conexión';
                            btn.disabled = false;
                        }
                    });
                });
            }

            function openModal() { modal.classList.remove('hidden'); }
            function closeModal() {
                modal.classList.add('hidden');
                dismissedKey = lastCheckedKey;
            }
            document.querySelectorAll('[data-close-duplicates]').forEach((el) => el.addEventListener('click', closeModal));
            form.querySelector('[data-open-duplicates]').addEventListener('click', (e) => { e.preventDefault(); openModal(); });

            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                }[c]));
            }

            // Al enviar, no re-chocar el modal con el servidor.
            form.addEventListener('submit', () => modal.classList.add('hidden'));
        })();
    </script>
@endpush
