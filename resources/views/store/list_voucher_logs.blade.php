{{--by intern 4--}}

@extends('store.layouts.service_master')

@section('title', 'Voucher Log Downloads')

@section('content')

@include('store.partials.navbar', ['headerTitle' => 'Export Voucher Logs'])

<!-- <h2 style="text-align:center;">Voucher Logs</h2> -->
<div id="container">
    <div class="content search">
        <!-- this is a weird class name for what it's actually used for?-->
        <table id='voucherExportTable'>
            <thead>
            <tr>
                <th class="center">Date Included</th>
                <th class="center">File Size</th>
                <th class="center">Last Modified</th>
                <th class="center">Download Link</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($data as $displayName => $metadata)
                <tr class="active">
                    <td class="center">{{$displayName}}</td>
                    <td class="center">{{$metadata["fileSize"]}}</td>
                    <td class="center">{{$metadata["displayDate"]}}</td>
                    <td class="center">
                        <a href="{{$metadata["downloadLink"]}}" target="_blank" class="centre link inline-link-button">
                            <div class="link-button">
                                <i class="fa fa-download" aria-hidden="true"></i> Download
                            </div>
                        </a>
                    </td>
                </tr>
            @endforeach

            </tbody>
        </table>
    </div>
</div>



@endsection
