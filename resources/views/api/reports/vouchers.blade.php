<h2>Vouchers submitted by {{ $user }} for payment on behalf of {{ $trader }} from {{ $market }}</h2>
<table>
    <thead>
        <tr>
            <th><strong>Payment Request Date</strong></th>
            <th><strong>Voucher Code</strong></th>
            <th><strong>Date Added</strong></th>
        </tr>
    </thead>
    <tbody>
    @foreach ($vouchers as $voucher)
        <tr>
            <td>{{ $voucher['pended_on'] }}</td>
            <td>{{ $voucher['code'] }}</td>
            <td>{{ $voucher['added_on'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
