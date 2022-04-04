<form action="{{ URL::route("admin.rules.create") }}" method="post">
    {!! csrf_field() !!}
<i>FAMILY</i>

<div>
  <input id="name" name="name" type="text">
    <label for="name" class="block">Name of rule</label>
</div>
<div class="dob-input relative">
    <input type="radio" class="styled-checkbox" id="family_exists_each" name="family_exists" checked>
    <label for="family_exists_each">Award vouchers per <i>eligible family member</i> if family exists?</label>
</div>
<div class="dob-input relative">
    <input type="radio" class="styled-checkbox" id="family_exists_total" name="family_exists" checked>
    <label for="family_exists_total">Award vouchers per <i>family</i> if family exists?</label>
</div>
<div class="dob-input">
    <input id="num_vouchers" name="num_vouchers" type="number" pattern="[0-9]*" min="0">
    <label for="num_vouchers" class="block">Number of vouchers</label>
</div>
<div class="dob-input relative">
    <input type="checkbox" class="styled-checkbox" id="has_warning" name="has_warning" checked>
    <label for="has_warning">Show warning?</label>
</div>
<div class="dob-input relative">
    <input type="number" class="styled-checkbox" id="warning_months" name="warning_months" pattern="[0-9]*" min="0">
    <label for="warning_months">Number of months before expiry to show warning</label>
</div>
<div class="dob-input relative">
    <input type="text" class="styled-checkbox" id="warning_message" name="warning_message">
    <label for="warning_message">Warning message</label>
</div>
<button class="long-button submit" type="submit">Save</button>
</form>
