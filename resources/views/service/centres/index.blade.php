@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Children's Centres</h1>
        @if (Session::get('message'))
            <div class="alert alert-success">
                {{ Session::get('message') }}
            </div>
        @endif
        <table id='centresTable' class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>RVID Prefix</th>
                    <th>Area</th>
                    <th>Form</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($centres as $centre)
                    <tr>
                        <td>{{ $centre->name }}</td>
                        <td>{{ $centre->prefix }}</td>
                        <td>{{ $centre->sponsor->name }}</td>
                        <td>{{ ucwords($centre->print_pref) }}</td>
                        <td>
                            <a href="{{ route('admin.centres.edit', ['id' => $centre->id]) }}" class="link">
                                <div class="link-button link-button-small">
                                    <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Edit
                                </div>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
<script>
  $(document).ready(function () {
    $('#centresTable').DataTable();
  });
</script>
@endsection
