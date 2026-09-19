@php
    $roDeleteLockMessage = __('Deletion is unavailable because this process has an assigned RO. Only an Admin or Manager can delete it.');
@endphp
<span class="d-inline-block process-ro-delete-tooltip"
      data-process-ro-delete-tooltip
      data-bs-toggle="tooltip"
      data-bs-placement="top"
      data-bs-title="{{ $roDeleteLockMessage }}"
      data-bs-delay='{"show":500,"hide":100}'
      tabindex="0">
    <button type="button"
            class="btn btn-outline-danger btn-sm disabled"
            disabled
            aria-disabled="true"
            aria-label="{{ $roDeleteLockMessage }}"
            data-process-ro-locked="delete">
        <i class="bi bi-lock-fill"></i>
    </button>
</span>
