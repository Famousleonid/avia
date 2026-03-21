<div class="log-card-partial">
    <div class="table-responsive" style="max-height: calc(100vh - 320px); overflow-y: auto;">
        <table class="table table-bordered table-hover dir-table align-middle bg-gradient">
            <thead class="table-dark" style="position: sticky; top: 0; z-index: 5;">
                <tr>
                    <th class="text-primary text-center">{{ __('Description') }}</th>
                    <th class="text-primary text-center">{{ __('Part Number') }} / {{ __('Assy PN') }}</th>
                    <th class="text-primary text-center">{{ __('Serial Number') }}</th>
                    <th class="text-primary text-center">{{ __('ASSY Serial Number') }}</th>
                    <th class="text-primary text-center">{{ __('Reason to Removed') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tableRows as $row)
                    @php
                        $comp = $row['component'];
                        $hasSerialNumber = !empty($row['serial_number']);
                        $hasAssySerialNumber = !empty($row['assy_serial_number']);
                    @endphp
                    <tr>
                        <td>
                            {{ $comp ? $comp->name : '' }}
                            @if($hasAssySerialNumber && !$hasSerialNumber)
                                , S/A
                            @endif
                        </td>
                        <td>
                            @if($hasAssySerialNumber && !$hasSerialNumber)
                                {{ $comp ? $comp->assy_part_number : '' }}
                            @else
                                {{ $comp ? $comp->part_number : '' }}
                            @endif
                        </td>
                        <td>{{ $row['serial_number'] }}</td>
                        <td>{{ $row['assy_serial_number'] }}</td>
                        <td>
                            @if($row['reason'])
                                @php
                                    $code = $codes->firstWhere('id', $row['reason']);
                                @endphp
                                {{ $code ? $code->name : $row['reason'] }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(empty($tableRows))
        <p class="text-center text-muted mt-3">{{ __('No components with log_card=1 for this manual.') }}</p>
    @endif
</div>
