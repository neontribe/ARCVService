<div class="dob-input-container">
    <div class="dob-input">
        <label for="dob-month" class="block">Month</label>
        <input id="dob-month" name="dob-month" type="number" pattern="[0-9]*" min="0" max="12">
    </div>
    <div class="dob-input">
        <label for="dob-year" class="block">Year</label>
        <input id="dob-year" name="dob-year" type="number" pattern="[0-9]*" min="0"
               max="{{ Carbon\Carbon::now()->year }}">
    </div>
    @if ( $verifying )
        <div class="dob-input relative">
            <input type="checkbox" class="styled-checkbox" id="dob-verified" name="dob-verified">
            <label for="dob-verified">ID Checked</label>
        </div>
    @endif
</div>