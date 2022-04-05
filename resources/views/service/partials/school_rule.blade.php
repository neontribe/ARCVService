<form action="{{ URL::route("admin.rules.create") }}" method="post">
    {!! csrf_field() !!}
<i>SCHOOL</i>

<div>
  <input id="name" name="name" type="text">
    <label for="name" class="block">Name of rule</label>
</div>
<div class="dob-input relative">
    <input type="radio" class="styled-checkbox" value='primary' id="child_at_school_primary" name="child_at_school" checked>
    <label for="child_at_school_primary">Exclude children at primary school</label>
</div>
<div class="dob-input relative">
    <input type="radio" class="styled-checkbox" value='secondary' id="child_at_school_secondary" name="child_at_school" checked>
    <label for="child_at_school_secondary">Exclude children at secondary school</label>
</div>
<div class="dob-input">
    <input id="except_if_age" name="except_if_age" type="checkbox">
    <label for="except_if_age" class="block">Exception</label>
    <span>Ignore this rule if family has other members that fulfil any age rule</span>
</div>
<div class="dob-input">
    <input id="except_if_prescription" name="except_if_prescription" type="checkbox">
    <label for="except_if_prescription" class="block">Exception</label>
    <span>Ignore this rule if family has other members that fulfil prescription rule</span>
</div>

<div class="dob-input">
    <input id="num_vouchers" name="num_vouchers" type="number" pattern="[0-9]*" min="0">
    <label for="num_vouchers" class="block">Number of vouchers for school age child if exception rule applies</label>
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
<input type="hidden" name="rule_type" value="{{ $new_rule_type }}">
<button class="long-button submit" type="submit">Save</button>
</form>
