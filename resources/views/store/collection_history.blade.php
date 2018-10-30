@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Full Collection History'])

    <div class="content">
        <h3>{{ $pri_carer->name }}</h3>
        <table>
            <tr>
              <th>Week Commencing</th>
              <th>Amount Collected</th>
              <th></th>
            </tr>
            <tr>
                <td>22/10/2018</td>
                <td>8</td>
                <td>
                    <button>Expand</button>
                </td>
            </tr>
                <td colspan=3>
                </td>
            </tr>
        </table>
    </div>

@endsection