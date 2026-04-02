{{--
    partial: store.partials.sec-carers_fields
    ════════════════════════════════════════════════════════════════════════
    Renders the voucher-collector adder widget and the secondary carers
    table, including both the persisted ($sec_carers) and the old()-repopulated
    (new_carers) rows.

    Variables inherited from parent scope via @include:
        $sec_carers   – collection of persisted secondary carer models,
                        present on edit pages, undefined on create pages.

    Variables passed explicitly by the caller:
        $newCarersErrorMessage  – validation error string shown beneath the
                                  table when new_carers.* fails (the only
                                  text that differs between callers).
--}}

<div>
    <label for="carer_adder_input">Voucher collectors (optional)</label>
    <div id="carer_adder" class="small-button-container">
        <input id="carer_adder_input"
               name="carer_adder_input"
               type="text"
               autocomplete="off"
               spellcheck="false"
        >
        <button id="add_collector" class="add-button">
            <i class="fa fa-plus" aria-hidden="true"></i>
        </button>
    </div>
    <span style="display:none;" id="carer-name-error" class="invalid-error">
        Must be: letters, numbers, spaces, hyphens, apostrophes and full stops.
    </span>
</div>
<div class="added">
    <p>You have added:</p>
    <table id="carer_wrapper">
        <!-- edit page -->
        @if (isset($sec_carers))
            @foreach ($sec_carers as $sec_carer)
                <tr>
                    <td>
                        <input name="sec_carers[{{ $sec_carer->id }}]"
                               type="text"
                               value="{{ $sec_carer->name }}"
                        >
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
        @if (is_array(old('new_carers')) || !empty(old('new_carers')))
            @foreach (old('new_carers') as $index => $old_new_carer)
                <tr>
                    <td>
                        <input name="new_carers[]"
                               type="text"
                               value="{{ $old_new_carer }}"
                               class="{{ $errors->has("new_carers.$index") ? 'invalid' : '' }}"
                        >
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

    @includeWhen(
        $errors->has('new_carers.*'),
        'store.partials.errors',
        ['error_array' => [$newCarersErrorMessage]]
    )
</div>
