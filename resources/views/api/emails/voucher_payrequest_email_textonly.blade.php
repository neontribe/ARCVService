* Voucher Payment Request *

==============================================

Hi {{ config('mail.to_admin.name') }},

{{ $user }} has just successfully requested payment for
{{ sizeOf($vouchers) }} vouchers, against
{{ $trader }} of
{{ $market }}'s account.

The details for this request are attached to this email.

The attached file is best viewed through a spreadsheet program such as Microsoft Excel, LibreOffice Calc or Google Sheets. If you have any problems with opening or downloading it, please email <a href="mailto:{{ config('mail.to_developer.address') }}">{{ config('mail.to_developer.name') }}</a>.

Thanks,
Rose Vouchers

==============================================

Alexandra Rose Charity

For more information please go to <a href="http://www.alexandrarose.org.uk/">www.alexandrarose.org.uk</a>.

<a href="{{ config('app.arc_market_url') }}/privacy-policy">Privacy Policy</a>
