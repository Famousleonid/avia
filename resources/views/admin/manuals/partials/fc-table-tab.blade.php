@php
use Illuminate\Support\Collection;

// Collect all F&C points across all figures
$fcRows = [];
foreach ($dimensionFigures as $figure) {
    foreach ($figure->points as $point) {
        if (!$point->is_fits_clearance) continue;
        $specs = $point->specs->where('spec_type', 'measurement')->sortBy('sort_order')->values();
        if ($specs->count() < 2) continue;

        $specA = $specs[0]; // Part A (lower sort_order = inner / ID)
        $specB = $specs[1]; // Part B (outer / OD)

        // Original clearance
        $clearOrigMin = ($specA->orig_dim_min !== null && $specB->orig_dim_max !== null)
            ? round((float)$specA->orig_dim_min - (float)$specB->orig_dim_max, 4)
            : null;
        $clearOrigMax = ($specA->orig_dim_max !== null && $specB->orig_dim_min !== null)
            ? round((float)$specA->orig_dim_max - (float)$specB->orig_dim_min, 4)
            : null;

        // Effective wear limits (fall back to orig if wear not set)
        $aWearMin = $specA->wear_dim_min ?? $specA->orig_dim_min;
        $aWearMax = $specA->wear_dim_max ?? $specA->orig_dim_max;
        $bWearMin = $specB->wear_dim_min ?? $specB->orig_dim_min;
        $bWearMax = $specB->wear_dim_max ?? $specB->orig_dim_max;

        // Permitted clearance (wear)
        $permClearMax = ($aWearMax !== null && $bWearMin !== null)
            ? round((float)$aWearMax - (float)$bWearMin, 4)
            : null;

        $fcRows[] = [
            'figure'       => $figure,
            'point'        => $point,
            'specA'        => $specA,
            'specB'        => $specB,
            'clearOrigMin' => $clearOrigMin,
            'clearOrigMax' => $clearOrigMax,
            'aWearMin'     => $aWearMin,
            'aWearMax'     => $aWearMax,
            'bWearMin'     => $bWearMin,
            'bWearMax'     => $bWearMax,
            'permClearMax' => $permClearMax,
        ];
    }
}

function fcFmt($v, $d = 4) {
    if ($v === null || $v === '') return '—';
    $f = round((float)$v, $d);
    return number_format($f, $d);
}
@endphp

<div class="p-3">
    <h5 class="mb-3">Fits and Clearances</h5>

    @if(empty($fcRows))
        <div class="text-secondary">No Fits &amp; Clearances points found. Mark measurement points as F&amp;C in the Dimensions tab.</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle" style="font-size:12px;white-space:nowrap">
                <thead class="table-light">
                    <tr>
                        <th rowspan="3" class="text-center align-middle">Figure</th>
                        <th rowspan="3" class="text-center align-middle">Ref.<br>No.</th>
                        <th rowspan="3" class="text-center align-middle">Mating IPL<br>Item No.</th>
                        <th colspan="4" class="text-center">Original Manufacturer Limits</th>
                        <th colspan="3" class="text-center">In-Service Wear Limits</th>
                    </tr>
                    <tr>
                        <th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">mm</span></th>
                        <th colspan="2" class="text-center">Assembly<br>Clearance<br><span class="fw-normal text-secondary">mm</span></th>
                        <th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">mm</span></th>
                        <th class="text-center">Permitted<br>Clearance<br><span class="fw-normal text-secondary">mm</span></th>
                    </tr>
                    <tr>
                        <th class="text-center">Min.</th>
                        <th class="text-center">Max.</th>
                        <th class="text-center">Min.</th>
                        <th class="text-center">Max.</th>
                        <th class="text-center">Min.</th>
                        <th class="text-center">Max.</th>
                        <th class="text-center">Max.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fcRows as $row)
                        @php
                            $compA = $row['specA']->component;
                            $compB = $row['specB']->component;
                            $iplA  = $compA ? $compA->ipl_num : '—';
                            $iplB  = $compB ? $compB->ipl_num : '—';
                            $descA = $row['specA']->description;
                            $descB = $row['specB']->description;
                        @endphp
                        {{-- Row 1: Part A (ID / inner) --}}
                        <tr>
                            <td rowspan="2" class="text-center align-middle text-secondary" style="font-size:11px">
                                {{ $row['figure']->title }}
                            </td>
                            <td rowspan="2" class="text-center align-middle fw-semibold">
                                {{ $row['point']->code }}
                            </td>
                            <td>
                                {{ $descA }}
                                @if($compA)
                                    <span class="text-secondary">({{ $iplA }})</span>
                                @endif
                            </td>
                            <td class="text-end">{{ fcFmt($row['specA']->orig_dim_min) }}</td>
                            <td class="text-end">{{ fcFmt($row['specA']->orig_dim_max) }}</td>
                            <td rowspan="2" class="text-end align-middle{{ $row['clearOrigMin'] !== null && $row['clearOrigMin'] < 0 ? ' text-danger' : '' }}">
                                {{ fcFmt($row['clearOrigMin']) }}
                            </td>
                            <td rowspan="2" class="text-end align-middle{{ $row['clearOrigMax'] !== null && $row['clearOrigMax'] < 0 ? ' text-danger' : '' }}">
                                {{ fcFmt($row['clearOrigMax']) }}
                            </td>
                            <td class="text-end">{{ fcFmt($row['aWearMin']) }}</td>
                            <td class="text-end">{{ fcFmt($row['aWearMax']) }}</td>
                            <td rowspan="2" class="text-end align-middle{{ $row['permClearMax'] !== null && $row['permClearMax'] < 0 ? ' text-danger' : '' }}">
                                {{ fcFmt($row['permClearMax']) }}
                            </td>
                        </tr>
                        {{-- Row 2: Part B (OD / outer) --}}
                        <tr>
                            <td>
                                {{ $descB }}
                                @if($compB)
                                    <span class="text-secondary">({{ $iplB }})</span>
                                @endif
                            </td>
                            <td class="text-end">{{ fcFmt($row['specB']->orig_dim_min) }}</td>
                            <td class="text-end">{{ fcFmt($row['specB']->orig_dim_max) }}</td>
                            <td class="text-end">{{ fcFmt($row['bWearMin']) }}</td>
                            <td class="text-end">{{ fcFmt($row['bWearMax']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
