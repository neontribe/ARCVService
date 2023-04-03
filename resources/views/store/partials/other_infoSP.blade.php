<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/info-light.svg') }}" alt="logo">
        <input type="hidden" name="registration" value="{{ $registration->id ?? ''}}">
        <h2>Other information</h2>
    </div>
    {{-- This section should only exist in add new rather than edit record --}}
    @if (!isset($family))
        <div class="user-control">
            <input type="checkbox" class="styled-checkbox @if($errors->has('consent'))invalid @endif" id="privacy-statement" name="consent" @if( old('consent') ) checked @endif/>
            <label for="privacy-statement">Has the registration form been completed and signed?</label><br></br>
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
        {{-- The save button for add new household --}}
        <button class="long-button submit" type="Submit">Save Household</button>
    @endif
    {{-- This section should only exist in edit rather than add new record --}}
    @if (isset($registration))
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
            </ul>
        </div>
        <button class="long-button submit" type="submit" formnovalidate>Save Changes</button>
        <a href="{{ route("store.registration.voucher-manager", ['registration' => $registration ]) }}" class="link">
            <div class="link-button link-button-large">
                <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher manager
            </div>
        </a>
        @php(\App\Http\Controllers\Store\FamilyController::status($registration))
        @if ($registration->family->status === true )
            <button class="remove long-button" type="button">Remove this household</button>
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
    