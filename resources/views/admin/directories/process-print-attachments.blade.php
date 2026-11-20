<h6 data-print-images-count="{{ $images->count() }}">{{ $processName->name }}</h6>
<p class="small text-muted">Images are shared by manual + process + P/N. Images without a manual are kept but are not included in forms.</p>
<form data-process-image-form action="{{ route('process-print-attachments.store', $processName) }}" enctype="multipart/form-data" data-no-spinner>
    @csrf
    @if($tdr)
        <input type="hidden" name="tdr_id" value="{{ $tdr->id }}">
        <input type="hidden" name="manual_id" value="{{ $tdr->component->manual_id }}">
        <input type="hidden" name="part_number" value="{{ $tdr->component->part_number }}">
        <div class="alert alert-info py-2">CMM: <strong>{{ $tdr->component->manual->number }}</strong> · P/N: <strong>{{ $tdr->component->part_number }}</strong></div>
        <p class="small text-muted">Save image saves this shared attachment immediately. It does not save or close your process draft.</p>
    @else
        <div class="mb-2"><label class="form-label" for="processImageManual">Manual</label>
            <select id="processImageManual" class="form-select" name="manual_id" required>
                <option value="">Select manual</option>
                @foreach($manuals as $manual)<option value="{{ $manual->id }}">{{ $manual->number }} · Lib {{ $manual->lib }}{{ $manual->deleted_at ? ' (archived)' : '' }}</option>@endforeach
            </select>
        </div>
        <div class="mb-2"><label class="form-label" for="processImagePart">Part number</label><input id="processImagePart" class="form-control" name="part_number" required maxlength="150"></div>
    @endif
    <div class="mb-3"><label class="form-label" for="processImageFile">Image (JPG, PNG, WebP · max 10 MB)</label><input id="processImageFile" class="form-control" name="image" type="file" accept="image/jpeg,image/png,image/webp" required></div>
    <button class="btn btn-primary" type="submit">Save image</button>
    <p class="small text-muted mt-2">One image per manual + process + P/N. Saving the same combination replaces its assigned image.</p>
</form>
<div class="d-flex flex-column gap-3">
@forelse($images as $image)
    @php($imageManualId = (int) $image->getCustomProperty('manual_id'))
    <div class="border rounded p-2" data-print-image-card>
        <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
            <a href="{{ route('process-print-attachments.image', [$processName, $image]) }}" target="_blank" rel="noopener"><img src="{{ route('process-print-attachments.image', [$processName, $image]) }}" alt="{{ $image->getCustomProperty('part_number') }}" style="width:100px;height:70px;object-fit:contain"></a>
            <div style="min-width:0;overflow-wrap:anywhere"><strong>{{ $image->getCustomProperty('part_number') }}</strong>
                @unless($imageManualId)<div class="text-warning">Manual not assigned — not used in forms</div>@endunless
            </div>
        </div>
        <form data-process-image-manual-form action="{{ route('process-print-attachments.assign-manual', [$processName, $image]) }}" data-no-spinner>
            @csrf
            @if($tdr)<input type="hidden" name="tdr_id" value="{{ $tdr->id }}">@endif
            <label class="form-label" for="imageManual{{ $image->id }}">Manual for this image</label>
            <div class="d-flex gap-2 flex-wrap">
                <select id="imageManual{{ $image->id }}" class="form-select" name="manual_id" required style="flex:1 1 240px;min-width:0">
                    <option value="">Select manual</option>
                    @foreach($manuals as $manual)
                        @if(!$tdr || (int) $manual->id === (int) $tdr->component->manual_id)
                            <option value="{{ $manual->id }}" @selected($imageManualId === (int) $manual->id)>{{ $manual->number }} · Lib {{ $manual->lib }}{{ $manual->deleted_at ? ' (archived)' : '' }}</option>
                        @endif
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline-info">Save manual</button>
            </div>
        </form>
        <div class="d-flex gap-2 mt-2">
            @if($imageManualId)<button type="button" class="btn btn-outline-secondary btn-sm" data-process-image-replace="{{ $image->getCustomProperty('part_number') }}" data-manual-id="{{ $imageManualId }}">Replace image</button>@endif
            <button type="button" class="btn btn-outline-danger btn-sm" data-process-image-delete="{{ route('process-print-attachments.destroy', [$processName, $image]) }}">Delete</button>
        </div>
    </div>
@empty
    <p class="text-muted">No print images.</p>
@endforelse
</div>