<?php

namespace App\View\Components;

use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\View;

class SecureInput extends Component
{
    /**
     * @param  string       $name         Input name attribute — also used as the old() / $errors key.
     * @param  string       $label        Human-readable label rendered above the field.
     * @param  bool         $filled       Pass true on edit pages when a value is already stored.
     * @param  string|null  $placeholder  Override the auto-generated placeholder text.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly bool $filled = false,
        public readonly ?string $placeholder = null,
    ) {
    }

    public function render(): View
    {
        /** @var ViewErrorBag $errors */
        $errors = session('errors', new ViewErrorBag());

        return view('components.secure-input', [
            'hasError' => $errors->has($this->name),
            'oldValue' => old($this->name),
            'placeholder' => $this->placeholder ?? (
                $this->filled
                    ? str_repeat('•', 16)
                    : 'Enter ' . strtolower($this->label) . '…'
            ),
        ]);
    }
}
