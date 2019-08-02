@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Edit a Children's Centre Worker</h1>

        <p>Use the form below to amend a Children's Centre Worker's details.</p>

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
                        <option value="{{ $worker->homeCentre[0]->name }}">{{ $worker->homeCentre[0]->name }}</option>
                        @foreach ($neighbours as $neighbour)
                            <option value="{{ $neighbour->name }}">{{ $neighbour->name }}</option>
                        @endforeach
                    </select>
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