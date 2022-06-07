@extends('service.layouts.app')

@section('content')
<div id="container">

    @include('service.includes.sidebar')

    <div id="main-content">
        <h1>Areas</h1>
        @if (Session::get('message'))
            <div class="alert alert-success">
                {{ Session::get('message') }}
            </div>
        @endif
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Voucher Prefix</th>
                    <th>Programme</th>
                    <th></th>
                    {{-- <th>Scottish Centre</th> --}}
                </tr>
            </thead>
            <tbody>
                @foreach ($sponsors as $sponsor)
                    <tr>
                        <td>{{ $sponsor->name }}</td>
                        <td>{{ $sponsor->shortcode }}</td>
                        <td>{{ config('arc.programmes')[$sponsor->programme] }}</td>
                        @if($sponsor->programme)
                            <td><a href="{{ route('admin.sponsors.edit', ['id' => $sponsor->id]) }}" style="padding:5px;" class="link-button">
                                <span class="link-button link-button-small">
                                    Edit
                                </span>
                            </a></td>
                        @else
                            <td></td>
                        @endif
                        {{-- <td>{{ $sponsor->is_scotland ? 'Yes' : 'No' }}</td> --}}
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection
