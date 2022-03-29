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
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script src="{{ asset('js/app.js') }}"></script>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.3.6/css/bootstrap-colorpicker.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-2.2.2.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/2.3.6/js/bootstrap-colorpicker.js"></script>
        @yield('hoist-head')
    </head>
    <body>
    @yield('cookie-warning')

    <div style="display:flex; justify-content:center; padding:10px;">
        <img src="{{ asset('store/assets/logo.png') }}" alt="Rose Vouchers for Fruit & Veg" class="logo" style="">
    </div>
@section('content')
<div style="display:flex; flex-direction:row; flex: 0 0 50%">
  <div style="width:200px;">
    <div>
        Choose colour
        <input id="colorpicker"/>
    </div>
    <br>
    <br>
    <div>
        Choose logo
        <input type="file" id='filepicker'/>
    </div>
  </div>
  <div style="flex: 0 0 50%">
    <div style="height:auto;" class="content navigation">
        <ul>
          <a href="">
              <li>
                  <img src="{{ asset('store/assets/add-pregnancy-light.svg') }}" id="add-family">
                  Add a new family
              </li>
          </a>
          <a href="">
              <li>
                  <img src="{{ asset('store/assets/search-light.svg') }}" id="search">
                  Search for a family
              </li>
          </a>
          <a href="">
              <li>
                  <img src="{{ asset('store/assets/print-light.svg') }}" id="print-registrations">
                  PRINT
              </li>
          </a>
        </ul>
    </div>
  </div>
</div>
</body>
</html>
    <script>
        $('#colorpicker')
          .colorpicker({
            "color": "#a74e94",
          })
          .on('changeColor', function (e) {
            var chosenColour = e.color.toHex();
            $("li").each(function() {
              $(this).css('background-color', chosenColour);
            })
        });
    </script>
