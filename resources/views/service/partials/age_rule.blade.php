<form action="{{ URL::route("admin.rules.create") }}" method="post">
    {!! csrf_field() !!}
<i>AGE</i>

<div>
  <input id="name" name="name" type="text">
    <label for="name" class="block">Name of rule</label>
</div>
<div class="dob-input relative">
    <input type="checkbox" class="styled-checkbox" id="pregnancy" name="pregnancy">
    <label for="pregnancy">Award vouchers for a pregnancy?</label>
</div>
<div class="dob-input">
  <input id="min_year" name="min_year" type="number" pattern="[0-9]*" min="0">
    <label for="min_year" class="block">Min Year</label>
</div>
<div class="dob-input">
    <input id="min_month" name="min_month" type="number" pattern="[0-9]*" min="0">
    <label for="min_month" class="block">Min Month</label>
</div>
<br>
<div class="dob-input">
    <input id="max_year" name="max_year" type="number" pattern="[0-9]*" min="0">
    <label for="max_year" class="block">Max Year (opt)</label>
</div>
<div class="dob-input">
    <input id="max_month" name="max_month" type="number" pattern="[0-9]*" min="0">
    <label for="max_month" class="block">Max Month (opt)</label>
</div>
<p>OR</p>
<div class="dob-input relative">
    <input type="checkbox" class="styled-checkbox" id="stop_at_primary" name="stop_at_primary" checked>
    <label for="stop_at_primary">STOP AT PRIMARY</label>
</div>
<div class="dob-input relative">
    <input type="checkbox" class="styled-checkbox" id="stop_at_secondary" name="stop_at_secondary" checked>
    <label for="stop_at_secondary">STOP AT SECONDARY</label>
</div>
<br>
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
<input type="hidden" name="rule_type" value="{{ $new_rule_type }}">
<button class="long-button submit" type="submit">Save</button>
</form>

<script>
$('#pregnancy').change(
  function(){
      if ($(this).is(':checked')) {
        $('#min_year').attr('disabled', 'disabled');
        $('#min_month').attr('disabled', 'disabled');
        $('#max_year').attr('disabled', 'disabled');
        $('#max_month').attr('disabled', 'disabled');
      } else {
        $('#min_year').removeAttr('disabled');
        $('#min_month').removeAttr('disabled');
        $('#max_year').removeAttr('disabled');
        $('#max_month').removeAttr('disabled');
      }
  });
</script>
