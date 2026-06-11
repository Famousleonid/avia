@foreach($workorders as $workorder)
    @php
        $completedDate = $workorder->done_at ?? $workorder->doneDate();
        $shippingLogDate = static fn ($date): string => $date ? \Carbon\Carbon::parse($date)->format('d/M/Y') : '';
    @endphp
    <tr class="shipping-log-row" data-update-url="{{ route('shipping-log-book.update', $workorder) }}">
        <td class="text-center fw-semibold">
            <a href="{{ route('mains.show', $workorder) }}" class="text-decoration-none text-info">
                w{{ $workorder->number }}
            </a>
        </td>
        <td class="text-center">{{ $workorder->unit?->part_number ?? '' }}</td>
        <td class="text-center" title="{{ $workorder->customer?->name ?? '' }}">
            {{ $workorder->customer?->name ?? '' }}
        </td>
        <td class="text-center">{{ $workorder->customer_po }}</td>
        <td class="text-center">
            {{ $shippingLogDate($completedDate) }}
        </td>
        <td class="shipping-log-col-shipment">
            <input
                type="text"
                name="shipping_shipment_at"
                maxlength="11"
                value="{{ $shippingLogDate($workorder->shipping_shipment_at) }}"
                class="form-control form-control-sm shipping-log-input shipping-log-date js-shipping-field"
                placeholder="..."
                data-project-date
                data-project-date-capital
                autocomplete="off"
            >
        </td>
        <td class="shipping-log-col-forwarder">
            <input
                type="text"
                name="shipping_freight_forwarder"
                maxlength="255"
                value="{{ $workorder->shipping_freight_forwarder }}"
                class="form-control form-control-sm shipping-log-input js-shipping-field"
                autocomplete="off"
            >
        </td>
        <td class="shipping-log-col-awb">
            <input
                type="text"
                name="shipping_awb_no"
                maxlength="255"
                value="{{ $workorder->shipping_awb_no }}"
                class="form-control form-control-sm shipping-log-input js-shipping-field"
                autocomplete="off"
            >
        </td>
        <td class="shipping-log-col-notes">
            <textarea
                name="shipping_notes"
                maxlength="2000"
                rows="1"
                class="form-control form-control-sm shipping-log-notes js-shipping-field"
            >{{ $workorder->shipping_notes }}</textarea>
        </td>
        <td class="text-center shipping-log-col-action no-print">
            <button type="button" class="btn btn-sm btn-outline-success shipping-log-save" title="Save">
                <i class="bi bi-check2"></i>
            </button>
            <span class="shipping-log-status text-secondary"></span>
        </td>
    </tr>
@endforeach
