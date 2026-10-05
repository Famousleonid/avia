<h6 data-print-images-count="{{ $processName->getMedia(\App\Models\ProcessName::PRINT_ATTACHMENT_COLLECTION)->count() }}">{{ $processName->name }}</h6>
<form data-process-image-form action="{{ route('process-print-attachments.store', $processName) }}" enctype="multipart/form-data" data-no-spinner>
    @csrf
    <div class="mb-2"><label class="form-label" for="processImagePart">Part number</label><input id="processImagePart" class="form-control" name="part_number" required maxlength="150"></div>
    <div class="mb-3"><label class="form-label" for="processImageFile">Image (JPG, PNG, WebP · max 10 MB)</label><input id="processImageFile" class="form-control" name="image" type="file" accept="image/jpeg,image/png,image/webp" required></div>
    <button class="btn btn-primary" type="submit">Save image</button>
    <p class="small text-muted mt-2">One image per part number. Saving an existing part number replaces its image.</p>
</form>
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Part number</th><th>Image</th><th></th></tr></thead><tbody>
@forelse($processName->getMedia(\App\Models\ProcessName::PRINT_ATTACHMENT_COLLECTION) as $image)
    <tr><td>{{ $image->getCustomProperty('part_number') }}</td><td><a href="{{ route('process-print-attachments.image', [$processName, $image]) }}" target="_blank" rel="noopener"><img src="{{ route('process-print-attachments.image', [$processName, $image]) }}" alt="{{ $image->getCustomProperty('part_number') }}" style="width:100px;height:70px;object-fit:contain"></a></td><td class="text-end text-nowrap"><button type="button" class="btn btn-outline-secondary btn-sm" data-process-image-replace="{{ $image->getCustomProperty('part_number') }}">Replace</button> <button type="button" class="btn btn-outline-danger btn-sm" data-process-image-delete="{{ route('process-print-attachments.destroy', [$processName, $image]) }}">Delete</button></td></tr>
@empty
    <tr><td colspan="3" class="text-muted">No print images.</td></tr>
@endforelse
</tbody></table></div>
