@extends('service.layouts.app')

@section('content')
    <div id="container">

        @include('service.includes.sidebar')

        <div id="main-content">
            <h1>Payment History</h1>
            @if (Session::get('message'))
                <div class="alert alert-success">
                    {{ Session::get('message') }}
                </div>
            @endif

            {{--{{var_dump($history)}}--}}

            <div>
                @foreach ($history as $payment)
                1    {{ var_dump($payment) }}
                @endforeach
            </div>






        </div>
    </div>
@endsection