@extends('api.layouts.email')

@section('content')

    <section role="main">
        <h1 style="text-align: center;">Voucher Duplicate Entered</h1>

        <p>[xXx]Hi {{ config('mail.to_admin.name') }},</p>

        <p> {{ $user }} has tried to submit voucher</p>
        <p> {{ $vouchercode }} against</p>
        <p> {{ $trader }} of </p>
        <p> {{ $market }}'s account, however that voucher has already been submitted by another trader.</p>

        <p>Thanks,<br>
        Rose Vouchers[xXx]</p>
    </section>

@endsection
