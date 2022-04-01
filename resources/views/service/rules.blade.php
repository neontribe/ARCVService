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
                      {{-- <th>Entity</th>
                      <th>Value</th>
                      <th>Min Age<br>(y m)</th>
                      <th>Max Age<br>(y m)</th>
                      <th>Has prescription</th>
                      <th>At Primary School</th>
                      <th>At Secondary School</th>
                      <th>Family exists</th> --}}
                  </tr>
                </thead>
                  @foreach ($rules as $key => $rule)
                        <tbody>
                            <tr>
                                <td>{{ $rule->id }}</td>
                                <td>{{ $rule->name }}</td>
                                <td>{{ $rule->describe($rule) }}</td>
                                {{-- <td>{{ $rule->entity }}</td>
                                <td>{{ $rule->value }}</td>
                                <td>{{ $rule->min_year }} {{ $rule->min_month }}</td>
                                <td>{{ $rule->max_year }} {{ $rule->max_month }}</td>
                                <td>{{ $rule->has_prescription }}</td>
                                <td>{{ $rule->is_at_primary_school }}</td>
                                <td>{{ $rule->is_at_secondary_school }}</td>
                                <td>{{ $rule->family_exists }}</td> --}}
                            </tr>
                  @endforeach
                </tbody>
            </table>

            <h1>Add new rule</h1>
            <h5>Select rule type</h5>
            <i>Brief explanation of rule types somewhere</i>
            {{-- <div style="display:flex; flex-direction:row; justify-content:space-evenly;"> --}}
                <div class="select">
                    <select name="new_rule_type" id="new_rule_type">
                        <option value="age">age</option>
                        <option value="family">family</option>
                        <option value="prescription">prescription</option>
                        <option value="school">school</option>
                    </select>
                </div>
            {{-- </div> --}}
            <br>
            <i>Pretend they've chosen age</i>

            <div class="dob-input">
              <input id="min_year" name="min_year" type="number" pattern="[0-9]*" min="0">
                <label for="min_year" class="block">Min Year</label>
            </div>
            <div class="dob-input">
                <input id="min_month" name="min_month" type="number" pattern="[0-9]*" min="0">
                <label for="min_month" class="block">Min Month</label>
            </div>

            <div class="dob-input">
                <input id="max_year" name="max_year" type="number" pattern="[0-9]*" min="0">
                <label for="max_year" class="block">Max Year (opt)</label>
            </div>
            <div class="dob-input">
                <input id="max_month" name="max_month" type="number" pattern="[0-9]*" min="0">
                <label for="max_month" class="block">Max Month (opt)</label>
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
                <input type="text" class="styled-checkbox" id="warning_message" name="warning_message" pattern="[A-Za-z]*">
                <label for="warning_message">Warning message</label>
            </div>

    </div>

@endsection
