@props([
    'name',
    'label',
    'filled'      => false,
    'hasError',
    'oldValue',
    'placeholder',
])

<div>

    <label for="{{ $name }}" class="control-label">
        {{ $label }}
        @if ($filled && ! $hasError)
            <small class="text-success">(already set)</small>
        @endif
    </label>

    <input type="password"
           id="{{ $name }}"
           name="{{ $name }}"
           autocomplete="new-password"
           spellcheck="false"
           placeholder="{{ $placeholder }}"
           value="{{ $oldValue }}"
    @if ($hasError)
        {{ $attributes->merge(['class' => 'invalid']) }}
        @endif
    />

    @includeWhen(
            $hasError,
            'store.partials.errors',
            ['error_array' => ['Please check the entry']]
            )

</div>
