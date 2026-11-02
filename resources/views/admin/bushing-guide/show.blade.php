@extends('admin.master')

@section('style')
<link rel="stylesheet" href="{{ asset('css/bushing-guide.css') }}?v={{ filemtime(public_path('css/bushing-guide.css')) }}">
@endsection

@section('content')
@php
    // Preserve stored sheet indexes used by cell updates; only change presentation order.
    $orderedSheets = collect($document['sheets'])->sortBy(fn ($sheet) => $sheet['name'] === 'Bushing' ? 0 : 1);
    $initialSheet = $orderedSheets->keys()->first();
@endphp
<section class="bushing-guide" id="bushingGuide" data-save-url="{{ route('bushing-guide.update', $workorder) }}" data-revision="{{ $guide->revision }}">
    <header class="guide-toolbar no-print">
        <div class="guide-heading">
            <h1>{{ $guide->title }}</h1><small>Shared manual form · {{ $manualNumbers->implode(' / ') }}</small>
        </div>
    </header>
    <nav class="guide-tabs nav nav-tabs no-print" role="tablist" aria-label="Workbook sheets">
        @foreach($orderedSheets as $s => $sheet)
            <button type="button" id="guide-tab-{{ $s }}" class="nav-link {{ $s === $initialSheet ? 'active' : '' }}" role="tab" data-guide-tab="{{ $s }}" aria-selected="{{ $s === $initialSheet ? 'true' : 'false' }}" aria-controls="guide-sheet-{{ $s }}" tabindex="{{ $s === $initialSheet ? '0' : '-1' }}">{{ $sheet['name'] }}</button>
        @endforeach
        @if(!$canEdit)<span class="text-secondary small">View and print</span>@endif
    </nav>
    <div class="guide-paper">
        <div class="guide-actions no-print">
            @if($canEdit)
                <button class="btn btn-success btn-sm" id="guideSave" disabled>Save</button>
                <button class="btn btn-outline-secondary btn-sm" id="guideDiscard" disabled>Cancel changes</button>
            @endif
            <a href="{{ route('mains.show', $workorder) }}" class="btn btn-outline-info btn-sm" data-spinner>← W {{ $workorder->number }}</a>
            <button class="btn btn-outline-info btn-sm" data-guide-print>Print</button>
        </div>
        <div class="guide-scroll">
        @foreach($orderedSheets as $s => $sheet)
            <article id="guide-sheet-{{ $s }}" role="tabpanel" aria-labelledby="guide-tab-{{ $s }}" class="guide-sheet {{ $s === $initialSheet ? 'is-active' : '' }}" data-guide-sheet="{{ $s }}">
                <table class="guide-table {{ $sheet['name'] === 'Items' ? 'guide-items-table' : 'guide-bushing-table' }}" style="--guide-table-width:{{ max(1000, array_sum($sheet['widths'])) }}px" aria-label="{{ $sheet['name'] }}">
                    <colgroup>@foreach($sheet['widths'] as $width)<col style="width:{{ $width / array_sum($sheet['widths']) * 100 }}%">@endforeach</colgroup>
                    @php($headerRows = $sheet['name'] === 'Items' ? 2 : 1)
                    <thead>
                    <tr class="guide-print-heading">
                        <th colspan="{{ count($sheet['widths']) }}">
                            <div class="guide-print-title-line">
                                <h2>{{ $guide->title }} — {{ $sheet['name'] }}</h2>
                                <p>{{ $manualNumbers->implode(' / ') }} · Revision <span data-guide-revision>{{ $guide->revision }}</span></p>
                            </div>
                        </th>
                    </tr>
                    @foreach(array_slice($sheet['rows'], 0, $headerRows) as $row)
                        @include('admin.bushing-guide.row')
                    @endforeach
                    </thead>
                    <tbody>
                    @foreach(array_slice($sheet['rows'], $headerRows) as $row)
                        @include('admin.bushing-guide.row')
                    @endforeach
                    </tbody>
                </table>
            </article>
        @endforeach
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script src="{{ asset('js/bushing-guide.js') }}?v={{ filemtime(public_path('js/bushing-guide.js')) }}"></script>
@endsection
