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
            <tr>
                <td>22/10/2018</td>
                <td>8</td>
                <td>
                    <i class="fa fa-caret-down" aria-hidden="true"></i>
                </td>
            </tr>
                <td colspan=3>
                  <p>
                      <span>
                          <i class="fa fa-calendar"></i>
                          Date Collected:
                      </span>
                      03/03/1994
                  </p>
                  <p>
                      <span>
                          <i class="fa fa-home"></i>
                          Collected At:
                      <span>
                      First Place Children's Centre
                  </p>
                  <p>
                      <span>
                          <i class="fa fa-user"></i>
                          Collected By:
                      </span>
                      Mr Higgins
                  </p>
                  <p>
                      <span>
                          <i class="fa fa-users"></i>
                          Allocated By:
                      </span>
                      Worker 1
                  </p>
                </td>
            </tr>
        </table>
    </div>

@endsection
