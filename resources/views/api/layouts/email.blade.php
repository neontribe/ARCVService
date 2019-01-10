<!DOCTYPE html>
<html lang="en-GB">
    <head>
        <meta charset="utf-8">
        <title>Rosie: Rose Voucher Records &amp; Reimbursement</title>
        <style type="text/css">
            @import url(http://fonts.googleapis.com/css?family=Open+Sans);
            body {
                font-family: 'Open Sans', 'Lucida Grande', sans-serif;
                font-size: 16px;
            }

            .button {
                border-radius: 3px;
                box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
                color: #FFF;
                display: inline-block;
                text-decoration: none;
                -webkit-text-size-adjust: none;
            }

            .button-pink {
                background-color: #a74e94;
                border-top: 10px solid #a74e94;
                border-right: 18px solid #a74e94;
                border-bottom: 10px solid #a74e94;
                border-left: 18px solid #a74e94;
            }

        </style>
    </head>
    <body>

        @yield('content')

        <div class="footer" style="margin-top: 40px; font-size: 13px; color: #999;">
            <p>&copy; Alexandra Rose Charity</p>
            <p>For more information please go to <a href="http://www.alexandrarose.org.uk/" role="link">www.alexandrarose.org.uk</a>.</p>
            <p><a href="{{ config('app.arc_market_url') }}/privacy-policy" role="link">Privacy Policy</a></p>
        </div>

    </body>
</html>
