* Voucher Payment Request History *

==============================================

Hi {{ $user }},

You've requested a record of {{ $trader }}'s voucher payment history, which is attached to this email.

==== variables for use in steph's copy =====
[xXx] The file includes payment records from {{ $date }} @isset($max_date)to {{ $max_date }}. @endisset
[xXx] Total Vouchers: {{ count($vouchers) }}
[xXx] Total Value: £{{ count($vouchers) }}
==== /variables for use in steph's copy =====

If you have any problems with opening or downloading the file attached, please email <a href="mailto:arc@neontribe.co.uk">arc@neontribe.co.uk</a>.

Thanks,
Rose Vouchers

==============================================

Alexandra Rose Charity

For more information please go to <a href="http://www.alexandrarose.org.uk/">www.alexandrarose.org.uk</a>.

<a href="{{ config('app.arc_market_url') }}/privacy-policy" role="link">Privacy Policy</a>
