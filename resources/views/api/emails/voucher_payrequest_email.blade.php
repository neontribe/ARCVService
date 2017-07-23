@extends('api.layouts.email')

@section('content')

    <section role="main">
        <h1>Voucher Payment Request</h1>

        <p>Hi {{ config('mail.to_admin.name') }},</p>

        <p> {{ $user }} has just successfully requested payment for</p>
        <p> {{ sizeOf($vouchers) }} vouchers, against</p>
        <p> {{ $trader }} of </p>
        <p> {{ $market }}'s account.</p>

        <p>The details for this request are attached to this email.</p>

        <p>The attached file is best viewed through a spreadsheet program such as Microsoft Excel, LibreOffice Calc or Google Sheets. If you have any problems with opening or downloading it, please email <a href="mailto:arc@neontribe.co.uk">arc@neontribe.co.uk</a>.</p>

        <p>Thanks,<br>
        Rose Vouchers</p>
    </section>

@endsection
