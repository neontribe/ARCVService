@props([
    'name',
    'label',
    'filled'      => false,
    'hasError',
    'oldValue',
    'placeholder',
    'removeName',
])

<div>

    <label for="{{ $name }}">
        {{ $label }}
        @if ($filled && ! $hasError)
            <small>(already set)</small>
        @endif
    </label>

    <input type="password"
           id="{{ $name }}"
           name="{{ $name }}"
           autocomplete="new-password"
           spellcheck="false"
           placeholder="{{ $placeholder }}"
           value="{{ $oldValue }}"
           @if ($filled) data-secure-remove="{{ $removeName }}" @endif
           @if ($hasError) {{ $attributes->merge(['class' => 'invalid']) }} @endif
    />

    @if ($filled)
        {{--
            Disabled by default so it is excluded from the request.
            JS enables it (and disables the password input) only when
            the user has touched the field and left it empty.
            Re-enabled server-side when old() signals a failed submission
            where the user had already cleared the field.
        --}}
        <input type="hidden"
               name="{{ $removeName }}"
               value="1"
            {{ old($removeName) ? '' : 'disabled' }}
        />
    @endif

    @includeWhen(
        $hasError,
        'store.partials.errors',
        ['error_array' => $errors->get($name)]
    )

</div>

@once
    <script>
        (function () {
            // Per-input dirty flag: keyed by input.name
            let dirty = {};

            function revert(input) {
                input.value = '';
                dirty[input.name] = false;
            }

            document.addEventListener('input', function (e) {
                let input = e.target;
                if (!input.dataset.secureRemove) return;
                dirty[input.name] = true;
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                let input = e.target;
                if (!input.dataset.secureRemove) return;
                revert(input);
            });

            document.addEventListener('submit', function (e) {
                let form = e.target;

                form.querySelectorAll('[data-secure-remove]').forEach(function (input) {
                    let removeInput = form.querySelector(
                        'input[name="' + input.dataset.secureRemove + '"]'
                    );

                    if (!dirty[input.name]) {
                        // Untouched (or reverted) — exclude entirely
                        input.disabled = true;
                    } else if (input.value === '') {
                        // Touched and cleared — send the remove indicator, not the field
                        input.disabled = true;
                        if (removeInput) removeInput.disabled = false;
                    }
                    // Touched with a value — submit normally; both defaults already correct
                });
            });
        }());
    </script>
@endonce
