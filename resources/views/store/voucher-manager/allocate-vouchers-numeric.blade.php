{{-- Requires: $registration, $vouchers, $vouchers_amount, $entitlement, $errors --}}
<div class="col allocation">
    <div>
        <img src="{{ asset('store/assets/allocation-light.svg') }}">
        <h2 id="allocate-vouchers">Allocate Vouchers</h2>
    </div>

    {{-- Quantity entry --}}
    <form method="POST" action="{{ route('store.registration.vouchers.post', ['registration' => $registration->id]) }}">
        @csrf
        <div class="single-container">
            <label for="voucher-quantity">Number of vouchers
                <input
                    id="voucher-quantity"
                    name="voucher-quantity"
                    type="number"
                    min="1"
                    max="{{ config('arc.bundle_max_voucher_append') }}"
                    value="{{ $entitlement }}"
                    autocomplete="off"
                >
            </label>
            <button id="quantity-add" class="add-button" type="submit" name="quantity-add" @disabled($pool_size === 0)>
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
        </div>


    </form>
    @if($pool_size > 0)
        <div id="vouchers-total">
            <span class="emphasised-section">Vouchers available:</span>
            <span >{{ $pool_size }}</span>
        </div>
    @else
        @include('store.partials.errors', ['error_array' => ['There are no more available vouchers; please contact Admin to get some more.']])
    @endif

    @includeWhen($errors->count() > 0, 'store.partials.errors', ['error_array' => $errors->all()])
    @includeWhen(Session::get('error_messages'), 'store.partials.errors', ['error_array' => Session::get('error_messages')])

    <button id="collection-button" class="long-button" @disabled($vouchers_amount === 0)>
        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher redemption
    </button>

    <div class="center" id="vouchers-added">
        <div id="vouchers-total">
            <span class="emphasised-section">Vouchers added</span>
            <span class="number-circle">{{ $vouchers_amount }}</span>
        </div>

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

@pushonce('js')
    <script>
        $(document).ready(function () {
            $('#collection-button').click(function (e) {
                e.preventDefault();
                $('#collection').addClass('slide-in');
                $('.allocation').addClass('fade-back');
            });

            if ($('#vouchers tr').length > 1) { // first tr is the header
                $('#vouchers-total').addClass('pulse');
            }

            $('#voucher-quantity').keypress(function (e) {
                if (e.keyCode !== 13) return;
                e.preventDefault();
                window.setTimeout(function () {
                    $('#quantity-add').trigger('click');
                }, 200);
            });
        });
    </script>
@endpushonce

