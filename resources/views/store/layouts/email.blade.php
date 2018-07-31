<!DOCTYPE html>
<html lang="en-GB">
    <head>
        <meta charset="utf-8">
        <title>Rosie: Rose Voucher Records &amp; Reimbursement</title>
        <style type="text/css">
            @import url(https://fonts.googleapis.com/css?family=Open+Sans);
            body {
                font-family: 'Open Sans', 'Lucida Grande', sans-serif;
                font-size: 16px;
            }
        </style>
    </head>
    <body>

        @yield('content')

        <div class="footer" style="margin-top: 40px; font-size: 13px; color: #999;">
            <p>&copy; Alexandra Rose Charity</p>
            <p>For more information please go to <a href="http://www.alexandrarose.org.uk/" role="link">www.alexandrarose.org.uk</a>.</p>
            <p><a href="{{ route('service.base') }}/privacy-policy" role="link">Privacy Policy</a></p>
        </div>

    </body>
</html>
