@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')
    @include('store.partials.navbar', ['headerTitle' => 'Voucher Manager'])

    <div class="content">
        <div class="col-container">
            @include('store.voucher-manager.family-info')
            @include('store.voucher-manager.collection-history')
            @include('store.voucher-manager.allocate-vouchers')
            @include('store.voucher-manager.pickup')
        </div>
    </div>
@endsection

