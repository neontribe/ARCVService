<?php

namespace App\View\Components;

use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\View;

class PasswordInput extends Component
{
    /**
     * A labelled text input with phantom-prefill behaviour for password fields.
     *
     * Create mode
     *   • Empty on first load, unmasked throughout.
     *   • On a failed submission the entered value is re-displayed unmasked
     *     (old() wins, same as GeneralInput).
     *
     * Edit mode  ($existingPassword = true)
     *   • On first load the field is in "phantom-prefill" state: name is
     *     suppressed (nothing submitted), value is empty, placeholder shows
     *     '••••••••'.  The backend interprets an absent field as "unchanged".
     *   • First keystroke switches to virgin state: name is restored, bullets
     *     are cleared, typed text is visible.
     *   • Pressing Escape reverts to phantom-prefill state.
     *   • On a failed submission (old() has a value) the field starts in
     *     virgin state so the user's input is sticky — same as GeneralInput.
     *
     * @param string $name Base field name — id attribute and old() key.
     * @param string $label Human-readable label rendered above the field.
     * @param int|string|null $modelId PK when editing; produces name[id] / old key name.id.
     * @param bool $existingPassword True when the record already has a stored password.
     * @param string|null $errorKey Override when the validation key differs from the
     *                                           rendered name (mirrors GeneralInput behaviour).
     * @param string|null $errorMessage Single custom error string; falls back to $errors->get().
     * @param string|null $alertId Forwarded as id= to the errors partial.
     * @param string|null $filter Named JS sanitiser (e.g. 'alpha-space').
     *                                           Applied via data-input-filter on the 'input' event.
     * @param string|null $placeholder Placeholder shown in virgin state only.
     *                                           The phantom-prefill placeholder ('••••••••') is
     *                                           always used in prefilled state regardless of this value.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly int|string|null $modelId = null,
        public readonly bool $existingPassword = false,
        public readonly ?string $errorKey = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $alertId = null,
        public readonly ?string $filter = null,
        public readonly ?string $placeholder = null,
    ) {
    }

    public function render(): View
    {
        /** @var ViewErrorBag $errors */
        $errors = session('errors', new ViewErrorBag());

        // ── Name & old() key ──────────────────────────────────────────────
        $inputName = $this->modelId !== null
            ? "{$this->name}[{$this->modelId}]"
            : $this->name;

        $oldKey = $this->modelId !== null
            ? "{$this->name}.{$this->modelId}"
            : $this->name;

        // ── Error key ─────────────────────────────────────────────────────
        $resolvedErrorKey = $this->errorKey ?? $oldKey;

        // ── Phantom-prefill state ─────────────────────────────────────────
        // Active only when editing a record with an existing password AND
        // the user hasn't re-entered anything (no old() value from a failed
        // submission).  A failed submission must always show the old() value
        // unmasked so the user can correct it.
        $oldValue = old($oldKey);
        $isPrefilled = $this->existingPassword && $oldValue === null;

        // ── Display value ─────────────────────────────────────────────────
        // Phantom state: field is visually empty (bullets come from placeholder).
        // Virgin state:  old() value on failed submit, otherwise empty.
        $displayValue = $isPrefilled ? null : $oldValue;

        // ── Errors ────────────────────────────────────────────────────────
        $hasError = $errors->has($resolvedErrorKey);
        $errorMessages = $this->errorMessage !== null
            ? [$this->errorMessage]
            : $errors->get($resolvedErrorKey);

        return view('components.password-input', [
            'inputName' => $inputName,
            'hasError' => $hasError,
            'displayValue' => $displayValue,
            'errorMessages' => $errorMessages,
            'isPrefilled' => $isPrefilled,
        ]);
    }
}
