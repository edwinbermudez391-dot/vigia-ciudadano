@extends('layouts.app')
@section('title', 'Reportar un problema — Vigía Ciudadano')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="anonymous">
@endpush

@section('content')
<div class="citizen-content citizen-report-page">
    <header class="citizen-heading">
        <h1>Vamos a reportar un problema</h1>
    </header>
    @if ($duplicateTicket = session('duplicate_report_ticket'))
        <section class="citizen-notice" aria-labelledby="duplicate-report-title">
            <h2 id="duplicate-report-title">Este problema ya fue reportado</h2>
            <p>Ya hay un reporte activo de este tipo en el lugar indicado. Puedes seguir su avance con el radicado <strong>{{ $duplicateTicket }}</strong>.</p>
            <div class="citizen-actions">
                <a class="btn-primary" href="{{ route('reports.show', ['ticket_code' => $duplicateTicket]) }}">Consultar el reporte existente</a>
            </div>
            <p>Si el tipo o el lugar no corresponden, corrígelos en el formulario y vuelve a agregar la foto.</p>
        </section>
    @endif
    <noscript><p class="citizen-notice">Puedes completar el formulario en esta página. Activa JavaScript para elegir el lugar del problema.</p></noscript>
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
        <li aria-current="step"><span aria-hidden="true">1</span><strong>El problema</strong></li>
        <li><span aria-hidden="true">2</span><strong>El lugar</strong></li>
        <li><span aria-hidden="true">3</span><strong>Revisar y enviar</strong></li>
    </ol>
    <p class="citizen-step-status" role="status" data-step-status hidden></p>

    <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data"
          id="report-form" class="citizen-report-form" data-citizen-report
          data-nearby-url="{{ url('/api/v1/reports/nearby') }}" data-subscribe-url="{{ url('/api/v1/reports') }}"
          data-detail-url="{{ url('/reportes') }}" data-can-subscribe="{{ auth()->check() ? 'true' : 'false' }}"
          data-nearby-radius="{{ \App\Services\ReportIntake::DUPLICATE_RADIUS_METERS }}">
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
            <div>
                <label for="description">Cuéntanos qué pasa</label>
                <textarea id="description" name="description" rows="4" required minlength="15" maxlength="1000" data-counter-textarea
                          placeholder="Ej.: Hay un hueco grande frente a mi casa. Se llena de agua y dificulta el paso."
                          aria-describedby="description-help description-error" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}">{{ old('description') }}</textarea>
                <span id="description-help" data-counter-output class="sr-only"></span>
                <x-citizen-field-error field="description" />
            </div>
            <div>
                <p id="photo-title" class="citizen-label">Foto del problema </p>
                <div class="citizen-photo-actions" role="group" aria-labelledby="photo-title">
                <label for="damage_photo" class="citizen-photo-picker"><x-icon name="camera" /><span data-photo-label>Agregar foto</span>
                    <input id="damage_photo" name="damage_photo" type="file" accept="image/jpeg,image/png,image/webp"
                           class="sr-only" data-photo-input required aria-describedby="damage_photo-error" aria-invalid="{{ $errors->has('damage_photo') ? 'true' : 'false' }}">
                </label>
                <button type="button" class="btn-secondary" data-photo-camera hidden><x-icon name="camera" />Tomar foto</button>
                <button type="button" class="btn-secondary" data-remove-photo hidden>Quitar foto</button>
                </div>
                <p class="citizen-help" role="status" data-photo-status></p>
                <img data-photo-preview class="citizen-photo-preview" alt="Vista previa de la foto que enviarás" hidden>
                <x-citizen-field-error field="damage_photo" />
            </div>
            <div class="citizen-step-actions" data-step-actions hidden>
                <button type="button" class="btn-primary" data-step-next @disabled($categories->isEmpty())>Continuar<x-icon name="arrow" /></button>
            </div>
        </section>

        <section class="citizen-card citizen-step" data-report-step="1" aria-labelledby="step-2-title">
            <h2 id="step-2-title" tabindex="-1">2. Tu ubicación</h2>
            <p>Ubícate junto al problema y toca el botón.</p>
            <input type="hidden" name="location_label" value="{{ old('location_label') }}" data-selected-place>
            <div class="citizen-location-choices">
                <button type="button" class="btn-primary" data-geolocate hidden><x-icon name="map" /><span>Usar mi ubicación</span></button>
                <button type="button" class="btn-secondary" data-open-map hidden>Elegir el lugar en el mapa</button>
            </div>
            <p id="location-status" class="citizen-notice" role="status" data-location-status>Elige una opción para ubicar el problema.</p>
            <details class="citizen-map-section" data-map-section hidden>
                <summary>Ver mi ubicación en el mapa</summary>
                <p class="citizen-help">Si el punto no coincide, toca el lugar correcto o centra el mapa sobre él.</p>
                <div class="citizen-map-target">
                    <div id="map" class="citizen-location-map" role="region" aria-label="Mapa para señalar el problema"></div>
                    <span class="citizen-map-crosshair" aria-hidden="true">+</span>
                </div>
                <button type="button" class="btn-secondary" data-use-map-center hidden>Marcar el centro del mapa</button>
            </details>
            <div class="citizen-notice" data-location-confirmation hidden>
                <p data-location-label></p>
                <h3 data-confirm-title>¿El problema está aquí?</h3>
                <div class="citizen-actions">
                    <button type="button" class="btn-primary" data-confirm-location>Sí, este es el lugar</button>
                    <button type="button" class="btn-secondary" data-change-location>Volver a obtener mi ubicación</button>
                </div>
            </div>
            <input id="latitude" name="latitude" type="hidden" value="{{ old('latitude') }}" data-lat-input>
            <input id="longitude" name="longitude" type="hidden" value="{{ old('longitude') }}" data-lng-input>
            <x-citizen-field-error field="latitude" />
            <x-citizen-field-error field="longitude" />
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
            <details class="citizen-contact" @if ($errors->hasAny(['citizen_name', 'citizen_email'])) open @endif>
                <summary>Recibir novedades por correo (opcional)</summary>
                <p class="citizen-help">Estos datos son opcionales y privados.</p>
                <div class="citizen-fields">
                    <div><label for="citizen_name">Nombre (opcional)</label><input id="citizen_name" name="citizen_name" type="text" maxlength="120" value="{{ old('citizen_name') }}" autocomplete="name" aria-describedby="citizen_name-error" aria-invalid="{{ $errors->has('citizen_name') ? 'true' : 'false' }}"><x-citizen-field-error field="citizen_name" /></div>
                </div>
                <div><label for="citizen_email">Correo electrónico (opcional)</label><input id="citizen_email" name="citizen_email" type="email" maxlength="255" value="{{ old('citizen_email') }}" autocomplete="email" aria-describedby="citizen_email-error" aria-invalid="{{ $errors->has('citizen_email') ? 'true' : 'false' }}"><x-citizen-field-error field="citizen_email" /></div>
            </details>
            <div class="citizen-notice" data-duplicate-banner hidden>
                <p>Hay <strong data-duplicate-count></strong> reporte(s) similares cerca.</p>
                <button type="button" class="btn-secondary" data-check-duplicates>Ver reportes cercanos</button>
                <ul class="citizen-address-results" data-duplicates-list hidden></ul>
                <p class="citizen-help">Consulta el reporte existente. Si el tipo o el lugar que elegiste no corresponden al problema, corrígelos antes de enviar.</p>
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
