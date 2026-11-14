@props(['field', 'id' => null])
@error($field)
    <p id="{{ $id ?? $field.'-error' }}" class="mt-2 text-base text-rose-700" role="alert">{{ $message }}</p>
@enderror
