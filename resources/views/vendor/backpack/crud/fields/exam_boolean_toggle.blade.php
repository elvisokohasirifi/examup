@php
    $value = old($field['name'], $field['value'] ?? $field['default'] ?? false);
    $isChecked = filter_var($value, FILTER_VALIDATE_BOOL);
    $fieldId = 'exam-boolean-toggle-'.str_replace(['[', ']'], '-', $field['name']);
@endphp

@include('crud::fields.inc.wrapper_start')
    <div class="form-check d-flex align-items-center mb-0">
        <input type="hidden" name="{{ $field['name'] }}" value="0">
        <input
            id="{{ $fieldId }}"
            type="checkbox"
            name="{{ $field['name'] }}"
            value="1"
            class="form-check-input"
            @checked($isChecked)
        >
        <label class="form-check-label ms-2" for="{{ $fieldId }}">{{ $field['label'] }}</label>
    </div>

    @if (isset($field['hint']))
        <p class="help-block ms-2">{{ $field['hint'] }}</p>
    @endif
@include('crud::fields.inc.wrapper_end')
