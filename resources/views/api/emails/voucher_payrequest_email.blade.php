@extends('api.layouts.email')

@section('content')

    <section role="main">
        <h1>Rose Voucher Payment Request</h1>

        <p>Hi {{ config('mail.to_admin.name') }},</p>

        <p> {{ $user }} has just successfully requested payment for</p>
        <p> {{ sizeOf($vouchers) }} vouchers, against</p>
        <p> {{ $trader }} of </p>
        <p> {{ $market }}'s account.</p>

        <p>The details for this request are attached to this email.</p>

        <p>The attached file is best viewed through a spreadsheet program such as Microsoft Excel, LibreOffice Calc or Google Sheets. If you have any problems with opening or downloading it, please email <a href="mailto:{{ config('mail.to_developer.address') }}">{{ config('mail.to_developer.name') }}</a>.</p>

        {{-- Action Button, sadly copied from template as couldn't get it to work using `@component` --}}
        <table class="action" align="center" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="center">
                                <table border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td>
                                            <a href="{{ $actionUrl }}" class="button button-pink" target="_blank">{{ $actionText }}</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table class="subcopy" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    If you’re having trouble clicking the "{{ $actionText }}" button, copy and paste the URL below
                    into your web browser:
                    <br>{{ $actionUrl }}
                </td>
            </tr>
        </table>
        <p>Thanks,<br>
        Rose Vouchers</p>
    </section>

@endsection
