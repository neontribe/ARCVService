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
                    <th>Home Centres</th>
                    <th>Alternative Centres</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($workers as $worker)
                    <tr>
                        <td>{{ $worker->name }}</td>
                        <td>{{ $worker->email }}</td>
                        <td>{{ $worker->homeCentre->first()['name'] }}</td>
                        <td>
                            <ul class="table-list">
                                @foreach ($worker->centres as $centre)
                                    <li>{{ $centre->name }}</li>
                                @endforeach
                            </ul>     
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection
