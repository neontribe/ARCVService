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
            @foreach ($week_commencing as $week)
                <tr>
                    <td>{{ $week }}</td>
                    <td>8</td>
                    <td>
                        <i class="fa fa-caret-down" aria-hidden="true"></i>
                    </td>
                </tr>
                @foreach ($bundles as $bundle)
                    <tr>
                        <td colspan="3">
                            <div>
                                <p>
                                    <span>
                                        <i class="fa fa-calendar"></i>
                                        Date Collected:
                                    </span>
                                    {{ $bundle->disbursed_at }}
                                </p>
                                <p>
                                    <span>
                                        <i class="fa fa-home"></i>
                                        Collected At:
                                    <span>
                                    {{ $bundle->disbursingCentre }}
                                </p>
                            </div>
                            <div>
                                <p>
                                    <span>
                                        <i class="fa fa-user"></i>
                                        Collected By:
                                    </span>
                                    {{ $bundle->collectingCarer }}
                                </p>
                                <p>
                                    <span>
                                        <i class="fa fa-users"></i>
                                        Allocated By:
                                    </span>
                                    {{ $bundle->disbursingUser }}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforeach
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
