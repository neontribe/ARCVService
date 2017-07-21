* Voucher Payment Request *

==============================================

[xXx]
Hi {{ config('mail.to_admin.name') }},

{{ $user }} has just successfully requested payment for
{{ sizeOf($vouchers) }} vouchers, against
{{ $trader }} of
{{ $market }}'s account.

The details for this request are attached to this email.

If you have any problems with opening or downloading the file attached, please email <a href="mailto:arc@neontribe.co.uk">arc@neontribe.co.uk</a>.

Thanks,
Rose Vouchers
[xXx]
==============================================

Alexandra Rose Charity

For more information please go to <a href="http://www.alexandrarose.org.uk/">www.alexandrarose.org.uk</a>.

<a href="{{ config('app.arc_market_url') }}/privacy-policy">Privacy Policy</a>
