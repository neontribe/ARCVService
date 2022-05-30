<div class="col full-height">
    <div>
        <img src="{{ asset('store/assets/info-light.svg') }}" alt="logo">
        <h2>Other information</h2>
    </div>
    @if ($programme === 0)
    <div>
        <label for="eligibility-hsbs">
            Are you receiving Healthy Start or Best Start?
        </label><br>
        <select name="eligibility-hsbs" id="eligibility-hsbs">
            <option value=0>Please select</option>
            @foreach (config('arc.reg_eligibilities_hsbs') as $index => $reg_eligibility)
                <option value="{{ $reg_eligibility }}"
                    @if(
                        (empty(old('eligibility-hsbs')) && $index === 0) ||
                        old('eligibility-hsbs') === $reg_eligibility
                        ) selected="selected"
                @endif
                >@lang('arc.reg_eligibilities_hsbs.' . $reg_eligibility)
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="eligibility-nrpf">
            No recourse to public funds (NRPF) family?
        </label><br>
        <select name="eligibility-nrpf" id="eligibility-nrpf">
            <option value=0>Please select</option>
            @foreach (config('arc.reg_eligibilities_nrpf') as $index => $reg_eligibility)
                <option value="{{ $reg_eligibility }}"
                    @if(
                        (empty(old('eligibility-nrpf')) && $index === 0) ||
                        old('eligibility-nrpf') === $reg_eligibility) selected="selected"
                    @endif
                >@lang('arc.reg_eligibilities_nrpf.' . $reg_eligibility)
                </option>
            @endforeach
        </select>
    </div>
    @endif
    <div>
        <div class="user-control">
            <input type="checkbox" class="styled-checkbox @if($errors->has('consent'))invalid @endif" id="privacy-statement" name="consent" @if( old('consent') ) checked @endif/>
            <label for="privacy-statement">Has the registration form been completed and signed?</label>
        </div>
    </div>
    @if ( $errors->has('consent') )
    <div class="alert-message error" id="registration-alert">
        <div class="icon-container error">
            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
        </div>
        <div>
            <p>Registration form must be signed in order to complete registration</p>
        </div>
    </div>
    @endif
    <button class="long-button submit" type="Submit">Save Family</button>
    </div>
</div>
    