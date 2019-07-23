@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Children's Centres</h1>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>RVID Prefix</th>
                    <th>Sponsor</th>
                    <th>Form</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($centres as $centre)
                    <tr>{{ $centre->name }}</tr>
                    <tr>{{ $centre->rvid }}</tr>
                    <tr>{{ $centre->sponsor }}</tr>
                    <tr>{{ $centre->form }}</tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection