@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Full Collection History'])

    <div class="content history">
        <div>
            <h3>{{ $pri_carer->name }}</h3>
            <a href="{{ route("store.registration.voucher-manager", ['id' => $registration->id ]) }}" class="link">
            <div class="link-button link-button-large">
                <i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher manager
            </div>
          </a>
        </div>
        @if ($bundles->count() > 0)
            <table class="outer">
                <thead>
                    <tr>
                        <th>Week Commencing</th>
                        <th>Weekly Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <!-- loop through each week in bundles by week -->
                    @foreach ($bundles_by_week as $date => $week)
                        <tr class="@if(!$week)disabled @endif week-row">
                            <td>{{ $date }}</td>
                            @if ($week)
                                <td>{{ $week->amount }}</td>
                            @else
                                <td>0</td>
                            @endif
                            <td>
                                @if ($week->amount > 0)
                                    <i class="fa fa-caret-down rotate" aria-hidden="true"></i>
                                @endif
                            </td>
                        </tr>
                        @if ($week)
                            @foreach($week as $bundle)
                                <tr class="bundle-row">
                                    <td colspan="3">
                                        <table class="bundle-table">
                                            <tr>
                                                <td>
                                                    <i class="fa fa-calendar"></i>
                                                    Date Collected:
                                                    {{ $bundle->disbursed_at->format('l jS F Y') }}
                                                </td>
                                                <td>
                                                    <i class="fa fa-home"></i>
                                                    Collected At:
                                                    {{ $bundle->disbursingCentre->name }}
                                                </td>
                                                <td rowspan="2">
                                                    <i class="fa fa-ticket button-icon" aria-hidden="true"></i>
                                                    {{ $bundle->vouchers->count() }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <i class="fa fa-user"></i>
                                                    Collected By:
                                                    {{ $bundle->collectingCarer->name }}
                                                </td>
                                                <td>
                                                    <i class="fa fa-users"></i>
                                                    Allocated By:
                                                    {{ $bundle->disbursingUser->name }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="content-warning">This family has not collected.</p>
        @endif
    </div>

    <script type="text/javascript">
        $(document).ready(
            function () {
                $("table.outer").addClass("show");
                $(".bundle-row").hide();
                $("tbody").click(function(event) {
                    event.stopPropagation();
                    var $target = $(event.target);
                    $target.closest("tr").find(".rotate").toggleClass("up");
                    if ( $target.closest("tr").hasClass("bundle-row")) {
                       $target.slideUp();
                    } else if ( $target.closest("tr").next().hasClass("bundle-row")){
                       $target.closest("tr").nextUntil(".week-row", ".bundle-row").slideToggle();
                    } else {
                       return;
                    }
                });
            }
        );
    </script>
@endsection
