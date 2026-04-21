<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/group-light.svg') }}" alt="logo">
        <input type="hidden" name="registration" value="{{ $registration->id ?? '' }}">
        <h2>Voucher collectors</h2>
    </div>
    @include('store.registrations.pri-carer_fields', [
        'labelName'      => "Main carer's full name",
        'labelTelno'     => "Main carer's telephone number",
        'labelEmail'     => "Main carer's email address",
        'labelEthnicity' => "Main carer's ethnic background (optional)",
        'labelLanguage'  => "Carer's main language (optional)",
    ])
    @include('store.registrations.sec-carers_fields', [
        'newCarersErrorMessage' => 'Please check you have valid carer names',
    ])
</div>

