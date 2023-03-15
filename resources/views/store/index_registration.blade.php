@extends('store.layouts.service_master')

@section('title', 'Check / Update Registration')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Search for a family'])
    @includeWhen(Session::has('message'), 'store.partials.success')
    <div class="content search">
        <div class="control-container">
            <form action="{{ URL::route('store.registration.index') }}" method="GET" id="searchform">
                {!! csrf_field() !!}
                {{-- Families left checkbox --}}
                <div class="checkbox-control">
                    <input type="checkbox" class="styled-checkbox no-margin" onChange="this.form.submit()" id="families_left" name="families_left" {{
                    Request::get("families_left") ? 'checked' : '' }} />
                    <label for="families_left">Show {{ $programme ? 'households' : 'families'}} who have left</label>
                </div>
                {{-- Name search --}}
                <div class="search-control">
                    <label for="family_name">Search by name</label>
                    <div class="search-actions">
                        <input type="text" name="family_name" id="family_name" autocomplete="off" autocorrect="off"
                               spellcheck="false" onkeyup="searchForm()" placeholder="Enter {{ $programme ? 'household' : 'family'}} name" aria-label="{{ $programme ? 'Household' : 'Family'}} Name">
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
                        <tr class="{{ $registration->family->leaving_on ? 'inactive' : 'active' }}">
                            <td class="pri_carer">
                                <div>{{ $registration->family->carers->first()->name }}</div>
                                {!! Request::get("centre") == ($registration->centre->id) ?
                                null : '<div class="secondary_info">' . $registration->centre->name . '</div>'
                                !!}
                            </td>
                            <td class="center">{{ $registration->family->rvid }}</td>
                            <td class="right no-wrap">
                                @if( !isset($registration->family->leaving_on) )
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
                                    <div class="link-button link-button-small disabled">
                                        <i class="fa fa-pencil button-icon" aria-hidden="true"></i>Edit
                                    </div>
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
        </div>
    </div>

    <script>
        function searchForm() {
            // Declare variables
            var input, filter, table, tr, td, i, txtValue, checkbox;
            input = document.getElementById("family_name");
            filter = input.value.toUpperCase();
            table = document.getElementById("registrations");
            tr = table.getElementsByTagName("tr");
            checkbox = document.getElementById("families_left");

            // Loop through all table rows, and hide those who don't match the search query
            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[0];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
@endsection
