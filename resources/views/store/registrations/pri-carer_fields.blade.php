{{--
    partial: store.registrations.primary-carer-fields
    ════════════════════════════════════════════════════════════════════════
    Renders the primary carer / participant input group:
      • name (text, required)
      • telephone number (password-input — unmasked entry, phantom-prefill on edit)
      • email address (password-input — unmasked entry, phantom-prefill on edit)
      • ethnic background (select, optional)
      • language (text, optional, alpha-space filter)

    Variables inherited from parent scope via @include:
        $pri_carer   – model instance when editing, undefined when creating.
                       Accessed throughout via ($pri_carer ?? null) so that
                       undefined and null are handled identically.

    Variables passed explicitly by the caller:
        $labelName        – label for the name field
        $labelTelno       – label for the telephone field
        $labelEmail       – label for the email field
        $labelEthnicity   – label for the ethnicity select
        $labelLanguage    – label for the language field
--}}

<div>
    <x-general-input name="pri_carer"
                     :label="$labelName"
                     :model-id="($pri_carer ?? null)?->id"
                     :value="($pri_carer ?? null)?->name"
                     error-message="This field is required"
                     alert-id="carer-alert"
    />

    <x-password-input name="pri_carer_telno"
                      :label="$labelTelno"
                      :model-id="($pri_carer ?? null)?->id"
                      :existing-password="!empty(($pri_carer ?? null)?->telnosecret?->reveal())"
    />

    <x-password-input name="pri_carer_email"
                      :label="$labelEmail"
                      :model-id="($pri_carer ?? null)?->id"
                      :existing-password="!empty(($pri_carer ?? null)?->emailsecret?->reveal())"
    />

    <br/>

    <x-general-select name="pri_carer_ethnicity"
                      :label="$labelEthnicity"
                      :options="config('arc.ethnicity_desc')"
                      :model-id="($pri_carer ?? null)?->id"
                      :value="($pri_carer ?? null)?->ethnicity"
    />
    @if (isset($pri_carer) && empty($pri_carer->ethnicity))
        <mark>Please complete ethnic background.</mark>
    @endif

    <br/>

    <x-general-input name="pri_carer_language"
                     :label="$labelLanguage"
                     :model-id="($pri_carer ?? null)?->id"
                     :value="($pri_carer ?? null)?->language"
                     error-key="pri_carer_language"
                     filter="alpha-space"
    />
    @if (isset($pri_carer) && empty($pri_carer->language))
        <mark>Please complete main language.</mark>
    @endif

    <br/>
</div>
