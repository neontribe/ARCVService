@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>{{ $sponsor->name }}</h1>
        @if (Session::get('message'))
            <div class="alert alert-success">
                {{ Session::get('message') }}
            </div>
        @endif
        <form role="form" method="POST" action="{{ route('admin.sponsors.update', ['id' => $sponsor->id]) }}">
          @method('put')
          @csrf
          <input type="hidden" id="id" name="id" value="{{ $sponsor->id }}">
          <table class="table table-striped">
              <thead>
                  <tr>
                      <th>Rule</th>
                      <th>Details</th>
                      <th>Value</th>
                  </tr>
              </thead>
              <tbody>
                  <tr>
                      <td>Household exists</td>
                      <td>Amount awarded for first carer</td>
                      <td><input type="number" name="householdExistsValue" id="householdExistsValue" value="{{ $householdExistsValue[0] ?? 0 }}"
                        min="0" class="{{ $errors->has('householdExistsValue') ? 'has-error' : '' }}" required>
                      </td>
                  </tr>
                  <tr>
                      <td>Household member</td>
                      <td>Amount awarded for each additional household member</td>
                      <td><input type="number" name="householdMemberValue" id="householdMemberValue" value="{{ $householdMemberValue[0] ?? 0 }}"
                        min="0" class="{{ $errors->has('householdMemberValue') ? 'has-error' : '' }}" required>
                      </td>
                  </tr>
              </tbody>
          </table>
          <td><button type="submit" id="save">Save</button></td>
      </form>
    </div>

</div>

@endsection
