@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false, 'id' => null])
@php $fieldId = $id ?? str_replace(['[', ']'], ['-', ''], $name); @endphp
<div class="form-field">
    <label for="{{ $fieldId }}" class="field-label">{{ $label }} @if ($required)<span aria-hidden="true">*</span>@endif</label>
    <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)
           aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
           @if ($help || $errors->has($name)) aria-describedby="{{ $help ? $fieldId.'-help' : '' }} {{ $errors->has($name) ? $fieldId.'-error' : '' }}" @endif
           {{ $attributes->class(['field-control']) }}>
    @if ($help)<p id="{{ $fieldId }}-help" class="field-help">{{ $help }}</p>@endif
    <x-field-error :field="$name" :id="$fieldId.'-error'" />
</div>
