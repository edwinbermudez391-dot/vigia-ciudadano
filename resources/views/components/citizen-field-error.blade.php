@props(['field', 'message' => null])
@php
    $error = $message ?? $errors->first($field);
    if ($error && $field === 'damage_photo') {
        $error = 'Agrega una foto JPEG, PNG o WebP de hasta 5 MB y 4000 × 4000 píxeles.';
    }
@endphp
<p id="{{ $field }}-error" class="citizen-error" role="alert" data-field-error="{{ $field }}" @if (!$error) hidden @endif>{{ $error }}</p>
