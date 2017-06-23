<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Service Dashboard</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Lato:100,700" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #f9f9f9;
                color: #433740;
                font-family: 'Lato', sans-serif;
                font-weight: 100;
                margin: 20px;
                line-height: 1.2;
            }

            p {
                color: #433740;
                font-weight: 700;
            }

            ul {
                list-style: none;
            }

            li {
                background-color: #a74e94;
                padding: 10px;
                margin: 10px 0;
                width: 90px;
                border-radius: 3px;
            }

            li a {
                color: #ffffff;
                font-size: 18px;
                font-weight: 700;
                text-decoration: none;
            }

            li.danger {
                background-color: #d2232a;
            }
        </style>
    </head>
    <body>
    @unless(Config('app.url') === 'https://voucher-admin.alexandrarose.org.uk')
        <p>{{ Session::get('message') }}</p>
        <h1>Service data endpoints</h1>

        <div>
            <ul>
                <li><a href="service/vouchers">Vouchers</a></li>
                <li><a href="service/users">Users</a></li>
                <li><a href="service/traders">Traders</a></li>
                <li><a href="service/markets">Markets</a></li>
                <li class="danger"><a href="service/reset-data">Reset data</a></li>
            </ul>
        </div>
    @endUnless
    </body>
</html>
