@extends('api.layouts.email')

@section('content')

    <section role="main">
        <h1 style="text-align: center;">Voucher Payment Request History</h1>

        <p>Hi {{ $user }},</p>

        <p>You've requested a record of {{ $trader }}'s voucher payment history, which is attached to this email.</p>
        ==== variables for use in steph's copy =====
        <p>[xXx] The file includes payment records from {{ $date }} @isset($max_date)to {{ $max_date }}. @endisset</p>
        <p>[xXx] Total Vouchers: {{ count($vouchers) }}</p>
        <p>[xXx] Total Value: £{{ count($vouchers) }}</p>
        ==== /variables for use in steph's copy =====
        <p>If you have any problems with opening or downloading the file attached, please <a href="mailto:arc@neontribe.co.uk">email arc@neontribe.co.uk</a>.</p>

        <p>Thanks,<br>
        Rose Vouchers</p>
    </section>

@endsection
