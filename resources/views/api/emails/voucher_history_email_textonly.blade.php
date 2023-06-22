* Rose Voucher Payment Records *

==============================================

Hi {{ $user }},

You've requested a copy of {{ $trader }}'s voucher payment records. The records are attached to this email for your
reference.

The file includes payment records from {{ $date }}@isset($max_date) to {{ $max_date }}. @endisset
Total Vouchers: {{ count($vouchers) }}
Total Value: £{{ count($vouchers) }}

The attached file is best viewed through a spreadsheet program such as Microsoft Excel, LibreOffice Calc or Google
Sheets. If you have any problems with opening or downloading it, please email <a
    href="mailto:{{ config('mail.to_developer.address') }}">{{ config('mail.to_developer.name') }}</a>.

Thanks,
Rose Vouchers

==============================================

Alexandra Rose Charity

For more information please go to <a href="https://www.alexandrarose.org.uk/" target="_blank" rel="noopener noreferrer">www.alexandrarose.org.uk</a>.

<a href="https://www.alexandrarose.org.uk/Handlers/Download.ashx?IDMF=0a18fbf9-ed33-4c1d-a8a3-766542c961c8" target="_blank" rel="noopener noreferrer" role="link">
    Privacy Policy
</a>