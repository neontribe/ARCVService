    <div class="col fit-height">
        <div>
            <img src="{{ asset('store/assets/group-light.svg') }}" alt="logo">
            <h2>Voucher collectors</h2>
        </div>
        <div>
            <label for="carer">Main participant's full name</label>
            <input id="carer" name="carer" class="@if($errors->has('carer'))invalid @endif" type="text" autocomplete="off" autocorrect="off" spellcheck="false" value="{{ old('carer') }}">
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
            <label for="carer_adder_input">Other people who can collect <span>(optional)</span></label>
            <div id="carer_adder" class="small-button-container">
                <input id="carer_adder_input" name="carer_adder_input" type="text" autocomplete="off" autocorrect="off" spellcheck="false">
                <button id="add_collector" class="add-button">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                </button>
            </div>
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


