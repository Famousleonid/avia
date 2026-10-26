@if(config('mobile_camera.pilot_enabled', false))
    <button type="button" class="btn btn-sm btn-outline-info js-pilot-camera-btn"
            data-workorder-id="{{ $workorder->id }}"
            data-workorder-number="{{ $workorder->number }}"
            @isset($photoCategory) data-photo-category="{{ $photoCategory }}" @endisset>
        @if($showCameraIcon ?? false)
            <i class="bi bi-camera2 fs-4" aria-hidden="true"></i>
        @endif
        <span>Test camera</span>
    </button>
@endif
