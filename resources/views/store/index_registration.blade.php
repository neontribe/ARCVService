@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

    @include('store.partials.navbar', [
        'headerTitle' => 'Search for a ' . ($programme ? 'household' : 'family')
    ])

    @if (session('message'))
        @include('store.partials.success')
    @endif

    <div class="content search">
        <div class="control-container">
            <form action="{{ route('store.registration.index') }}" method="GET" id="searchform">
                @csrf

                {{-- Families left checkbox --}}
                <div class="checkbox-control">
                    <input
                        type="checkbox"
                        class="styled-checkbox no-margin"
                        id="families_left"
                        name="families_left"
                        @checked(request('families_left'))
                        onChange="submitSearchForm()"
                    />
                    <label for="families_left">
                        Show {{ $programme ? 'households' : 'families' }} who have left
                    </label>
                </div>

                {{-- Families left checkbox --}}
                <div class="checkbox-control">
                    <input
                        type="checkbox"
                        class="styled-checkbox no-margin"
                        id="filter_by_centre"
                        name="filter_by_centre"
                        @checked(request('filter_by_centre'))
                        onChange="submitSearchForm()"
                    />
                    <label for="filter_by_centre">
                        Show only my {{ $programme ? 'households' : 'families' }}
                    </label>
                </div>

                {{-- Name search --}}
                <div class="search-control">
                    <label for="family_name">Search by name</label>
                    <div class="search-actions">
                        <input
                            type="text"
                            name="family_name"
                            id="family_name"
                            autocomplete="off"
                            spellcheck="false"
                            onkeyup="searchForm()"
                            placeholder="Enter {{ $programme ? 'household' : 'family' }} name"
                            aria-label="{{ $programme ? 'Household' : 'Family' }} Name"
                            value="{{ request('family_name', '') }}"
                        />

                        <input type="hidden" name="fuzzy" value="{{ $fuzzy }}">

                        <div class="fuzzy-search">
                            <button class="btn" onClick="toggleFuzzySearch(); return false;">
                                <i
                                    id="fuzzy-search-icon"
                                    class="fa fa-dot-circle-o {{ $fuzzy ? 'fuzzy-on' : '' }}"
                                    aria-hidden="true"
                                ></i>
                            </button>
                            <div class="fuzzy-search-content" id="fuzzy-search-content">
                                <a href="#" id="fuzzy-search-exact"
                                   @class(['fuzzy-text-on' => !$fuzzy]) onClick="setExactSearch()">Exact</a>
                                <a href="#" id="fuzzy-search-fuzzy"
                                   @class(['fuzzy-text-on' => $fuzzy]) onClick="setFuzzySearch()">Fuzzy</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div>
            <table id="registrations">
                <thead>
                <tr>
                    <th>
                        Name
                        <span class="sort-link-container">
                            @include('store.partials.sortableChevron', [
                                'route' => 'store.registration.index',
                                'orderBy' => 'name',
                                'direction' => request('direction'),
                            ])
                        </span>
                    </th>
                    <th class="center">RV-ID</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($registrations as $registration)
                    @if ($registration->family)
                        @php $isActive = $registration->family->status() === true; @endphp
                        <tr @class(['active' => $isActive, 'inactive' => !$isActive])>
                            <td class="pri_carer">
                                <div>{{ $registration->family->carers->first()->name }}</div>
                                @if (request('centre') != $registration->centre->id)
                                    <div class="secondary_info">{{ $registration->centre->name }}</div>
                                @endif
                            </td>
                            <td class="center">{{ $registration->family->rvid }}</td>
                            <td class="right no-wrap">
                                @if ($isActive)
                                    <a href="{{ route('store.registration.voucher-manager', ['registration' => $registration->id]) }}"
                                       class="link inline-link-button">
                                        <div class="link-button">
                                            <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Vouchers
                                        </div>
                                    </a>
                                    <a href="{{ route('store.registration.edit', ['registration' => $registration->id]) }}"
                                       class="link inline-link-button">
                                        <div class="link-button">
                                            <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Edit
                                        </div>
                                    </a>
                                @else
                                    <div class="link-button link-button-small disabled">
                                        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Vouchers
                                    </div>
                                    <a href="{{ route('store.registration.view', ['registration' => $registration->id]) }}"
                                       class="link inline-link-button">
                                        <div class="link-button view">View</div>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>

        <div>
            {{ $registrations->links() }}
            Showing
            {{ ($registrations->currentPage() - 1) * $registrations->perPage() + ($registrations->total() ? 1 : 0) }}
            to
            {{ ($registrations->currentPage() - 1) * $registrations->perPage() + count($registrations) }}
            of
            {{ $registrations->total() }}
            Results
        </div>
    </div>

    <script>
        var typingTimer;
        var doneTypingInterval = 750;

        function searchForm() {
            clearTimeout(typingTimer);
            if ($("#family_name").val().length >= 3) {
                typingTimer = setTimeout(function () {
                    $("#searchform").submit();
                }, doneTypingInterval);
            }
        }

        function submitSearchForm() {
            $("#searchform").submit();
        }

        function toggleFuzzySearch() {
            if ($("input[name='fuzzy']").val() === "0") {
                setFuzzySearch();
            } else {
                setExactSearch();
            }
        }

        function setExactSearch() {
            $("input[name='fuzzy']").val(0);
            $("#fuzzy-search-exact").addClass("fuzzy-text-on");
            $("#fuzzy-search-fuzzy").removeClass("fuzzy-text-on");
            $("#fuzzy-search-icon").removeClass("fuzzy-on");
            submitSearchForm();
        }

        function setFuzzySearch() {
            $("input[name='fuzzy']").val(1);
            $("#fuzzy-search-exact").removeClass("fuzzy-text-on");
            $("#fuzzy-search-fuzzy").addClass("fuzzy-text-on");
            $("#fuzzy-search-icon").addClass("fuzzy-on");
            submitSearchForm();
        }
    </script>

@endsection
