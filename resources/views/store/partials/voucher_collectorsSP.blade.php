<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/group-light.svg') }}" alt="logo">
        <input type="hidden" name="registration" value="{{ $registration->id ?? '' }}">
        <h2>Voucher collectors</h2>
    </div>
    @include('store.partials.pri-carer_fields', [
        'labelName'      => "Main Participant's full name",
        'labelTelno'     => "Main participant's telephone number",
        'labelEmail'     => "Main participant's email address",
        'labelEthnicity' => "Main participant's ethnic background (optional)",
        'labelLanguage'  => "Main participant's preferred language (optional)",
    ])

    <div id="addCarerAgeInput" class="age-input-container">
        @include('store.partials.ageInput')
        <button id="add-carer-age" class="link-button link-button-large">
            <i class="fa fa-plus button-icon" aria-hidden="true"></i>
            @if (isset($pri_carer))
                Update Main Participant
            @else
                Add Main Participant
            @endif
        </button>
    </div>

    @include('store.partials.sec-carers_fields', [
        'newCarersErrorMessage' => 'Please check you have valid collector names',
    ])
</div>

@pushonce('bottom')
    <script>
        $("#addCarerAgeInput").ageInput();
    </script>
@endpushonce
