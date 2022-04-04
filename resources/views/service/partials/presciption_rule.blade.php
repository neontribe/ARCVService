<form action="{{ URL::route("admin.rules.create") }}" method="post">
    {!! csrf_field() !!}
<i>PRESCIPTION</i>

<div>
  <input id="name" name="name" type="text">
    <label for="name" class="block">Name of rule</label>
</div>
<div class="dob-input relative">
    <input type="checkbox" class="styled-checkbox" id="has_prescription" name="has_prescription" checked>
    <label for="has_prescription">Person has prescription</label>
</div>
<div class="dob-input">
    <input id="num_vouchers" name="num_vouchers" type="number" pattern="[0-9]*" min="0">
    <label for="num_vouchers" class="block">Number of vouchers</label>
</div>
<button class="long-button submit" type="submit">Save</button>
</form>
