<?php

namespace App\View\Components;

use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\View;

class GeneralSelect extends Component
{
    /**
     * @param  string           $name              Base field name — also used as the id attribute.
     * @param  string           $label             Human-readable label rendered above the select.
     * @param  array            $options           Associative array of value => label pairs to render
     *                                             as <option> elements. The caller is responsible for
     *                                             building this — including any config() lookups and
     *                                             __() / @lang() translation.
     * @param  int|string|null  $modelId           When editing an existing record, pass the model PK.
     *                                             The rendered name becomes name[modelId] and the
     *                                             old() key uses dot-notation (name.modelId).
     *                                             Omit (null) for flat names such as 'eligibility-hsbs'.
     * @param  mixed            $value             The model's currently stored value for this field.
     *                                             old() always takes precedence on a failed submission.
     *                                             Pass null on create pages.
     * @param  string|null      $oldKey            Explicit override for the old() / $errors lookup key.
     *                                             Defaults to name.modelId (edit) or name (create).
     *                                             Override when the validator key differs from the
     *                                             rendered name — e.g. a flat validator key despite an
     *                                             array-style input name.
     * @param  string|null      $errorKey          Explicit $errors key. Defaults to $oldKey.
     * @param  string|null      $errorMessage      Single fixed error string forwarded to the errors
     *                                             partial. When null, real validator messages are used.
     * @param  string|null      $alertId           Optional id attribute forwarded to the errors partial.
     * @param  string           $placeholder       Label for the empty/default option. Default: 'Please select'.
     * @param  int|string       $placeholderValue  Value attribute of the placeholder option. Default: '0'.
     * @param  bool             $warning           When true, renders a <mark> warning below the select.
     *                                             The caller evaluates the condition and passes the result,
     *                                             keeping field-specific logic out of the component.
     * @param  string|null      $warningMessage    Text displayed inside the <mark> tag when $warning is true.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly array $options,
        public readonly int|string|null $modelId = null,
        public readonly mixed $value = null,
        public readonly ?string $oldKey = null,
        public readonly ?string $errorKey = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $alertId = null,
        public readonly string $placeholder = 'Please select',
        public readonly int|string $placeholderValue = '0',
        public readonly bool $warning = false,
        public readonly ?string $warningMessage = null,
    ) {
    }

    public function render(): View
    {
        /** @var ViewErrorBag $errors */
        $errors = session('errors', new ViewErrorBag());

        // ── Name attribute ────────────────────────────────────────────────
        $inputName = $this->modelId !== null
            ? "{$this->name}[{$this->modelId}]"
            : $this->name;

        // ── old() key ─────────────────────────────────────────────────────
        // Dot-notation for array-style names, flat for everything else.
        // Explicit $oldKey overrides both.
        $resolvedOldKey = $this->oldKey ?? (
        $this->modelId !== null
            ? "{$this->name}.{$this->modelId}"
            : $this->name
        );

        // ── Resolved value ────────────────────────────────────────────────
        // old() wins on a failed submission so the user's selection is sticky.
        $resolvedValue = old($resolvedOldKey) ?? $this->value;

        // ── Placeholder selected ──────────────────────────────────────────
        // True when nothing has been chosen yet, or when the user explicitly
        // submitted the placeholder value (loose comparison handles "0" == 0).
        // phpcs:ignore Universal.Operators.StrictComparisons
        $placeholderSelected = $resolvedValue === null
            || $resolvedValue == $this->placeholderValue;

        // ── Error handling ────────────────────────────────────────────────
        $resolvedErrorKey = $this->errorKey ?? $resolvedOldKey;
        $hasError         = $errors->has($resolvedErrorKey);
        $errorMessages    = $this->errorMessage !== null
            ? [$this->errorMessage]
            : $errors->get($resolvedErrorKey);

        return view('components.general-select', [
            'inputName'           => $inputName,
            'resolvedValue'       => $resolvedValue,
            'placeholderSelected' => $placeholderSelected,
            'hasError'            => $hasError,
            'errorMessages'       => $errorMessages,
        ]);
    }
}
