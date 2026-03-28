<?php

namespace App\View\Components;

use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\View;

class GeneralInput extends Component
{
    /**
     * @param string $name Base field name — used for old() lookup and as the id attribute.
     * @param string $label Human-readable label rendered above the field.
     * @param string $type HTML input type. Defaults to 'text'.
     * @param int|string|null $modelId When editing an existing record, pass the model's PK.
     *                                         The rendered name becomes name[modelId] and the old()
     *                                         lookup uses dot-notation (name.modelId).
     * @param string|null $value Pre-filled value for edit pages (the model attribute).
     *                                         Falls back to old() automatically on a failed submission.
     * @param string|null $errorKey Explicit Validator key to check against $errors and to
     *                                         fetch messages from. Defaults to the computed key
     *                                         (name.modelId when modelId is set, otherwise name).
     *                                         Pass this when the validation key doesn't follow the
     *                                         same array-naming convention as the input (see the
     *                                         pri_carer_language field in voucher_collectors).
     * @param string|null $errorMessage Single custom error string.  When null, the real
     *                                         messages from $errors->get($errorKey) are used, which
     *                                         mirrors the existing @includeWhen('store.partials.errors')
     *                                         behaviour for fields that carry server messages.
     * @param string|null $alertId Optional id attribute forwarded to the errors partial
     *                                         (matches the existing 'id' => 'carer-alert' usage).
     * @param string|null $filter Named sanitiser applied via JS on the 'input' event.
     *                                         Supported values: 'alpha-space' (/[^a-z ]/ — strips
     *                                         anything that isn't a lowercase letter or space, which
     *                                         is what the language fields currently do inline).
     * @param string|null $placeholder Optional placeholder text.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type = 'text',
        public readonly int|string|null $modelId = null,
        public readonly ?string $value = null,
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
        // Array-style name when editing an existing record:   field[123]
        // Dot-notation old() key for the same case:           field.123
        $inputName = $this->modelId !== null
            ? "{$this->name}[{$this->modelId}]"
            : $this->name;

        $oldKey = $this->modelId !== null
            ? "{$this->name}.{$this->modelId}"
            : $this->name;

        // ── Error key ────────────────────────────────────────────────────
        // Callers can override when the validation key doesn't match the
        // rendered name (e.g. pri_carer_language uses a flat key even in
        // edit mode where the name is array-style).
        $resolvedErrorKey = $this->errorKey ?? $oldKey;

        // ── Value ────────────────────────────────────────────────────────
        // On a failed submission old() wins so the user's input is sticky.
        // On a clean edit page $this->value (the model attribute) is shown.
        // On a clean create page both are null → empty field.
        $displayValue = old($oldKey) ?? $this->value;

        // ── Errors ───────────────────────────────────────────────────────
        $hasError = $errors->has($resolvedErrorKey);
        // Prefer an explicit single message; fall back to whatever the
        // Validator produced (mirrors the original @includeWhen usage).
        $errorMessages = $this->errorMessage !== null
            ? [$this->errorMessage]
            : $errors->get($resolvedErrorKey);

        return view('components.general-input', [
            'inputName' => $inputName,
            'hasError' => $hasError,
            'displayValue' => $displayValue,
            'errorMessages' => $errorMessages,
        ]);
    }
}
