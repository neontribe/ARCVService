@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Search for a ' . ($programme ? 'household' : 'family')])
    @includeWhen(Session::has('message'), 'store.partials.success')
    <div class="content search">
        <div class="control-container">
            <form action="{{ URL::route('store.registration.index') }}" method="GET" id="searchform">
                {!! csrf_field() !!}
                {{-- Families left checkbox --}}
                <div class="checkbox-control">
                    <input type="checkbox" class="styled-checkbox no-margin" onChange="submitSearchForm()" id="families_left" name="families_left" {{
                    Request::get("families_left") ? 'checked' : '' }} />
                    <label for="families_left">Show {{ $programme ? 'households' : 'families'}} who have left</label>
                </div>
                {{-- Name search --}}
                <div class="search-control">
                    <label for="family_name">Search by name</label>
                    <div class="search-actions">
                        <input type="text" name="family_name" id="family_name" autocomplete="off" autocorrect="off"
                               spellcheck="false" onkeyup="searchForm()"
                               placeholder="Enter {{ $programme ? 'household' : 'family'}} name" aria-label="{{ $programme ? 'Household' : 'Family'}} Name"
                               value="{{ Request::get("family_name") ?? '' }}" />

                        <input type="hidden" name="fuzzy" value="{{ $fuzzy }}">
                        <div class="fuzzy-search">
                            <button class="btn" onClick="toggleFuzzySearch(); return false;">
                                <i id="fuzzy-search-icon"
                                   @if ($fuzzy)
                                        class="fa fa-dot-circle-o fuzzy-on"
                                   @else
                                        class="fa fa-dot-circle-o"
                                   @endif
                                   aria-hidden="true"></i>
                            </button>
                            <div class="fuzzy-search-content" id="fuzzy-search-content">
                                <a href="#"
                                   id="fuzzy-search-exact"
                                   @if (! $fuzzy)
                                        class="fuzzy-text-on"
                                   @endif
                                   onClick="setExactSearch()">Exact</a>
                                <a href="#"
                                   id="fuzzy-search-fuzzy"
                                   @if ($fuzzy)
                                        class="fuzzy-text-on"
                                   @endif
                                   onClick="setFuzzySearch()">Fuzzy</a>
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
                    <td>Name<span class="sort-link-container">@include('store.partials.sortableChevron', ['route' =>
                            'store.registration.index', 'orderBy' => 'name', 'direction' => request('direction')
                            ])</span></td>
                    <td class="center">RV-ID</td>
                    <td></td>
                </tr>
                </thead>
                <tbody>
                @foreach ($registrations as $registration)
                    @if ($registration->family)
                        @php(\App\Http\Controllers\Store\FamilyController::status($registration))
                        @if( $registration->family->status === true)
                            <tr class='active'>
                        @else
                            <tr class='inactive'>
                        @endif
                            <td class="pri_carer">
                                <div>{{ $registration->family->carers->first()->name }}</div>
                                {!! Request::get("centre") == ($registration->centre->id) ?
                                null : '<div class="secondary_info">' . $registration->centre->name . '</div>'
                                !!}
                            </td>
                            <td class="center">{{ $registration->family->rvid }}</td>
                            <td class="right no-wrap">
                                @if( $registration->family->status === true)
                                    <a href="{{ route('store.registration.voucher-manager', ['registration'=> $registration->id ]) }}"
                                       class="link inline-link-button">
                                        <div class="link-button">
                                            <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Vouchers
                                        </div>
                                    </a>
                                    <a href="{{ route('store.registration.edit', ['registration'=> $registration->id ]) }}" class="link
                            inline-link-button">
                                        <div class="link-button">
                                            <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Edit
                                        </div>
                                    </a>
                                @else
                                    <div class="link-button link-button-small disabled">
                                        <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Vouchers
                                    </div>
                                    <a href="{{ route('store.registration.view', ['registration'=> $registration->id ]) }}" class="link
                            inline-link-button">
                                        <div class="link-button view">
                                            View
                                        </div>
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
            Showing {{($registrations->currentPage()-1)* $registrations->perPage()+($registrations->total() ? 1:0)}} to {{($registrations->currentPage()-1)*$registrations->perPage()+count($registrations)}}  of  {{$registrations->total()}}  Results
        </div>
    </div>

    <script>
        function searchForm() {
            // setup before functions
            var typingTimer;                //timer identifier
            var doneTypingInterval = 750;   //time in ms (half a second)
            // on keyup, start the countdown
            $("#family_name").keyup(function(){
                clearTimeout(typingTimer);
                if ($("#family_name").val()) {
                    typingTimer = setTimeout(doneTyping, doneTypingInterval);
                }
            });
            // user is "finished typing" do something
            function doneTyping () {
                $("#searchform").submit();
            }
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
            document.getElementById("fuzzy-search-exact").classList.add("fuzzy-text-on");
            document.getElementById("fuzzy-search-fuzzy").classList.remove("fuzzy-text-on");
            document.getElementById("fuzzy-search-icon").classList.remove("fuzzy-on");
        }

        function setFuzzySearch() {
            $("input[name='fuzzy']").val(1);
            document.getElementById("fuzzy-search-exact").classList.remove("fuzzy-text-on");
            document.getElementById("fuzzy-search-fuzzy").classList.add("fuzzy-text-on");
            document.getElementById("fuzzy-search-icon").classList.add("fuzzy-on");
        }

        function submitSearchForm() {
            if (document.getElementById("family_name").val().length >= 3) {
                document.getElementById("searchform").submit();
            }
        }
    </script>
@endsection
