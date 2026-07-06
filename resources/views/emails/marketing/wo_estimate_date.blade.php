@php
    $profile = $customer?->marketingProfile;
    $workorderUrl = $workorder ? route('mains.show', $workorder->id) : null;
    $marketingUrl = $customer ? route('marketing.index', ['customer' => $customer->id]) : null;
    $done = $workorder?->isDone();
    $status = $done ? 'Complete' : ($workorder?->approve_at ? 'In Process' : 'Waiting Approval');
    $estimateDate = format_project_date($notification->estimate_date) ?? (string) $notification->estimate_date;
    $estimateAmount = $workorder?->wo_estimate_amount !== null ? '$' . number_format((float) $workorder->wo_estimate_amount, 2, '.', ',') : '-';
@endphp

<p>WO Estimate Date was set in Marketing.</p>

<table style="border-collapse: collapse; width: 100%;" cellpadding="6">
    <tbody>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Customer</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $customer?->name ?? '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">WO #</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $workorder?->number ? 'W' . $workorder->number : '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">WO Estimate Date</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $estimateDate }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">WO Estimate</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $estimateAmount }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Status</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $status }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">RO #</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $workorder?->customer_po ?? '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Part Number</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $workorder?->unit?->part_number ?? '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Description</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $workorder?->displayDescription() ?? $workorder?->description ?? '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Serial Number</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $workorder?->serial_number ?? '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Task</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $workorder?->instruction?->name ?? '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Terms</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $workorder?->wo_terms ?? '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Invoice</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $workorder?->sales_invoice_amount !== null ? '$' . number_format((float) $workorder->sales_invoice_amount, 2, '.', ',') : '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Invoice Date</th>
        <td style="border-bottom: 1px solid #ddd;">{{ format_project_date($workorder?->sales_invoice_date) ?? '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">Ship Date</th>
        <td style="border-bottom: 1px solid #ddd;">{{ format_project_date($workorder?->shipping_shipment_at) ?? '-' }}</td>
    </tr>
    <tr>
        <th align="left" style="border-bottom: 1px solid #ddd;">AWB #</th>
        <td style="border-bottom: 1px solid #ddd;">{{ $workorder?->shipping_awb_no ?? '-' }}</td>
    </tr>
    </tbody>
</table>

@if($workorderUrl || $marketingUrl)
    <p>
        @if($workorderUrl)
            <a href="{{ $workorderUrl }}">Open workorder</a>
        @endif
        @if($workorderUrl && $marketingUrl)
            |
        @endif
        @if($marketingUrl)
            <a href="{{ $marketingUrl }}">Open marketing customer</a>
        @endif
    </p>
@endif
