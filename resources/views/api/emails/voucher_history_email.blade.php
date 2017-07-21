@extends('api.layouts.email')

@section('content')

    <section role="main">
        <h1 style="text-align: center;">[xXx] Voucher Payment Records</h1>

        <p>Hi {{ $user }},</p>

        <p>[xXx] You've requested a copy of {{ $trader }}'s voucher payment records for your reference, which is attached to this email.</p>

        <p>[xXx] The file includes payment records from {{ $date }} @isset($max_date)to {{ $max_date }}. @endisset</p>
        <p>[xXx] Total Vouchers: {{ count($vouchers) }}</p>
        <p>[xXx] Total Value: £{{ count($vouchers) }}</p>

        <p>The attached file is best viewed through a spreadsheet program such as Microsoft Excel, LibreOffice Calc or Google Sheets. If you have any problems with opening or downloading it, please email <a href="mailto:arc@neontribe.co.uk">arc@neontribe.co.uk</a>.</p>

        <p>Thanks,<br>
        Rose Vouchers</p>
    </section>

@endsection
