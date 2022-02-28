@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">
        <h1>{{ $centre->name }}</h1>
        @if (Session::get('message'))
            <div class="alert alert-success">
                {{ Session::get('message') }}
            </div>
        @endif
        <form role="form" method="POST" action="{{ route('admin.centres.update') }}">
          @csrf
          <input type="hidden" id="id" name="id" value="{{ $centre->id }}">
          <table class="table table-striped">
              <thead>
                  <tr>
                      <th>Name</th>
                      <th>RVID Prefix</th>
                      <th>Area</th>
                      <th>Form</th>
                      <th>Save</th>
                  </tr>
              </thead>
              <tbody>
                  <tr>
                      <td><input type="text" id="name" name="name" value="{{ $centre->name ?? '' }}"
                        class="{{ $errors->has('name') ? 'has-error' : '' }}" required>
                      </td>
                      <td>{{ $centre->prefix }}</td>
                      <td>{{ $centre->sponsor->name }}</td>
                      <td>{{ ucwords($centre->print_pref) }}</td>
                      <td><button type="submit" id="save">Save</button></td>
                  </tr>
              </tbody>
          </table>
      </form>
    </div>
</div>
@endsection
