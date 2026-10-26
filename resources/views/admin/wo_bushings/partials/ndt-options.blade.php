@php
    $normalizeNdt = static fn ($text) => mb_strtolower(rtrim(preg_replace('/\s+/u', ' ', trim((string) $text)), '. '));
    $ndtOptionGroups = $ndtProcesses->groupBy(fn ($process) => $process->process_names_id.'|'.$normalizeNdt($process->process).'|'.$normalizeNdt($bushingProcessComments[$process->id] ?? ''));
    $ndtNameCounts = $ndtOptionGroups->map(fn ($group) => $group->first()->process_names_id)->countBy();
@endphp
@foreach($ndtOptionGroups as $equivalentProcesses)
    @php
        $process = $equivalentProcesses->firstWhere('id', (int) ($selectedNdt ?? 0)) ?? $equivalentProcesses->first();
        $specification = preg_replace('/\s+/u', ' ', trim($process->process));
        $label = $process->process_name->name;
        if (($ndtNameCounts[$process->process_names_id] ?? 0) > 1) $label .= ' — '.$specification;
    @endphp
    <option value="{{ $process->id }}" data-ndt-aliases="{{ $equivalentProcesses->pluck('id')->implode(',') }}"
            data-process-comment="{{ trim((string) ($bushingProcessComments[$process->id] ?? '')) }}"
            title="{{ $specification }}" @selected((int) ($selectedNdt ?? 0) === (int) $process->id)>{{ $label }}</option>
@endforeach
