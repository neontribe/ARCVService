{{--
    general-input.blade.php
    ════════════════════════════════════════════════════════════════════════
    Renders a labelled text input with optional error display.

    Props (passed from the component class via render()):
        inputName     – resolved name attribute (may be array-style: field[123])
        hasError      – bool  — whether $errors contains the resolved key
        displayValue  – string|null — old() value or model value
        errorMessages – array  — messages to forward to store.partials.errors

    Props declared here (consumed by callers directly in the blade tag):
        name          – base field name, used for id= and label for=
        label         – visible label text
        type          – input type, default 'text'
        filter        – named JS sanitiser ('alpha-space' supported)
        placeholder   – optional placeholder text
        alertId       – optional id forwarded to the errors partial
--}}

@props([
    'name',
    'label',
    'type'        => 'text',
    'filter'      => null,
    'placeholder' => null,
    'alertId'     => null,
    // Injected by the component class:
    'inputName',
    'hasError',
    'displayValue',
    'errorMessages',
])

<div>
    <label for="{{ $name }}">{{ $label }}</label><br/>

    <input
        id="{{ $name }}"
        name="{{ $inputName }}"
        type="{{ $type }}"
        @class(['invalid' => $hasError])
        autocomplete="off"
        autocorrect="off"
        spellcheck="false"
        value="{{ $displayValue }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($filter) data-input-filter="{{ $filter }}" @endif
    /><br/>

    @includeWhen(
        $hasError,
        'store.partials.errors',
        array_filter([
            'error_array' => $errorMessages,
            'id' => $alertId,
        ])
    )
</div>

{{-- Filter listener extracted to a shared partial so x-password-input can
     include the same file without duplicating the script.  The @once key
     inside the partial guarantees one registration per page regardless of
     how many components include it. --}}
@include('components.partials.input-filter')
