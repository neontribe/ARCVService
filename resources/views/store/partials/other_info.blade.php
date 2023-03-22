<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/info-light.svg') }}" alt="logo">
        <input type="hidden" name="registration" value="{{ $registration->id ?? ''}}">
        <h2>This family</h2>
    </div>
    {{-- This section should only exist in add new rather than edit record --}}
    @if (isset($family))
        <div>
            Their RV-ID is: <strong>{{ $family->rvid }}</strong>
            <ul>
                <li>
                    Should collect
                    <strong>
                        {{ $entitlement }}
                    </strong>
                    per week
                </li>
                <li>
                    Has
                    <strong>
                        {{ count($family->children) }}
                    </strong>
                    {{ str_plural('child', count($family->children)) }}
                    registered
                    @if ( $family->expecting != null )
                        including one pregnancy
                    @endif
                    <span class="clickable-span">(more)</span>
                </li>
                <li class="collapsed" id="more-family-info">
                    <p>The system gives these vouchers per week:</p>
                    <ul id="creditables">
                        @foreach($evaluations["creditables"] as $creditable)
                            <li>
                                If {{ strtolower(class_basename($creditable::SUBJECT)) }}
                                {{ $creditable->reason }} :
                                {{ $creditable->value }} {{ str_plural('voucher', $creditable->value) }}
                            </li>
                        @endforeach
                    </ul>
                    @if(count($evaluations["disqualifiers"]) > 0)
                        <p>Reminders:</p>
                        <ul id="disqualifiers">
                            @foreach($evaluations["disqualifiers"] as $disqualifier)
                                <li>
                                    If {{ strtolower(class_basename($disqualifier::SUBJECT)) }} {{ $disqualifier->reason }} {{ $disqualifier->value}} {{ str_plural('voucher', $disqualifier->value) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            </ul>
        </div>
    @endif
    <div>
        <label for="eligibility-hsbs">
            Are you receiving Healthy Start or Best Start?
        </label><br>
        <select name="eligibility-hsbs" id="eligibility-hsbs">
            <option value=0>Please select</option>
            @foreach (config('arc.reg_eligibilities_hsbs') as $index => $reg_eligibility)
                <option value="{{ $reg_eligibility }}"
                    @if(
                        (!isset($registration) && $index === 0) ||
                        (isset($registration) && $registration->eligibility_hsbs === $reg_eligibility)
                        ) selected="selected"
                @endif
                >@lang('arc.reg_eligibilities_hsbs.' . $reg_eligibility)
                </option>
            @endforeach
        </select>
        @if ($registration->eligibility_hsbs === 'healthy-start-applying')
            <br><mark>Please check if status has changed to receiving.</mark></br>
        @endif
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
                        (!isset($registration) && $index === 1) ||
                        (isset($registration) && $registration->eligibility_nrpf === $reg_eligibility)
                        ) selected="selected"
                    @endif
                >@lang('arc.reg_eligibilities_nrpf.' . $reg_eligibility)
                </option>
            @endforeach
        </select>
    </div>
    {{-- This section should only exist in edit rather than add new --}}
    @if (isset($noticeReasons))
        @includeWhen(!empty($noticeReasons), 'store.partials.notice_box', ['noticeReasons' => $noticeReasons])
    @endif
    {{-- This section should only exist in add new rather than existing records --}}
    @if (!isset($family))
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
    @endif
    {{-- This section should only exist in edit rather than add new --}}
    @if (isset($registration))
    <button class="long-button submit" type="submit" formnovalidate>Save Changes</button>
        <button class="long-button"
                onclick="window.open( '{{ URL::route("store.registration.print", ["registration" => $registration]) }}'); return false">
            Print a 4 week collection sheet for this family
        </button>
        <a href="{{ route("store.registration.voucher-manager", ['registration' => $registration ]) }}" class="link">
            <div class="link-button link-button-large">
                <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher manager
            </div>
        </a>
        @php(\App\Http\Controllers\Store\FamilyController::status($registration))
        @if ($registration->family->status === true )
        <button class="remove long-button" type="button">Remove this family</button>
        <div id="expandable" class="collapsed confirm-leaving">
            <div class="reason">
                <label for="reason-for-leaving">
                    Reason for leaving
                </label>
                <select id="reason-for-leaving" name="leaving_reason" required>
                    <option value="" disabled selected>Select a reason...</option>
                    @foreach(Config::get('arc.leaving_reasons') as $reason)
                        <option value="{{ $reason }}"> {{ $reason }}</option>
                    @endforeach
                </select>
            </div>
            <p>Are you sure?</p>
            <div class="confirmation-buttons">
                <button type="submit" class="submit" formaction="{{ URL::route('store.registration.family',['registration' => $registration]) }}">Yes</button>
                <button id="cancel">Cancel</button>
            </div>
        </div>
        @endif
    @endif
</div>
