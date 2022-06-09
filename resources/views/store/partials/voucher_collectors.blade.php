<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/group-light.svg') }}" alt="logo">
        <input type="hidden" name="registration" value="{{ $registration->id ?? ''}}">
        <h2>Voucher collectors</h2>
    </div>
    <div>
        <label for="carer">Main carer's full name</label>
        @if (isset($pri_carer))
            {{-- This section should only exist in edit rather than add new record --}}
            <input id="carer" name="pri_carer[{{ $pri_carer->id }}]"
                   class="@if($errors->has('pri_carer'))invalid @endif" type="text"
                   value="{{ $pri_carer->name }}" autocomplete="off"
                   autocorrect="off" spellcheck="false">

        @else
            {{-- If this is a new record do this instead --}}
            <input id="carer" name="carer" class="@if($errors->has('carer'))invalid @endif" type="text"
                   autocomplete="off" autocorrect="off" spellcheck="false" value="{{ old('carer') }}">
        @endif
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
                @foreach ( $sec_carers as $carer )
                    <tr>
                        <td>
                            <input name="carers[{{ $carer->id }}]" type="text"
                                   value="{{ $carer->name }}">
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
            @if(is_array(old('carers')) || (!empty(old('carers'))))
                @foreach (old('carers') as $old_sec_carer )
                    <tr>
                        <td><input name="carers[]" type="text" value="{{ $old_sec_carer }}"></td>
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
