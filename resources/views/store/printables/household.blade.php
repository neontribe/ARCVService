@extends('store.layouts.printable_master')

@section('title', $sheet_title)

@section('content')


    @include('store.printables.partials.masthead', ['specificPrintNote' => 'Print a new form every 4 weeks so you have the most up to date information available.'])

    <div class="content">
        <h1>Weekly Voucher Collection Sheet</h1>
        <table>
            <tr>
                <td width="50%" style="text-align:left; border:1px solid white;"><h2>Centre: {{ $centre_name }}</h2></td>
                <td style="text-align:left; border:1px solid white;">Week commencing:
                    <div class="date_placeholder">D</div>
                    <div class="date_placeholder">D</div>
                    &nbsp;
                    <div class="date_placeholder">M</div>
                    <div class="date_placeholder">M</div>
                    &nbsp;
                    <div class="date_placeholder">Y</div>
                    <div class="date_placeholder">Y</div>
                    <div class="date_placeholder">Y</div>
                    <div class="date_placeholder">Y</div>
                </td>
            </tr>
        </table>
        <br/>
        <table>
            <thead>
                <tr class="titles">
                    <th class='hidden_column'></th>
                    <th>RV-ID</th>
                    <th>Weekly Voucher allocation</th>
                    <th>Amount of vouchers given out</th>
                    <th style="width:20%;color:white;">Voucher Numbers</th>
                    <th>Date collected</th>
                    <th>Initials of person who collected</th>
                </tr>
            </thead>
            <tbody>
                @foreach ( $regs as $index => $reg )

                <tr>
                    <td class='hidden_column'></td>
                    <td class="colspan">{{ $reg["family"]->rvid }}</td>
                    <td class="colspan vouchers"><i class="fa fa-ticket" aria-hidden="true"></i> {{ $reg["entitlement"] }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @endforeach
            </tbody>

        </table>
        <table class="info general">
          <tr>
            <td valign="top">
              <h3><i class="fa fa-question-circle" aria-hidden="true"></i> Hints &amp; Tips</h3>
              <p>When did you last chat to them about how they're finding shopping at the market?</p>
            </td>
          </tr>
        </table>

    </div>
@endsection

<style>
    tr > th {
        text-align:center;
    }
    .hidden_column {
        border:1px solid white;
        border-right: 1px solid #a74e94;
    }
    .date_placeholder {
        background:lightgrey;
        color:white;
        padding:10px;
        height:10px;
        width:10px;
        display: inline-block;
        text-align: center;
    }
</style>
