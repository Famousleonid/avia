@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
            width: 850px;
        }

        .border-all {
            border: 1px solid black;
        }
        .border-all-b {
            border: 2px solid black;
        }

        .border-l-t-r {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-l-b-r {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-lll-b-r {
            border-left: 8px  solid lightgrey;
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-b-r {
            border-bottom: 1px solid black;
            border-right: 1px solid black;
        }
        .border-r {
            border-right: 1px solid black;
        }
        .border-l-b-rrr {
            border-left: 1px solid black;
            border-bottom: 1px solid black;
            border-right: 5px solid black;
        }
        .border-l-b {
            border-left: 1px solid black;
            border-bottom: 1px solid black;

        }
        .border-t-r {
            border-top: 1px solid black;
            border-right: 1px solid black;
        }
        .border-t-b {
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-l-t-b {
            border-left: 1px solid black;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-l-t {
            border-left: 1px solid black;
            border-top: 1px solid black;
        }
        .border-l {
            border-left: 1px solid black;
        }
        .border-ll-bb {
            border-left: 2px solid black;
            border-bottom: 2px solid black;

        }
        .border-ll-bb-rr {
            border-left: 2px solid black;
            border-bottom: 2px solid black;
            border-right: 2px solid black;
        }
        .border-bb {
            border-bottom: 2px solid black;
        }
        .border-b {
            border-bottom: 1px solid black;
        }
        .border-t-r-b {
            border-top: 1px solid black;
            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .border-t {
            border-top: 1px solid black;

        }
        .border-tt-gr {
            border-top: 3px solid gray;

        }
        .border-r-b {

            border-right: 1px solid black;
            border-bottom: 1px solid black;
        }
        .text-center {
            text-align: center;

        }

        .text-black {
            color: #000;
        }

        /*.p-1, .p-2, .p-3, .p-4 {*/
        /*    padding: 0.25rem;*/
        /*    padding: 0.5rem;*/
        /*    padding: 0.75rem;*/
        /*    padding: 1rem;*/
        /*}*/

        .topic-header {
            width: 100px;
        }

        .topic-content {
            width: 600px;
        }

        .topic-content-2 {
            width: 701px;
        }

        .hrs-topic, .trainer-init {
            width: 100px;
        }
        .hrs-topic-1,.trainer-init-1 {
            width: 98px;
        }
        .trainer-init-1 {
            width: 99px;
        }
        .fs-9 {
            font-size: 0.9rem; /* или любое другое подходящее значение */
        }
        .fs-8 {
            font-size: 0.8rem; /* или любое другое подходящее значение */
        }
        .fs-7 {
            font-size: 0.7rem; /* или любое другое подходящее значение */
        }
        .fs-75 {
            font-size: 0.75rem; /* или любое другое подходящее значение */
        }
        .fs-4 {
            font-size: 0.4rem; /* или любое другое подходящее значение */
        }



        .parent {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 0px;
        }

        .div1 {
            grid-column: span 2 / span 2;
        }

        .div2 {
            grid-column-start: 3;
        }

        .div3 {
            grid-column: span 3 / span 3;
            grid-column-start: 4;
        }

        .div4 {
            grid-column: span 4 / span 4;
            grid-column-start: 7;
        }




    </style>
    <div class="card-shadow ">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between">
                <div>
                    <h4 class="text-primary me-5">{{__('Work Order: ')}} {{$current_wo->number}}</h4>
                    <div>
                        <h4 class="text-center ps-1" >{{__(' REPAIR and MODIFICATION RECORD')}}</h4>
                    </div>

                </div>
                <div class="ps-2 d-flex" style="width: 400px;">
                    @if($current_wo->rm_report)
                        <a href="{{ route('rm_reports.edit', $current_wo->id) }}" class="btn btn-outline-primary"
                           style="height: 60px; width: 180px">
                            <i class="fas fa-edit"></i> Edit WorkOrder R&M Record
                        </a>
                    @else
                        <a href="{{ route('rm_reports.create', $current_wo->id) }}" class="btn btn-success"
                           style="height:60px; width: 180px">
                            <i class="fas fa-plus"></i> Create WorkOrder R&M Record
                        </a>
                    @endif

                </div>


                <div class="ps-2 d-flex" style="width: 600px;">
{{--                    @if($log_card)--}}
                        <a href="{{ route('rm_reports.rmRecordForm', ['id'=> $current_wo->id]) }}"
                           class="btn btn-outline-warning mb-3 formLink "
                           target="_blank"
                           id="#" style=" height: 40px">
                            <i class="bi bi-file-earmark-excel">R & M Record Form </i>
                        </a>
{{--                    @endif--}}
                </div>

                <div class="">
                    <a href="{{ route('tdrs.show', ['tdr'=>$current_wo->id]) }}"
                       class="btn btn-outline-secondary mt-3" style="min-height: 40px">{{ __('Back to Work Order') }} </a>
                </div>





            </div>

        </div>

        <div class="card-body">
            <div class="">
                <div class="parent mt-2">
                    <div class="div1 border-all  text-center align-content-center" style="border-color: blue; min-height: 36px">Part
                        Description</div>
                    <div class="div2 border-t-r-b text-center align-content-center" style="border-color: blue">Modification or Repair#</div>
                    <div class="div3 border-t-r-b text-center align-content-center" style="border-color: blue">Description of Modification or Repair</div>
                    <div class="div4 border-t-r-b text-center align-content-center" style="border-color: blue">identification
                        Method</div>
                </div>

                {{--                <table class="table table-bordered table-hover">--}}
{{--                    <thead>--}}
{{--                    <tr>--}}
{{--                        <th>Part Description</th>--}}
{{--                        <th>Modification or Repair#</th>--}}
{{--                        <th>Description of Modification or Repair</th>--}}
{{--                        <th>Select for Report</th>--}}
{{--                        <th>identification Method</th>--}}
{{--                        <th></th>--}}
{{--                    </tr>--}}
{{--                    </thead>--}}
{{--                    <tbody>--}}
{{--                    </tbody>--}}
{{--                </table>--}}
            </div>
        </div>


    </div>

@endsection
