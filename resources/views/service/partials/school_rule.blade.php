<form action="{{ URL::route("admin.rules.create") }}" method="post">
    {!! csrf_field() !!}
<i>SCHOOL</i>

<div>
  <input id="name" name="name" type="text">
    <label for="name" class="block">Name of rule</label>
</div>
<div class="dob-input relative">
    <input type="radio" class="styled-checkbox" id="child_at_school_primary" name="child_at_school" checked>
    <label for="child_at_school_primary">Child is at primary school</label>
</div>
<div class="dob-input relative">
    <input type="radio" class="styled-checkbox" id="child_at_school_secondary" name="child_at_school" checked>
    <label for="child_at_school_secondary">Child is at secondary school</label>
</div>

<div class="dob-input">
  <input id="month_school_start" name="month_school_start" type="number" pattern="[0-9]*" min="0" min="12" value='9'>
    <label for="month_school_start" class="block">Month school starts</label>
</div>

<p>Minimum in years and months of child in month school start</p>
<div class="dob-input">
  <input id="age_year_school_start" name="age_year_school_start" type="number" pattern="[0-9]*" min="0">
    <label for="age_year_school_start" class="block">Year</label>
</div>
<div class="dob-input">
    <input id="age_month_school_start" name="age_month_school_start" type="number" pattern="[0-9]*" min="0">
    <label for="age_month_school_start" class="block">Month</label>
</div>

<div class="dob-input">
    <input id="num_vouchers" name="num_vouchers" type="number" pattern="[0-9]*" min="0">
    <label for="num_vouchers" class="block">Number of vouchers</label>
</div>
<div class="dob-input">
    <input id="except_if_rule_id" name="except_if_rule_id" type="number" pattern="[0-9]*" min="0">
    <label for="except_if_rule_id" class="block">Exception rule ID</label>
    <span>Ignore this rule if another family member fulfils the exception rule</span>
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
