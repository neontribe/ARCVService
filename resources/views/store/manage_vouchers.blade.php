@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')
    @include('store.partials.navbar', ['headerTitle' => 'Voucher Manager'])

    <div class="content">
        <div class="col-container">
            @include('store.partials.voucher-manager.family-info')
            @include('store.partials.voucher-manager.collection-history')
            @include('store.partials.voucher-manager.allocate-vouchers')
            @include('store.partials.voucher-manager.pickup')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {

            $('#collection-button').click(function (e) {
                e.preventDefault();
                $('#collection').addClass('slide-in');
                $('.allocation').addClass('fade-back');
            });

            if ($('#vouchers tr').length > 1) { // first tr is the header
                $('#vouchers-added').addClass('pulse');
            }

            var delay = 200;
            var firstVoucher = $('#first-voucher');
            var lastVoucher = $('#last-voucher');
            var singleVoucher = $('#single-voucher');

            firstVoucher.keypress(function (e) {
                if (e.keyCode !== 13) return;
                e.preventDefault();
                window.setTimeout(function () {
                    if (firstVoucher.val() !== '') lastVoucher.focus();
                }, delay);
            });

            lastVoucher.keypress(function (e) {
                if (e.keyCode !== 13) return;
                e.preventDefault();
                window.setTimeout(function () {
                    if (firstVoucher.val() === '') {
                        firstVoucher.focus();
                        return;
                    }
                    if (lastVoucher.val() !== '') {
                        $('#range-add').trigger('click');
                    }
                }, delay);
            });

            singleVoucher.keypress(function (e) {
                if (e.keyCode !== 13) return;
                e.preventDefault();
                window.setTimeout(function () {
                    $('#single-add').trigger('click');
                }, delay);
            });

            var collectedOn = $('#collected-on');
            if (collectedOn[0].type !== 'date') {
                collectedOn.datepicker({dateFormat: 'yy-mm-dd'}).val();
            }
            collectedOn.valueAsDate = new Date();

            collectedOn.change(function () {
                var chosen = new Date($(this).val());
                var cutoff = new Date();
                cutoff.setDate(cutoff.getDate() + 42); // six weeks

                if (chosen > cutoff) {
                    $('#dateError')
                        .text('Please choose a date within six weeks of today')
                        .css({fontSize: '12px', color: 'red'})
                        .show();
                } else {
                    $('#dateError').hide();
                }
            });
        });
    </script>
@endpush
