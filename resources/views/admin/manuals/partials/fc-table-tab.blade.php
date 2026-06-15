@php
/**
 * Fits & Clearances table — built from explicit manual_fits (ManualFit), NOT
 * the legacy shared-point specs. One fit → two member rows (ID then OD) plus a
 * shared clearance bracket. Members carry their own limits; clearances come from
 * the fit (stored manual value, else derived). A derived value is shown muted;
 * a stored value that disagrees with the derived one is flagged.
 */
if (! function_exists('fcFmt')) {
    function fcFmt($v, $d = 4) {
        if ($v === null || $v === '') return '—';
        return number_format(round((float)$v, $d), $d);
    }
}
if (! function_exists('fcMemberIpl')) {
    function fcMemberIpl($param) {
        return optional($param?->inspectionComponent?->variants?->first()?->component)->ipl_num;
    }
}

$fits = $manualFits ?? collect();
@endphp

<div class="p-3">
    <h5 class="mb-3">Fits and Clearances</h5>

    @if($fits->isEmpty())
        <div class="text-secondary">
            No Fits &amp; Clearances pairs yet. Add them in the Fits &amp; Clearances panel
            of the Dimensions tab (or run <code>php artisan fits:backfill --write</code> to
            import legacy shared-point pairs).
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle" style="font-size:12px;white-space:nowrap">
                <thead class="table-light">
                    <tr>
                        <th rowspan="3" class="text-center align-middle">Ref.<br>No.</th>
                        <th rowspan="3" class="text-center align-middle">Mating IPL<br>Item / Member</th>
                        <th colspan="4" class="text-center">Original Manufacturer Limits</th>
                        <th colspan="3" class="text-center">In-Service Wear Limits</th>
                    </tr>
                    <tr>
                        <th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">in</span></th>
                        <th colspan="2" class="text-center">Assembly<br>Clearance<br><span class="fw-normal text-secondary">in</span></th>
                        <th colspan="2" class="text-center">Dimension<br><span class="fw-normal text-secondary">in</span></th>
                        <th class="text-center">Permitted<br>Clearance<br><span class="fw-normal text-secondary">in</span></th>
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
                    @foreach($fits as $fit)
                        @php
                            $idP = $fit->idParam;
                            $odP = $fit->odParam;

                            $idIpl = fcMemberIpl($idP);
                            $odIpl = fcMemberIpl($odP);

                            // ID wear falls back to orig when wear not set; same for OD.
                            $idWearMin = $idP?->wear_dim_min ?? $idP?->orig_dim_min;
                            $idWearMax = $idP?->wear_dim_max ?? $idP?->orig_dim_max;
                            $odWearMin = $odP?->wear_dim_min ?? $odP?->orig_dim_min;
                            $odWearMax = $odP?->wear_dim_max ?? $odP?->orig_dim_max;

                            $asmMin = $fit->effectiveAssemblyClearanceMin();
                            $asmMax = $fit->effectiveAssemblyClearanceMax();
                            $perm   = $fit->effectivePermittedClearance();

                            $asmMinDerived = $fit->assembly_clearance_min === null;
                            $asmMaxDerived = $fit->assembly_clearance_max === null;
                            $permDerived   = $fit->permitted_clearance === null;

                            $mismatch = $fit->hasClearanceMismatch();
                        @endphp

                        {{-- Row 1: ID member (inner) --}}
                        <tr @if($mismatch) class="table-warning" @endif>
                            <td rowspan="2" class="text-center align-middle fw-semibold">
                                {{ $fit->ref_no ?: '—' }}
                                @if($mismatch)
                                    <div class="text-danger" style="font-size:10px" title="Stored clearance differs from derived">⚠ check</div>
                                @endif
                            </td>
                            <td>
                                {{ $idP?->description ?? '—' }}
                                @if($idIpl)<span class="text-secondary">({{ $idIpl }})</span>@endif
                            </td>
                            <td class="text-end">{{ fcFmt($idP?->orig_dim_min) }}</td>
                            <td class="text-end">{{ fcFmt($idP?->orig_dim_max) }}</td>
                            <td rowspan="2" class="text-end align-middle {{ $asmMinDerived ? 'text-secondary' : '' }}"
                                @if($asmMinDerived) title="derived" @endif>
                                {{ fcFmt($asmMin) }}
                            </td>
                            <td rowspan="2" class="text-end align-middle {{ $asmMaxDerived ? 'text-secondary' : '' }}"
                                @if($asmMaxDerived) title="derived" @endif>
                                {{ fcFmt($asmMax) }}
                            </td>
                            <td class="text-end">{{ fcFmt($idWearMin) }}</td>
                            <td class="text-end">{{ fcFmt($idWearMax) }}</td>
                            <td rowspan="2" class="text-end align-middle {{ $permDerived ? 'text-secondary' : '' }}"
                                @if($permDerived) title="derived" @endif>
                                {{ fcFmt($perm) }}
                            </td>
                        </tr>
                        {{-- Row 2: OD member (outer) --}}
                        <tr @if($mismatch) class="table-warning" @endif>
                            <td>
                                {{ $odP?->description ?? '—' }}
                                @if($odIpl)<span class="text-secondary">({{ $odIpl }})</span>@endif
                            </td>
                            <td class="text-end">{{ fcFmt($odP?->orig_dim_min) }}</td>
                            <td class="text-end">{{ fcFmt($odP?->orig_dim_max) }}</td>
                            <td class="text-end">{{ fcFmt($odWearMin) }}</td>
                            <td class="text-end">{{ fcFmt($odWearMax) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-secondary mt-2" style="font-size:11px">
            Muted clearance values are derived from the member limits; fill the manual
            values in the Fits &amp; Clearances panel to override. Rows flagged
            <span class="text-danger">⚠ check</span> have a stored clearance that disagrees with the derived one.
        </p>
    @endif
</div>
