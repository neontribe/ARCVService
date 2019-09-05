@extends('store.layouts.service_master')

@section('title', 'Voucher Manager')

@section('content')

    @include('store.partials.navbar', ['headerTitle' => 'Full Collection History'])

    <div class="content history">
        <div>

            <p>{{$datedBundleArray}}</p>

         
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
