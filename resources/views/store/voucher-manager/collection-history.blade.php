{{-- Requires: $programme, $registration, $entitlement, $lastCollection --}}
<div class="col">
    <div>
        <img src="{{ asset('store/assets/history-light.svg') }}">
        <h2 id="collection-history">Collection History</h2>
    </div>

    <div>
        <div class="emphasised-section">
            <p>This {{ $programme === 0 ? 'family' : 'household' }} should collect:</p>
            <p><b>{{ $entitlement }} vouchers per week</b></p>
        </div>

        <div class="emphasised-section">
            @isset($lastCollection)
                <p>Their last collection was:</p>
                <p><b>{{ $lastCollection }}</b></p>
            @else
                <p class="v-spaced">
                    This {{ $programme === 0 ? 'family' : 'household' }} has not collected
                </p>
            @endisset
        </div>
    </div>

    <x-link-button
        href="{{ route('store.registration.collection-history', ['registration' => $registration->id]) }}"
        icon="clock-o"
        id="full-collection-link"
    >
        Full Collection History
    </x-link-button>
</div>
