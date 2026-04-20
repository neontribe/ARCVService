{{-- Requires: $programme, $registration, $vouchers_amount, $carers, $centre --}}
<div id="collection" class="col collection-section">
    <div>
        <i class="fa fa-shopping-basket fa-3x" style="margin: 0 0.5rem;" ></i>
        <h2>Request Payment</h2>
    </div>

    <div>
        <p>There's <span class="number-circle">{{ $vouchers_amount }}</span> @choice('{1}voucher|[0,2,*]vouchers', $vouchers_amount) waiting for this {{ $programme === 0 ? 'family' : 'household' }}</p>
    </div>

    <form
        method="POST"
        action="{{ route('store.registration.vouchers.transitions.collect', ['registration' => $registration->id]) }}"
    >
        @method('PUT')
        @csrf
        <div class="pick-up">
            <div>
                <i class="fa fa-user"></i>
                <div>
                    <label for="collected-by">Transact with:</label>
                    <select id="collected-by" name="collected_by">
                        @foreach($carers as $carer)
                            <option value="{{ $carer->id }}">{{ $carer->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <i class="fa fa-calendar"></i>
                <div>
                    <label for="collected-on">Transact on:</label>
                    <div id="dateError" style="display:none;"></div>
                    <input
                        id="collected-on"
                        name="collected_on"
                        value="{{ now()->format('Y-m-d') }}"
                        type="date"
                    >
                </div>
            </div>

            <div>
                <i class="fa fa-home"></i>
                <div>
                    <label for="collected-at">Transact at: {{ $centre->name }}</label>
                    <input type="hidden" id="collected-at" name="collected_at" value="{{ $centre->id }}">
                </div>
            </div>

            <div>
                <i class="fa fa-shopping-cart"></i>
                <div>
                    <label for="collected-as">Transact as:</label>
                    <select id="collected-as" name="trader_id">
                        @foreach($centre->markets as $market)
                            <optgroup label="{{ $market->name }}">
                                @foreach($market->traders as $trader)
                                    <option value="{{ $trader->id }}">{{ $trader->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
            </div>

            <button id="collection-button" class="long-button submit" type="submit" @disabled($vouchers_amount === 0)>
                Confirm Transaction
            </button>
        </div>
    </form>

    <x-link-button
        href="{{ route('store.registration.voucher-manager', ['registration' => $registration->id]) }}"
        icon="ticket"
    >
        Change allocated vouchers
    </x-link-button>
</div>

@pushonce('scripts')
    <script>
        $(document).ready(function () {
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
@endpushonce
