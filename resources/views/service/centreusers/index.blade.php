@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Workers</h1>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email Address</th>
                    <th>Home Centre</th>
                    <th>Alternative Centres</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($workers as $worker)
                    <tr>
                        <td>{{ $worker->name }}</td>
                        <td>{{ $worker->email }}</td>
                        <td>{{ $worker->homeCentre()->first()->name }}</td>
                        <td>
                            <ul class="table-list">
                                @foreach ($worker->centres as $centre)
                                    @if ($centre->id !== $worker->homeCentre()->first()->id)
                                    <li>{{ $centre->name }}</li>
                                    @endif
                                @endforeach
                            </ul>     
                        </td>
                        <td>
                            <a href="{{ route('admin.centreusers.edit', ['id' => $worker->id ]) }}" class="link">
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

@endsection
