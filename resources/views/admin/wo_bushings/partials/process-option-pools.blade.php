{{-- One option catalogue per form; hidden rows are populated only when needed. --}}
@foreach(array_merge($processColumnsBeforeNdt, $processColumnsAfterNdt) as $poolField => $poolColumn)
<template data-bushing-options="{{ $poolField }}"><option value="">...</option>@foreach($poolColumn['options'] as $process)<option value="{{ $process->id }}" data-process-comment="{{ trim((string) ($bushingProcessComments[$process->id] ?? '')) }}">{{ $process->process }}</option>@endforeach</template>
@endforeach
<template data-bushing-options="ndt"><option value="">...</option>@include('admin.wo_bushings.partials.ndt-options', ['selectedNdt' => null])</template>
