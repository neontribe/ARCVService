@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Add a worker</h1>

        <p>Use the form below to add a new worker. Add their name, email address, home centre and any alternative neighbouring centres they may work from.</p>

        <!-- ADD NEW POST ROUTE HERE eg. method="POST" action="{{ route('admin.vouchers.storebatch') }}" -->
        <form role="form" class="styled-form">
            {!! csrf_field() !!}
            <div class="horizontal-container">
                <div>
                    <label for="name" class="required">Name</label>
                    <input type="text" id="name" value="{{ $worker->name }}" name="name" class="{{ $errors->has('name') ? 'error' : '' }}" required>
                </div>
                <div>
                    <label for="email" class="required">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ $worker->email }}" class="{{ $errors->has('email') ? 'error' : '' }}" required>
                </div>
                <div class="select">
                    <label for="worker_centre">Home Centre</label>
                    <select name="worker_centre" id="worker_centre" class="{{ $errors->has('worker_centre') ? 'error' : '' }}" required>
                        <option >hello</option>
                    </select>
                    <p>{{ $worker->homeCentre }}</p>
                </div>

                <div class="checkboxes">
                    <label for="alternative_centres">Set Neighbours as Alternatives</label>
                    @foreach ($worker->relevantCentres() as $centre)
                        <div class="checkbox-group">
                            <input type="checkbox" id="{{ $centre->name }}" name="{{ $centre->name }}">
                            <label for="{{ $centre->name }}">{{ $centre->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <button type="submit" id="createWorker">Add worker</button>
        </form>
    </div>
</div>

@endsection