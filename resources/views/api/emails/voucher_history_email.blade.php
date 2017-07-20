@extends('api.layouts.email')

@section('content')

    <section role="main">
        <h1 style="text-align: center;">[xXx] Voucher Payment Records</h1>

        <p>Hi {{ $user }},</p>

        <p>[xXx] You've requested a copy of {{ $trader }}'s voucher payment records, which is attached to this email.</p>

        <p>[xXx] The file includes payment records from {{ $date }} @isset($max_date)to {{ $max_date }}. @endisset</p>
        <p>[xXx] Total Vouchers: {{ count($vouchers) }}</p>
        <p>[xXx] Total Value: £{{ count($vouchers) }}</p>

        <p>If you have any problems with opening or downloading the file attached, please <a href="mailto:arc@neontribe.co.uk">email arc@neontribe.co.uk</a>.</p>

        <p>Thanks,<br>
        Rose Vouchers</p>
    </section>

@endsection
