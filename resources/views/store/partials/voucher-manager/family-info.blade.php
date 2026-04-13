{{-- Requires: $programme, $registration, $pri_carer, $children, $noticeReasons --}}
<div class="col">
    <div>
        <img src="{{ asset('store/assets/' . ($programme === 0 ? 'info' : 'group') . '-light.svg') }}">
        <h2 id="this-family">{{ $programme === 0 ? 'This Family' : 'Voucher Collectors' }}</h2>
    </div>

    <div>
        <h3 id="rv-id">Their RV-ID is: {{ $registration->family->rvid }}</h3>
    </div>

    <div class="alongside-container">
        <div>
            <h3>Main {{ $programme === 0 ? 'Carer' : 'Participant' }}</h3>
            <p>{{ $pri_carer->name }}</p>
        </div>
        <div>
            <h3>{{ $programme === 0 ? 'Children:' : 'Household' }}</h3>
            <ul>
                @foreach($children as $child)
                    <li>{{ $child->getAgeString() }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    @if($programme === 0)
        @includeWhen(!empty($noticeReasons), 'store.partials.notice_box', ['noticeReasons' => $noticeReasons])
    @endif

    <x-link-button
        href="{{ route('store.registration.edit', ['registration' => $registration->id]) }}"
        icon="pencil"
        id="edit-family-link"
    >
        Go to edit {{ $programme === 0 ? 'family' : 'household' }}
    </x-link-button>

    <x-link-button
        href="{{ route('store.registration.index') }}"
        icon="search"
        id="find-another-family-link"
    >
        Find another {{ $programme === 0 ? 'family' : 'household' }}
    </x-link-button>
</div>
