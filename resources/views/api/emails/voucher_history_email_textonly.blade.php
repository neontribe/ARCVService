* Voucher Payment Records *

==============================================

Hi {{ $user }},

You've requested a copy of {{ $trader }}'s voucher payment records for your reference, which is attached to this email.

[xXx] The file includes payment records from {{ $date }}@isset($max_date) to {{ $max_date }}. @endisset
[xXx] Total Vouchers: {{ count($vouchers) }}
[xXx] Total Value: £{{ count($vouchers) }}

The attached file is best viewed through a spreadsheet program, such as Microsoft Excel or Google Sheets. If you have any problems with opening or downloading it, please email <a href="mailto:arc@neontribe.co.uk">arc@neontribe.co.uk</a>.

Thanks,
Rose Vouchers

==============================================

Alexandra Rose Charity

For more information please go to <a href="http://www.alexandrarose.org.uk/">www.alexandrarose.org.uk</a>.

<a href="{{ config('app.arc_market_url') }}/privacy-policy" role="link">Privacy Policy</a>
