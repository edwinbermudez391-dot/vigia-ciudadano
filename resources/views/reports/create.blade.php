@extends('layouts.app')
@section('title', 'Reportar un problema — Vigía Ciudadano')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
@endpush

@section('content')
<div class="citizen-content citizen-report-page">
    <header class="citizen-heading">
        <h1>Vamos a reportar un problema</h1>
        <p>No necesitas una cuenta.</p>
    </header>
    <noscript><p class="citizen-notice">Puedes completar el formulario en esta página. Sin JavaScript, escribe las coordenadas del lugar en el paso 2.</p></noscript>
    @if ($errors->any())
        <div class="citizen-notice citizen-error-summary" role="alert" tabindex="-1" data-citizen-errors>
            <h2>Revisa la información que ingresaste</h2>
            <ul>
                @foreach ($errors->messages() as $field => $messages)
                    <li><a href="#{{ match ($field) { 'category_id' => 'category-options', 'title' => 'description', default => $field } }}">{{ match ($field) {
                        'damage_photo' => 'Vuelve a elegir una foto JPEG, PNG o WebP de hasta 5 MB y 4000 × 4000 píxeles.',
                        'latitude', 'longitude' => 'Selecciona la ubicación del problema en el paso 2.',
                        default => $messages[0],
                    } }}</a></li>
                @endforeach
            </ul>
            <p>Los demás datos se conservaron. Por seguridad, debes volver a seleccionar la foto.</p>
        </div>
    @endif

    <ol class="citizen-progress" aria-label="Pasos para enviar el reporte" data-report-progress hidden>
        <li aria-current="step"><span>1</span>El problema</li>
        <li><span>2</span>El lugar</li>
        <li><span>3</span>Revisar y enviar</li>
    </ol>
    <p class="citizen-help" role="status" data-step-status hidden></p>

    <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data"
          id="report-form" class="citizen-report-form" data-citizen-report
          data-nearby-url="{{ url('/api/v1/reports/nearby') }}" data-subscribe-url="{{ url('/api/v1/reports') }}"
          data-detail-url="{{ url('/reportes') }}" data-can-subscribe="{{ auth()->check() ? 'true' : 'false' }}">
        @csrf
        <section class="citizen-card citizen-step" data-report-step="0" aria-labelledby="step-1-title">
            <h2 id="step-1-title" tabindex="-1">1. ¿Qué pasó?</h2>
            <p id="category-help">Elige un tipo de problema.</p>
            <fieldset id="category-options" tabindex="-1" aria-describedby="category-help category_id-error">
                <legend class="sr-only">Tipo de problema</legend>
                @php
                    $commonNames = ['Baches y Malla Vial', 'Baches', 'Luminaria Apagada o en Falla', 'Fuga de Agua Potable', 'Basuras y Escombros en Vía Pública'];
                    $commonCategories = $categories->filter(fn ($category) => in_array($category->name, $commonNames, true));
                    if ($commonCategories->isEmpty()) $commonCategories = $categories->take(4);
                    $otherCategories = $categories->whereNotIn('id', $commonCategories->pluck('id'));
                @endphp
                @foreach ([$commonCategories, $otherCategories] as $groupIndex => $categoryGroup)
                @if ($categoryGroup->isNotEmpty())
                @if ($groupIndex === 1)
                    <details class="citizen-other-categories" @if ($otherCategories->contains('id', (int) old('category_id'))) open @endif>
                        <summary>Otro problema</summary>
                @endif
                <div class="citizen-categories">
                    @foreach ($categoryGroup as $category)
                        <label class="citizen-category">
                            <input type="radio" name="category_id" value="{{ $category->id }}" required
                                   @checked((int) old('category_id') === $category->id)
                                   aria-describedby="category-help category_id-error" aria-invalid="{{ $errors->has('category_id') ? 'true' : 'false' }}"
                                   data-category data-category-name="{{ $category->name }}"
                                   data-category-sla="{{ $category->sla_hours }}" data-category-department="{{ $category->department->name }}"
                                   data-category-description="{{ $category->description }}">
                            <span><x-citizen-category-label :name="$category->name" with-icon /></span>
                        </label>
                    @endforeach
                </div>
                @if ($groupIndex === 1)</details>@endif
                @endif
                @endforeach
                <x-citizen-field-error field="category_id" />
                @if ($categories->isEmpty())<p class="citizen-notice">No hay tipos de problema disponibles. Inténtalo más tarde.</p>@endif
            </fieldset>
            <details class="citizen-category-info" data-category-info hidden>
                <summary>Ver información de esta categoría</summary>
                <p data-category-description></p><p data-category-responsible></p><p data-category-time></p>
            </details>
            <div>
                <label for="description">Cuéntanos qué pasa</label>
                <textarea id="description" name="description" rows="4" required minlength="15" maxlength="1000" data-counter-textarea
                          placeholder="Ej.: Hay un hueco grande frente a mi casa. Se llena de agua y dificulta el paso."
                          aria-describedby="description-help description-error" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}">{{ old('description') }}</textarea>
                <p id="description-help" class="citizen-help">Sin nombres ni teléfonos. <span data-counter-output class="sr-only"></span></p>
                <x-citizen-field-error field="description" />
            </div>
            <div>
                <p id="photo-title" class="citizen-label">¿Quieres agregar una foto? (Opcional)</p>
                <label for="damage_photo" class="citizen-photo-picker"><x-icon name="camera" /><span data-photo-label>Agregar foto</span>
                    <input id="damage_photo" name="damage_photo" type="file" accept="image/jpeg,image/png,image/webp"
                           class="sr-only" data-photo-input aria-describedby="photo-help damage_photo-error" aria-invalid="{{ $errors->has('damage_photo') ? 'true' : 'false' }}">
                </label>
                <button type="button" class="btn-secondary" data-photo-camera hidden><x-icon name="camera" />Tomar foto</button>
                <button type="button" class="btn-secondary" data-remove-photo hidden>Quitar foto</button>
                <p class="citizen-help" role="status" data-photo-status></p>
                <p id="photo-help" class="citizen-help">Evita rostros y datos personales.</p>
                <img data-photo-preview class="citizen-photo-preview" alt="Vista previa de la foto que enviarás" hidden>
                <x-citizen-field-error field="damage_photo" />
            </div>
            <details><summary>¿Necesitas ayuda?</summary><p>Elige el problema y cuenta qué ocurre. Puedes continuar sin foto.</p></details>
            <div class="citizen-step-actions" data-step-actions hidden>
                <button type="button" class="btn-primary" data-step-next @disabled($categories->isEmpty())>Continuar<x-icon name="arrow" /></button>
            </div>
        </section>

        <section class="citizen-card citizen-step" data-report-step="1" aria-labelledby="step-2-title">
            <h2 id="step-2-title" tabindex="-1">2. ¿Dónde está el problema?</h2>
            <p>¿El problema está donde estás ahora?</p>
            <input type="hidden" name="location_label" value="{{ old('location_label') }}" data-selected-place>
            <button type="button" class="btn-secondary" data-geolocate hidden><x-icon name="map" /><span>Sí, usar mi ubicación</span></button>
            <button type="button" class="btn-secondary" data-choose-address hidden aria-expanded="false" aria-controls="address-panel">No, buscar dirección</button>
            <div id="address-panel" class="citizen-location-search" data-address-search hidden>
                <label for="address-search">Busca una dirección o un lugar conocido</label>
                <div class="citizen-lookup-controls">
                    <input id="address-search" type="search" maxlength="255" placeholder="Ej.: Parque principal, Santander de Quilichao" aria-describedby="address-privacy location-status">
                    <button type="button" class="btn-secondary" data-find-address>Buscar dirección</button>
                </div>
                <details><summary>Ayuda para buscar</summary><p id="address-privacy" class="citizen-help">Incluye calle, barrio y municipio. La dirección se busca en OpenStreetMap; no escribas nombres ni teléfonos.</p></details>
                <ul class="citizen-address-results" data-address-results aria-label="Direcciones encontradas"></ul>
            </div>
            <p id="location-status" class="citizen-notice" role="status" data-location-status>Elige una opción para ubicar el problema.</p>
            <div class="citizen-notice" data-location-confirmation hidden>
                <p data-location-label></p>
                <h3>¿El problema está aquí?</h3>
                <div class="citizen-actions">
                    <button type="button" class="btn-primary" data-confirm-location>Sí, este es el lugar</button>
                    <button type="button" class="btn-secondary" data-change-location>Cambiar lugar</button>
                </div>
            </div>
            <details class="citizen-map-section" data-map-section hidden>
                <summary>Señalar el lugar en el mapa</summary>
                <p id="map-help" class="citizen-help">Toca o haz clic en el lugar. Con el teclado, mueve el mapa con las flechas y usa «Elegir el centro del mapa» para seleccionar el punto azul.</p>
                <div id="map" class="citizen-location-map" role="region" aria-label="Mapa para señalar el problema" aria-describedby="map-help"></div>
                <button type="button" class="btn-secondary" data-use-map-center>Elegir el centro del mapa</button>
            </details>
            <div>
                <label for="address_reference">¿Qué hay cerca? (Opcional)</label>
                <input id="address_reference" name="address_reference" type="text" value="{{ old('address_reference') }}" maxlength="255"
                       placeholder="Ej.: frente al parque, junto a la tienda" autocomplete="street-address"
                       aria-describedby="address_reference-error" aria-invalid="{{ $errors->has('address_reference') ? 'true' : 'false' }}">
                <x-citizen-field-error field="address_reference" />
            </div>
            <details class="citizen-coordinates" data-coordinates open>
                <summary>Más opciones de ubicación</summary>
                <p class="citizen-help">Si buscas la dirección, eliges el mapa o usas tu ubicación, estos datos se completan solos.</p>
                <div class="citizen-fields">
                    <div>
                        <label for="latitude">Latitud</label>
                        <input id="latitude" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude') }}" data-lat-input required
                               aria-describedby="latitude-error" aria-invalid="{{ $errors->has('latitude') ? 'true' : 'false' }}">
                        <x-citizen-field-error field="latitude" />
                    </div>
                    <div>
                        <label for="longitude">Longitud</label>
                        <input id="longitude" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude') }}" data-lng-input required
                               aria-describedby="longitude-error" aria-invalid="{{ $errors->has('longitude') ? 'true' : 'false' }}">
                        <x-citizen-field-error field="longitude" />
                    </div>
                </div>
            </details>
            <div class="citizen-step-actions" data-step-actions hidden>
                <button type="button" class="btn-secondary" data-step-previous>Anterior</button>
                <button type="button" class="btn-primary" data-step-next>Continuar<x-icon name="arrow" /></button>
            </div>
        </section>

        <section class="citizen-card citizen-step" data-report-step="2" aria-labelledby="step-3-title">
            <h2 id="step-3-title" tabindex="-1">3. Revisa y envía tu reporte</h2>
            <p>Comprueba los datos. Puedes corregirlos antes de enviar.</p>
            <dl class="citizen-summary" data-report-review>
                <div><dt>Problema</dt><dd data-review-category></dd><button type="button" class="btn-secondary" data-edit-step="0" hidden>Cambiar problema</button></div>
                <div><dt>Descripción</dt><dd data-review-description></dd></div>
                <div><dt>Lugar</dt><dd data-review-location></dd><button type="button" class="btn-secondary" data-edit-step="1" hidden>Cambiar lugar</button></div>
            </dl>
            <img data-review-photo class="citizen-photo-preview" alt="Foto del problema que vas a enviar" hidden>
            <details class="citizen-contact" @if ($errors->hasAny(['citizen_name', 'citizen_phone', 'citizen_email'])) open @endif>
                <summary>Recibir novedades por correo (opcional)</summary>
                <p class="citizen-help">Estos datos son opcionales y privados.</p>
                <div class="citizen-fields">
                    <div><label for="citizen_name">Nombre (opcional)</label><input id="citizen_name" name="citizen_name" type="text" maxlength="120" value="{{ old('citizen_name') }}" autocomplete="name" aria-describedby="citizen_name-error" aria-invalid="{{ $errors->has('citizen_name') ? 'true' : 'false' }}"><x-citizen-field-error field="citizen_name" /></div>
                    <div><label for="citizen_phone">Teléfono (opcional)</label><input id="citizen_phone" name="citizen_phone" type="tel" maxlength="25" value="{{ old('citizen_phone') }}" autocomplete="tel" aria-describedby="citizen_phone-error" aria-invalid="{{ $errors->has('citizen_phone') ? 'true' : 'false' }}"><x-citizen-field-error field="citizen_phone" /></div>
                </div>
                <div><label for="citizen_email">Correo electrónico (opcional)</label><input id="citizen_email" name="citizen_email" type="email" maxlength="255" value="{{ old('citizen_email') }}" autocomplete="email" aria-describedby="citizen_email-error" aria-invalid="{{ $errors->has('citizen_email') ? 'true' : 'false' }}"><x-citizen-field-error field="citizen_email" /></div>
            </details>
            <div class="citizen-notice" data-duplicate-banner hidden>
                <p>Hay <strong data-duplicate-count></strong> reporte(s) similares cerca.</p>
                <button type="button" class="btn-secondary" data-check-duplicates>Ver reportes cercanos</button>
                <ul class="citizen-address-results" data-duplicates-list hidden></ul>
                <p class="citizen-help">Puedes consultar uno existente o enviar este reporte si es un problema diferente.</p>
            </div>
            <p class="citizen-help">Recibirás un número para consultar tu reporte.</p>
            <p class="citizen-error" role="alert" data-submit-error hidden></p>
            <div class="citizen-step-actions">
                <button type="button" class="btn-secondary" data-step-previous hidden>Anterior</button>
                <button type="submit" class="btn-primary" data-submit>Enviar reporte<x-icon name="arrow" /></button>
            </div>
        </section>
    </form>
</div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin="anonymous"></script>
@endpush
