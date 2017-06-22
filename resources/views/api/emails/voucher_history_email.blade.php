@extends('layouts.email')

@section('content')
[xXx]
	<div role="main">
		<h1 style="text-align: center;">Voucher payment history request</h1>

	    <p>Hi {{ $user }},</p>

	    <p>You've requested a record of {{ $trader }}'s voucher payment history, which is attached to this email.</p>

	    <p>If you have any problems with opening or downloading the file attached, please <a href="mailto:arc@neontribe.co.uk">email arc@neontribe.co.uk</a>.</p>

	    <p>Thanks,<br>
	    Rose Vouchers<br>
	    <a href="http://www.alexandrarose.org.uk/" role="link">www.alexandrarose.org.uk</a></p>
	</div>
[xXx]
@endsection
