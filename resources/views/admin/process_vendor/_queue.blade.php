            @foreach($rows->groupBy('process_group')->sortKeys() as $name => $group)
            <section class="pv-process" data-process-group="{{ $name }}">
                <h2><b class="pv-process-title">{{ $name }}</b> <span class="pv-process-count">{{ $group->groupBy('workorder_id')->count() }} WO · {{ $group->sum('qty') }} parts</span></h2>
                @foreach($group->groupBy('workorder_id') as $woRows)
                @php($first = $woRows->first())
                <details class="pv-workorder" data-workorder="{{ $first['workorder_id'] }}" data-group-key="{{ $first['workorder_id'].'|'.$name }}">
                    <summary><label class="pv-wo-choice"><input class="pv-pick" type="checkbox" aria-label="Select WO {{ $first['wo'] }} for {{ $name }}"><span class="pv-wo-title">WO {{ $first['wo'] }}</span></label><span class="pv-part-count">{{ $woRows->sum('qty') }} parts <small>· {{ $woRows->count() }} lines</small></span><span class="pv-wo-processes" hidden></span><span class="pv-technician">{{ $first['technician'] }}</span><span class="pv-status pv-wo-status {{ $woRows->every(fn ($r) => $r['checked']) ? 'checked' : '' }}">{{ $woRows->every(fn ($r) => $r['checked']) ? 'At shipping' : 'Not checked' }}</span><a href="{{ route('mains.show', $first['workorder_id']) }}" class="pv-open-wo" aria-label="Open WO {{ $first['wo'] }}">Open WO <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></summary>
                    <div class="pv-table-wrap"><table class="pv-table"><thead><tr><th>IPL</th><th>Part / Description</th><th>S/N</th><th>Qty</th><th>Shipping</th></tr></thead><tbody>
                    @foreach($woRows as $row)
                    <tr data-key="{{ $row['key'] }}" data-search="{{ mb_strtolower(implode(' ', [$row['wo'], $row['part_number'], $row['description'], $row['technician'], $row['serial'], $row['source'], $row['specification'], $row['process_name'], $row['process_group'], $row['manual'], $row['ipl']])) }}">
                        <td class="pv-ipl">{{ $row['ipl'] ?: '—' }}</td>
                        <td><div class="pv-part-line"><strong>{{ $row['part_number'] ?: '—' }}</strong><span>{{ $row['description'] }}</span><small class="pv-source">{{ $row['process_name'] }} · {{ $row['source'] }} · CMM {{ $row['manual'] ?: '—' }}</small></div>
                            @if($row['specification'])<details class="pv-spec"><summary>Process / specification</summary><div>{{ $row['specification'] }}</div></details>@endif
                        </td>
                        <td>{{ $row['serial'] ?: '—' }}</td><td class="pv-qty">{{ $row['qty'] }}</td>
                        <td class="pv-shipping">
                            @if($row['repair_order_id'])<a class="pv-status assigned" href="{{ route('process-vendor.orders.show', $row['repair_order_id']) }}">RO prepared</a>@if($row['changed'])<small class="text-danger d-block">Details changed — review RO</small>@endif
                            @elseif($row['checked'])<span class="pv-status checked" title="Checked by {{ $row['checked_by'] }}">At shipping</span>
                            @else<span class="pv-status">{{ $row['changed'] ? 'Check again' : 'Not checked' }}</span>@endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody></table></div>
                </details>
                @endforeach
            </section>
            @endforeach
