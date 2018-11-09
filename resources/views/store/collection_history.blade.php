@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Full Collection History'])

    <div class="content history">
        <div>
          <h3>{{ $pri_carer->name }}</h3>
          <a href="{{ route("store.registration.voucher-manager", ['id' => $registration->id ]) }}" class="link">
              <div class="link-button link-button-large">
                  </i><i class="fa fa-ticket button-icon" aria-hidden="true"></i>Go to voucher manager
              </div>
          </a>
        </div>
        @if (!empty($bundles_by_week))
            <table>
                <tr>
                    <th>Week Commencing</th>
                    <th>Amount Collected</th>
                    <th></th>
                </tr>
                @foreach ($bundles_by_week as $week => $bundle)
                    <tr class="@if(!$bundle)disabled @endif">
                        <td>{{ $week }}</td>
                        @if ($bundle)
                            <td>{{ $bundle->vouchers->count() }}</td>
                        @else
                            <td>0</td>
                        @endif
                        <td>
                            @if ($bundle)
                                <i class="fa fa-caret-down rotate" aria-hidden="true"></i>
                            @endif
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
                                            {{ $bundle->disbursed_at->format('l jS F Y') }}
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
        @else
            <p>This family has not collected.</p>
        @endif
    </div>

    <script type="text/javascript">
        $(document).ready(
            function () {
                $("table").addClass("show");
                $("td[colspan=3]").find("p").hide();
                $("table").click(function(event) {
                    event.stopPropagation();
                    $(".rotate").toggleClass("up");
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
