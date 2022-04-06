@extends('service.layouts.app')

@section('content')

    <div id="container">

        @include('service.includes.sidebar')

        <div id="main-content">
            <h1>Edit Rules</h1>
            <table class="table table-striped">
                <thead>
                  <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Description</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($rules as $key => $rule)

                            <tr>
                                <td>{{ $rule->id }}</td>
                                <td>{{ $rule->name }}</td>
                                <td>{{ $rule->describe($rule) }}</td>
                            </tr>
                  @endforeach
                </tbody>
            </table>

            <h1>Add new rule</h1>


            <p>If family exists award 10 vouchers per family</p>
            <p>If family exists award 5 vouchers per member of family</p>
            <p>If person has prescription award 3 vouchers</p>
            <p></p>
            <p></p>
            <p>If child is under X years award Z vouchers</p>
            <p>If child is over X years award Z vouchers</p>
            <p>If child is over X years and under Y years award Z vouchers</p>
            <p>If child is over X years and not at primary school award Z vouchers</p>
            <p>If child is at primary school do not award vouchers unless</p>
            <p>If child is at secondary school do not award vouchers unless</p>





            <h5>Select rule type</h5>

            <i>Brief explanation of rule types somewhere</i>
            <form action="{{ URL::route("admin.rules.new", ['rule_type' => 'age']) }}" method="get" id='select_rule_type' autocomplete="off">
                <div class="select">
                    <select name="new_rule_type" id="new_rule_type">
                        <option value=0 disabled selected>Please Select</option>
                        <option value="age">Age</option>
                        <option value="family">Family</option>
                        <option value="prescription">Prescription</option>
                        <option value="school">School</option>
                    </select>
                </div>
            </form>
            <br>
            @includeWhen($new_rule_type === 'age', 'service.partials.age_rule')
            @includeWhen($new_rule_type === 'family', 'service.partials.family_rule')
            @includeWhen($new_rule_type === 'prescription', 'service.partials.presciption_rule')
            @includeWhen($new_rule_type === 'school', 'service.partials.school_rule')
    </div>

    <script>
    $('#new_rule_type').on('change', function() {
      console.log($(this).val());
      $('#select_rule_type').trigger('submit');
    })
    </script>
@endsection
