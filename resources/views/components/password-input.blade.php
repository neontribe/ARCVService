{{--
    password-input.blade.php
    ════════════════════════════════════════════════════════════════════════
    Renders a labelled, unmasked password field with phantom-prefill support.

    The field always uses type="text" so the value is visible to the user.
    Masking in edit mode is achieved through a suppressed name + bullet
    placeholder rather than type="password", which avoids browser autofill
    quirks and the value-loss that comes with type switching.

    ── States ───────────────────────────────────────────────────────────────

    Phantom-prefill  ($isPrefilled = true)
      name=""              nothing submitted; backend treats absence as "unchanged"
      value=""             field appears empty
      placeholder="••••••••"  visual cue that a password exists
      data-pw-prefilled    present — JS hook that triggers virgin transition

    Virgin  ($isPrefilled = false)
      name="{{ $inputName }}"   submitted normally
      value="{{ $displayValue }}"  old() value or empty
      placeholder="{{ $placeholder }}"
      data-pw-prefilled    absent

    Both states carry data-pw-name when the field is prefillable, so the
    Escape handler can revert from virgin → phantom at any point.

    ── Props (declared here — consumed directly from the blade tag) ──────────
        name          – base field name, used for id= and label for=
        label         – visible label text
        filter        – named JS sanitiser ('alpha-space' supported)
        placeholder   – placeholder for virgin state only
        alertId       – optional id forwarded to the errors partial

    ── Props (injected by the component class via render()) ─────────────────
        inputName     – resolved name (may be array-style: field[123])
        hasError      – bool
        displayValue  – string|null
        errorMessages – array
        isPrefilled   – bool
        existingPassword – bool (public prop, auto-injected from class)
--}}

@props([
    'name',
    'label',
    'filter'      => null,
    'placeholder' => null,
    'alertId'     => null,
    // Injected by the component class:
    'inputName',
    'hasError',
    'displayValue',
    'errorMessages',
    'isPrefilled',
    'existingPassword' => false,
])

<div>
    <label for="{{ $name }}">{{ $label }}</label><br/>

    <input
        id="{{ $name }}"
        type="text"
        autocomplete="new-password"
        autocorrect="off"
        spellcheck="false"

        {{-- Phantom state: name suppressed so nothing is submitted.
             Virgin state:  resolved name submitted as normal. --}}
        name="{{ $isPrefilled ? '' : $inputName }}"
        value="{{ $isPrefilled ? '' : ($displayValue ?? '') }}"
        placeholder="{{ $isPrefilled ? '••••••••' : ($placeholder ?? '') }}"

        {{-- data-pw-prefilled: present only in phantom state.
             Removed by JS on first keystroke (virgin transition). --}}
        @if ($isPrefilled) data-pw-prefilled @endif

        {{-- data-pw-name / data-pw-placeholder: present whenever the field is
             prefillable (existingPassword = true), whether currently in phantom
             or virgin state, so Escape can always revert. --}}
        @if ($existingPassword)
            data-pw-name="{{ $inputName }}"
        data-pw-placeholder="{{ $placeholder ?? '' }}"
        @endif

        @if ($filter) data-input-filter="{{ $filter }}" @endif

        @class(['invalid' => $hasError])
    /><br/>

    @includeWhen(
        $hasError,
        'store.partials.errors',
        array_filter([
            'error_array' => $errorMessages,
            'id'          => $alertId,
        ])
    )
</div>

{{-- Shared filter listener (safe to include from both x-general-input and
     x-password-input; the @once key ensures one registration per page). --}}
@include('components.partials.input-filter')

{{-- Phantom-prefill state machine.
     Two delegated listeners on document cover every x-password-input on
     the page with a single registration. --}}
@once('pw-input-js')
    <script>
        (function () {
            const PHANTOM_PLACEHOLDER = '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022'; // ••••••••

            // ── phantom → virgin ────────────────────────────────────────────
            // First keystroke in a phantom field activates it:
            //   • removes the data-pw-prefilled marker
            //   • restores the submitted name from data-pw-name
            //   • restores the virgin-state placeholder from data-pw-placeholder
            document.addEventListener('input', function (e) {
                const el = e.target;
                if (!el.hasAttribute('data-pw-prefilled')) return;

                el.removeAttribute('data-pw-prefilled');
                el.name = el.dataset.pwName;
                el.placeholder = el.dataset.pwPlaceholder;
            });

            // ── virgin → phantom ────────────────────────────────────────────
            // Escape on any prefillable field (data-pw-name present) reverts
            // to phantom state:
            //   • clears the entered value
            //   • suppresses the name so nothing is submitted
            //   • restores the bullet placeholder
            //   • marks the field as phantom again
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;

                const el = e.target;
                if (!el.dataset.pwName) return; // not a prefillable field
                if (el.hasAttribute('data-pw-prefilled')) return; // already phantom

                el.value = '';
                el.name = '';
                el.placeholder = PHANTOM_PLACEHOLDER;
                el.setAttribute('data-pw-prefilled', '');
                el.blur(); // release focus; mirrors typical Escape UX
            });
        }());
    </script>
@endonce
