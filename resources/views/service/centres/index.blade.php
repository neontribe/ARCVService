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
                    <th>Can Redeem</th>
                    <th>Form</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($centres as $centre)
                    <tr>
                        <td>{{ $centre->name }}</td>
                        <td>{{ $centre->prefix }}</td>
                        <td>{{ $centre->sponsor->name }}</td>
                        <td>{{ $centre->can_collect ? 'Yes' : 'No' }}</td>
                        <td>{{ ucwords($centre->print_pref) }}</td>
                        <td>
                            <a href="{{ route('admin.centres.edit', ['centre' => $centre->id]) }}" style="padding:5px;" class="link-button">
                              Edit
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.datatables.net/1.13.11/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.11/css/jquery.dataTables.min.css">
<script>
  $(document).ready(function () {
    $('#centresTable').DataTable({
        columnDefs: [{ orderable: false, targets: 4}]
    });
  });
</script>
@endsection
