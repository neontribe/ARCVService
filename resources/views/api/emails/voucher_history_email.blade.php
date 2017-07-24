@extends('api.layouts.email')

@section('content')

    <section role="main">
        <h1>Rose Voucher Payment Records</h1>

        <p>Hi {{ $user }},</p>

        <p>You've requested a copy of {{ $traders }}'s voucher payment records. The records are attached to this email for your reference.</p>

        <p>The file includes payment records from {{ $date }} @isset($max_date)to {{ $max_date }}. @endisset</p>
        <p>Total Vouchers: {{ count($vouchers) }}</p>
        <p>Total Value: £{{ count($vouchers) }}</p>

        <p>The attached file is best viewed through a spreadsheet program such as Microsoft Excel, LibreOffice Calc or Google Sheets. If you have any problems with opening or downloading it, please email <a href="mailto:{{ config('mail.to_developer.address') }}">{{ config('mail.to_developer.name') }}</a>.</p>

        <p>Thanks,<br>
        Rose Vouchers</p>
    </section>

@endsection
