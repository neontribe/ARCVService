<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/group-light.svg') }}" alt="logo">
        <input type="hidden" name="registration" value="{{ $registration->id ?? ''}}">
        <h2>Voucher collectors</h2>
    </div>
    <div>
        <label for="pri_carer">Main carer's full name</label>
        @if (isset($pri_carer))
            {{-- This section should only exist in edit rather than add new record --}}
            <input id="carer" name="pri_carer[{{ $pri_carer->id }}]"
                   class="@if($errors->has('pri_carer'))invalid @endif" type="text"
                   value="{{ $pri_carer->name }}" autocomplete="off"
                   autocorrect="off" spellcheck="false">

        @else
            {{-- If this is a new record do this instead --}}
            <input id="carer" name="pri_carer" class="@if($errors->has('pri_carer'))invalid @endif" type="text"
                   autocomplete="off" autocorrect="off" spellcheck="false" value="{{ old('pri_carer') }}">
        @endif
    </div>
    @if ( $errors->has('pri_carer') )
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
        <label for="carer_adder_input">Voucher collectors (optional)</label>
        <div id="carer_adder" class="small-button-container">
            <input id="carer_adder_input" name="carer_adder_input" type="text" autocomplete="off" autocorrect="off"
                   spellcheck="false">
            <button id="add_collector" class="add-button">
                <i class="fa fa-plus" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="added">
        <p>You have added:</p>
        <table id="carer_wrapper">
            <!-- edit page -->
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
            <!-- create and edit pages, bad submit reload -->
            @if(is_array(old('new_carers')) || (!empty(old('new_carers'))))
                @foreach (old('new_carers') as $old_new_carer )
                    <tr>
                        <td><input name="new_carers[]" type="text" value="{{ $old_new_carer }}"></td>
                        <td>
                            <button type="button" class="remove_field"><i class="fa fa-minus" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            @endif
        </table>
    </div>
</div>
