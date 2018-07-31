<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>@yield('title')</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link href="https://fonts.googleapis.com/css?family=Roboto+Slab" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/font-awesome-4.7.0/css/font-awesome.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('css/print.css') }}">

        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    <body>
    <script type="text/php">
    if ( isset($pdf) ) {
        $font = $fontMetrics->getFont("helvetica", "bold");
        $pdf->page_text(72, 18, "Page: {PAGE_NUM} of {PAGE_COUNT}", $font, 6, array(0,0,0));
    }
    </script>
    @yield('content')

    </body>
</html>
