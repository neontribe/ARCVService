<div class="col full-height">
    <div>
        <img src="{{ asset('store/assets/info-light.svg') }}" alt="logo">
        <h2>Other information</h2>
    </div>
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
    <button class="long-button submit" type="Submit">Save Household</button>
    </div>
</div>
    