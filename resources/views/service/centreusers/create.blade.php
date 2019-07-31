@extends('service.layouts.app')
@section('content')

<div id="container">
    @include('service.includes.sidebar')
    <div id="main-content">

        <h1>Add a Children's Centre Worker</h1>

        <p>Use the form below to add a new worker. Add their name, email address, home centre and any alternative neighbouring centres they may work from.</p>

        <form role="form" class="styled-form" method="POST" action="{{ route('admin.workers.store') }}">
            {!! csrf_field() !!}
            <div class="horizontal-container">
                <div>
                    <label for="name" class="required">Name</label>
                    <input type="text" id="name" name="name" class="{{ $errors->has('name') ? 'error' : '' }}" required>
                </div>
                <div>
                    <label for="email" class="required">Email Address</label>
                    <input type="email" id="email" name="email" class="{{ $errors->has('email') ? 'error' : '' }}" required>
                </div>
                <div class="select">
                    <label for="worker_centre">Home Centre</label>
                    <select name="worker_centre" id="worker_centre" class="{{ $errors->has('worker_centre') ? 'error' : '' }}" required>
                        <option value="">Choose one</option>
                        @foreach ($centres as $centre)
                            <option value="{{ $centre->id }}">{{ $centre->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="checkboxes">
                    <label for="alternative_centres">Set Neighbours as Alternatives</label>
                    {{-- WIRE THIS UP CORRECTLY FROM CONTROLLER --}}
                    {{-- @foreach ($relevantCentres as $centre)
                        <div>
                            <input type="checkbox" id="{{ $centre->name }}" name="{{ $centre->name }}">
                            <label for="{{ $centre->name }}">{{ $centre->name }}</label>
                        </div>
                    @endforeach --}}
                    <div class="checkbox-group">
                        <input type="checkbox" id="Example1" name="Example1">
                        <label for="Example1">Example1</label>
                    </div>
                </div>
            </div>
            <button type="submit" id="createWorker">Add worker</button>
        </form>
        <script>


        </script>
    </div>
</div>

@endsection