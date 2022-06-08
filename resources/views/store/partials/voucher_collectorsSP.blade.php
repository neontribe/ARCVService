    <div class="col fit-height">
        <div>
            <img src="{{ asset('store/assets/group-light.svg') }}" alt="logo">
            <input type="hidden" name="registration" value="{{ $registration->id ?? ''}}">
            <h2>Voucher collectors</h2>
        </div>
        <div>
            <label for="carer">Main participant's full name</label>
            {{-- This will only already be set for existing registrations and not new ones --}}
            @if (isset($pri_carer))
                <input id="carer" name="pri_carer[{{ $pri_carer->id }}]"
                           class="@if($errors->has('pri_carer'))invalid @endif" type="text"
                           value="{{ $pri_carer->name }}" autocomplete="off"
                           autocorrect="off" spellcheck="false">
            {{-- If this is a new record do this instead --}}
            @else
                <input id="carer" name="carer" class="@if($errors->has('carer'))invalid @endif" type="text" autocomplete="off" autocorrect="off" spellcheck="false" value="{{ old('carer') }}">
            @endif
        </div>

        <div id="addCarerAgeInput" class="age-input-container">
            @include('store.partials.ageInput')
            <button id="add-carer-age" class="link-button link-button-large">
                <i class="fa fa-plus button-icon" aria-hidden="true"></i>
                Add Main Participant
            </button>
        </div>

        @if ( $errors->has('carer') )
        <div class="alert-message error" id="carer-alert">
            <div class="icon-container error">
                <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
            </div>
            <div>
                <p>This field is required</p>
            </div>
        </div>
        @endif
        <div>
            <label for="carer_adder_input">Voucher collectors <span>(optional)</span></label>
            <table id="carer_wrapper">
                {{-- This section should only exist in edit rather than add new record --}}
                @if (isset($sec_carers))
                @foreach ( $sec_carers as $sec_carer )
                    <tr>
                        <td>
                            <input name="sec_carers[{{ $sec_carer->id }}]" type="text"
                                   value="{{ $sec_carer->name }}">
                        </td>
                        <td>
                            <button type="button" class="remove_field">
                                <i class="fa fa-minus" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
                @endif
            </table>
        </div>
            <div id="carer_adder" class="small-button-container">
                <input id="carer_adder_input" name="carer_adder_input" type="text" autocomplete="off" autocorrect="off" spellcheck="false">
                <button id="add_collector" class="add-button">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                </button>
            </div>
        <div class="added">
            <label for="carer_wrapper">You have added: </label>
            <table id="carer_wrapper">
                @if(is_array(old('carers')) || (!empty(old('carers'))))
                    @foreach (old('carers') as $old_sec_carer )
                        <tr>
                            <td><input name="carers[]" type="text" value="{{ $old_sec_carer }}" ></td>
                            <td><button type="button" class="remove_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>
                        </tr>
                    @endforeach
                @endif
            </table>
    </div>
</div>
