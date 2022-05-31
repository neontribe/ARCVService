<div class="age-input-container">
    <div class="age-input">
        <label for="age-year"
               class="block"
        >age</label>
        <input id="age-year"
               name="age-year"
               type="number"
               pattern="[0-9]*" min="0"
               max="{{ $max || 120 }}"
        >
    </div>
</div>

