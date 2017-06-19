@extends('layouts.email')

@section('content')
[xXx]
    <p>Hi {{ $user }},</p>

    <p>You've requested a record of {{ $trader }}'s voucher payment history, which is attached to this email.</p>
    <!-- Is this the right email> -->
    <p>If you have any problems with opening or downloading the file attached, please <a href="mailto:arc@neontribe.co.uk">email arc@neontribe.co.uk</a>.</p>

    <p>Thanks,<br>
    Rose Vouchers<br>
    <a href="http://www.alexandrarose.org.uk/">www.alexandrarose.org.uk</a></p>
[xXx]
@endsection
