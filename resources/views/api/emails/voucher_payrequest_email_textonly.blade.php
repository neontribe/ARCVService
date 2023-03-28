* Rose Voucher Payment Request *

==============================================

Hi {{ config('mail.to_admin.name') }},

{{ $user }} has just successfully requested payment for
{{ sizeOf($vouchers) }} vouchers, against
{{ $trader }} of
{{ $market }}'s account.

The details for this request are attached to this email.

The attached file is best viewed through a spreadsheet program such as Microsoft Excel, LibreOffice Calc or Google
Sheets. If you have any problems with opening or downloading it, please email <a
    href="mailto:{{ config('mail.to_developer.address') }}">{{ config('mail.to_developer.name') }}</a>.

To register this request as paid, copy and paste the URL below
into your web browser and follow the directions there :

{{ $actionUrl }}

Thanks,
Rose Vouchers

==============================================

Alexandra Rose Charity

For more information please go to <a href="http://www.alexandrarose.org.uk/" target="_blank" rel="noopener noreferrer">www.alexandrarose.org.uk</a>.

<a href="https://www.alexandrarose.org.uk/Handlers/Download.ashx?IDMF=0a18fbf9-ed33-4c1d-a8a3-766542c961c8" target="_blank" rel="noopener noreferrer">
    Privacy Policy
</a>