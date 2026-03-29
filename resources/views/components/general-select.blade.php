{{--
    general-select.blade.php
    ════════════════════════════════════════════════════════════════════════
    Renders a labelled <select> with an optional warning mark and optional
    error display.  Handles both flat and array-style name attributes, and
    both model-value and old() selection across create / edit pages.

    Props declared here (consumed directly in the blade tag by callers):
        name              – base field name; used for id= and label for=
        label             – visible label text
        options           – value => label associative array for <option> elements
        placeholder       – placeholder option label  (default: 'Please select')
        placeholderValue  – placeholder option value  (default: '0')
        warning           – bool; when true renders the warningMessage in <mark>
        warningMessage    – string shown inside <mark> when warning is true
        alertId           – optional id forwarded to the errors partial

    Props injected by the component class:
        inputName           – resolved name attribute (may be array-style)
        resolvedValue       – old() value or model value
        placeholderSelected – bool; whether the placeholder option is selected
        hasError            – bool
        errorMessages       – array of messages for the errors partial
--}}

@props([
    'name',
    'label',
    'options',
    'placeholder'       => 'Please select',
    'placeholderValue'  => '0',
    'alertId'           => null,
    // Injected by the component class:
    'inputName',
    'resolvedValue',
    'placeholderSelected',
    'hasError',
    'errorMessages',
])

<div>
    <label for="{{ $name }}">{{ $label }}</label><br />

    <select id="{{ $name }}"
            name="{{ $inputName }}"
        @class(['invalid' => $hasError])
    >
        <option value="{{ $placeholderValue }}"
            @selected($placeholderSelected)
        >{{ $placeholder }}</option>

        @foreach ($options as $optionValue => $optionLabel)
            {{-- Loose comparison intentional: old() returns strings; stored values may be int or string. --}}
            <option value="{{ $optionValue }}"
                @selected($resolvedValue == $optionValue)
            >{{ $optionLabel }}</option>
        @endforeach
    </select>
    @includeWhen(
        $hasError,
        'store.partials.errors',
        array_filter([
            'error_array' => $errorMessages,
            'id'          => $alertId,
        ])
    )
</div>
