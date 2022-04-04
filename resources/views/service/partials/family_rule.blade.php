<form action="{{ URL::route("admin.rules.create") }}" method="post">
    {!! csrf_field() !!}
<i>FAMILY</i>

<div>
  <input id="name" name="name" type="text">
    <label for="name" class="block">Name of rule</label>
</div>
<div class="dob-input relative">
    <input type="radio" class="styled-checkbox" value="family_exists_each" name="family_exists" id="family_exists">
    <label for="family_exists_each">Award vouchers per <i>eligible family member</i> if family exists?</label>
</div>
<div class="dob-input relative">
    <input type="radio" class="styled-checkbox" value="family_exists_total" name="family_exists" id="family_exists">
    <label for="family_exists_total">Award vouchers per <i>family</i> if family exists?</label>
</div>
<div class="dob-input">
    <input id="num_vouchers" name="num_vouchers" type="number" pattern="[0-9]*" min="0">
    <label for="num_vouchers" class="block">Number of vouchers</label><span id='who_gets_vouchers'></span>
</div>
<input type="hidden" name="rule_type" value="{{ $new_rule_type }}">
<button class="long-button submit" type="submit">Save</button>
</form>

<script>
  $('input[name="family_exists"]')
    .change(function (){
        var val = $(this).val();
        if (val === 'family_exists_total') {
          $('#who_gets_vouchers').text(' per family');
        } else {
          $('#who_gets_vouchers').text(' per family member');
        }
  });
</script>
