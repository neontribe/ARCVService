@extends('api.layouts.email')

@section('content')

    <section role="main">
        <h1>Rose Voucher Payment Request</h1>

        <p>Hi {{ config('mail.to_admin.name') }},</p>

        <p> {{ $user }} has just successfully requested payment for</p>
        <p> {{ sizeOf($vouchers) }} vouchers, against</p>
        <p> {{ $trader }} of </p>
        <p> {{ $market }}'s account.</p>

        <p>They have requested payment for {{ $programme_amounts['numbers']['standard'] }} standard vouchers and {{ $programme_amounts['numbers']['social_prescription'] }} social prescription vouchers.</p>
        @if ($programme_amounts['numbers']['standard'] > 0)
            <h4>Standard</h4>
            <table>
                <tr><th>Area</th><th>Number of Vouchers</th></tr>
                @foreach ($programme_amounts['byArea'][0] as $name => $amount)
                    <tr>
                        <td>{{ $name }}</td>
                        <td style="text-align:center;">{{ $amount }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
        @if ($programme_amounts['numbers']['social_prescription'] > 0)
            <h4>Social Prescription</h4>
            <table>
                <tr><th>Area</th><th>Number of Vouchers</th></tr>
                @foreach ($programme_amounts['byArea'][1] as $name => $amount)
                    <tr>
                        <td>{{ $name }}</td>
                        <td style="text-align:center;">{{ $amount }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
        <br/>

        <table class="subcopy" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    To pay this request, please go to the Rose Voucher Admin Portal.
                </td>
            </tr>
        </table>
        <p>Thanks,<br>
        Rose Vouchers</p>
    </section>

@endsection
