@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Workers</h1>
        @if (Session::get('message'))
        <div class="alert alert-success">
            {{ Session::get('message') }}
        </div>
        @endif
        <table id="workersTable" class="table table-striped sortable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email Address</th>
                    <th>Home Centre Area</th>
                    <th>Home Centre</th>
                    <th>Alternative Centres</th>
                    <th>Downloader</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($workers as $worker)
                <tr>
                    <td>{{ $worker->name }}</td>
                    <td>{{ $worker->email }}</td>
                    <td> {{ $worker->homeCentre->sponsor->name }}</td>
                    <td>{{ $worker->homeCentre->name }}</td>
                    <td>
                        <ul class="table-list">
                            @foreach ($worker->centres as $centre)
                            @if ($centre->id !== $worker->homeCentre->id)
                            <li>{{ $centre->name }}</li>
                            @endif
                            @endforeach
                        </ul>
                    </td>
                    <td>{{ $worker->downloader ? 'Yes' : 'No' }}</td>
                    <td>
                        <a href="{{ route('admin.centreusers.edit', ['id' => $worker->id]) }}" class="link">
                            <div class="link-button link-button-small">
                                <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Edit
                            </div>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <a href="{{ URL::route('admin.centreusers.download')}}" class="link" target="_blank">
            <div class="link-button download-list">
                Download Worker List
            </div>
        </a>
    </div>

</div>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
<script>
  $(document).ready( function () {
    $('#workersTable').DataTable();
  } );
</script>
@endsection
