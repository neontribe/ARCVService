<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Store Beta - @yield('title')</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="{{ asset('store/assets/google/fonts.css') }}" >
        <link rel="stylesheet" href="{{ asset('store/assets/font-awesome-4.7.0/css/font-awesome.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('store/css/main.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('store/css/datepicker.css') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script src="{{ asset('js/app.js') }}"></script>
        @yield('hoist-head')
        @stack('js')
    </head>
    <body>
    @yield('cookie-warning')

    @include('store.partials.masthead')

    @yield('content')

    </body>
</html>
