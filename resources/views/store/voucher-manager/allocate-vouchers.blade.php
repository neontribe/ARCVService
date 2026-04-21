{{-- Requires: $registration, $vouchers, $vouchers_amount, $errors --}}
<div class="col allocation">
    <div>
        <img src="{{ asset('store/assets/allocation-light.svg') }}">
        <h2 id="allocate-vouchers">Allocate Vouchers</h2>
    </div>

    {{-- Range entry --}}
    <form method="POST" action="{{ route('store.registration.vouchers.post', ['registration' => $registration->id]) }}">
        @csrf
        <div class="alongside-container">
            <label>First voucher
                <input id="first-voucher" name="start" type="text" autofocus class="uppercase" autocomplete="off">
            </label>
            <label>Last voucher
                <input id="last-voucher" name="end" type="text" class="uppercase" autocomplete="off">
            </label>
            <button id="range-add" class="add-button" type="submit" name="range-add">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
        </div>
    </form>

    <p class="center no-margin">OR</p>

    {{-- Single entry --}}
    <form method="POST" action="{{ route('store.registration.vouchers.post', ['registration' => $registration->id]) }}">
        @csrf
        <div class="single-container">
            <label for="single-voucher">Add individual vouchers
                <input id="single-voucher" name="start" type="text" class="uppercase" autocomplete="off">
            </label>
            <button id="single-add" class="add-button" type="submit" name="add-button">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
        </div>
    </form>

    @includeWhen($errors->count() > 0, 'store.partials.errors', ['error_array' => $errors->all()])
    @includeWhen(Session::get('error_messages'), 'store.partials.errors', ['error_array' => Session::get('error_messages')])

    <button id="collection-button" class="long-button" @disabled($vouchers_amount == 0)>
        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher collection
    </button>

    <div class="center" id="vouchers-added">
        <span class="emphasised-section">Vouchers added</span>
        <span class="number-circle">{{ $vouchers_amount }}</span>

        <div @class(['collapsed' => $vouchers_amount === 0])>
            <form
                id="unbundle-all"
                name="unbundle-all"
                method="POST"
                action="{{ route('store.registration.vouchers.delete', ['registration' => $registration->id]) }}"
                class="delete-button remove-all-container"
            >
                @method('DELETE')
                @csrf
                <button type="submit" name="delete-all-button" id="delete-all-button">
                    <i class="fa fa-trash" aria-hidden="true"></i> Remove all added vouchers
                </button>
            </form>
        </div>

        <div id="vouchers" @class(['collapsed' => $vouchers_amount === 0])>
            <form id="unbundle" name="unbundle" method="POST">
                @method('DELETE')
                @csrf
                <table>
                    <tr>
                        <th>Voucher code</th>
                        <th>Remove</th>
                    </tr>
                    @foreach($vouchers as $voucher)
                        <tr>
                            <td>{{ $voucher->code }}</td>
                            <td>
                                <button
                                    type="submit"
                                    class="delete-button"
                                    name="delete-button"
                                    id="{{ $voucher->id }}"
                                    formaction="{{ route('store.registration.voucher.delete', ['registration' => $registration->id, 'voucher' => $voucher->id]) }}"
                                >
                                    <i class="fa fa-minus" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </form>
        </div>
    </div>
</div>

@pushonce('scripts')
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
        });
    </script>
@endpushonce
