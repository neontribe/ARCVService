<h2>Vouchers Queued on behalf of {{ $trader }} by {{ $user }}</h2>
<table>
    <thead>
        <tr>
            <th><strong>Voucher Code</strong></th>
            <th><strong>Date Added</strong></th>
        </tr>
    </thead>
    <tbody>
    @foreach ($vouchers as $voucher)
        <tr>
            <td>{{ $voucher->code }}</td>
            <td>{{ $voucher->updated_at->format('d-m-Y H:i.s') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
