@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Full Collection History'])

    <div class="content history">
        <h3>{{ $pri_carer->name }}</h3>
        <table>
            <tr>
                <th>Week Commencing</th>
                <th>Amount Collected</th>
                <th></th>
            </tr>
            @foreach ($bundles_by_week as $week => $bundle)
                <tr>
                    <td>{{ $week }}</td>
                    @if ($bundle)
                        <td>{{ $bundle->vouchers->count() }}</td>
                    @else
                        <td>0</td>
                    @endif
                    <td>
                        <i class="fa fa-caret-down" aria-hidden="true"></i>
                    </td>
                </tr>
                @if ($bundle)
                    <tr>
                        <td colspan="3">
                            <div>
                                <p>
                                    <span>
                                        <i class="fa fa-calendar"></i>
                                        Date Collected:
                                        {{ $bundle->disbursed_at }}
                                    </span>
                                </p>
                                <p>
                                    <span>
                                        <i class="fa fa-home"></i>
                                        Collected At:
                                        {{ $bundle->disbursingCentre->name }}
                                    <span>
                                </p>
                            </div>
                            <div>
                                <p>
                                    <span>
                                        <i class="fa fa-user"></i>
                                        Collected By:
                                        {{ $bundle->collectingCarer->name }}
                                    </span>
                                </p>
                                <p>
                                    <span>
                                        <i class="fa fa-users"></i>
                                        Allocated By:
                                        {{ $bundle->disbursingUser->name }}
                                    </span>
                                </p>
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>
    </div>

    <script type="text/javascript">
        $(document).ready(
            function () {
                $("td[colspan=3]").find("p").hide();
                $("table").click(function(event) {
                    event.stopPropagation();
                    var $target = $(event.target);
                    if ( $target.closest("td").attr("colspan") > 1 ) {
                       $target.slideUp();
                    } else {
                       $target.closest("tr").next().find("p").slideToggle();
                    }
                });
            }
        );
    </script>
@endsection
