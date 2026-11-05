@php
    $attachmentTag = ($embeddedAttachment ?? false) ? 'div' : 'td';
    $family = app(\App\Services\BushingDocumentFamily::class);
    $document = $family->document($component);
    $component = $family->owner($component);
@endphp
<{{ $attachmentTag }} class="bushing-documents" data-component-id="{{ $component->id }}" data-upload-url="{{ route('components.bushing-documents.store', $component) }}">
    <div class="bushing-document-row">
    <button type="button" class="btn btn-sm btn-outline-info bushing-document-drop" aria-label="Upload image or PDF" title="Drop one image or PDF, or click to choose. Replaces the current document for this part in all workorders."><i class="bi bi-upload" aria-hidden="true"></i></button>
    <input type="file" class="d-none bushing-document-input" accept="image/jpeg,image/png,image/webp,application/pdf">
    <div class="bushing-document-links">
        @if($document)
            <button type="button" class="btn btn-sm btn-outline-secondary p-1 bushing-document-preview" data-url="{{ route('components.bushing-documents.show', [$document->model_id, $document]) }}" data-mime="{{ $document->mime_type }}" data-name="{{ $document->file_name }}" title="{{ $document->file_name }}" aria-label="Preview {{ $document->file_name }}">
                @if(str_starts_with($document->mime_type, 'image/'))
                    <img src="{{ route('components.bushing-documents.show', [$document->model_id, $document]) }}" alt="" width="36" height="36" style="object-fit:contain" loading="lazy">
                @else
                    <i class="bi bi-file-earmark-pdf text-danger" style="font-size:28px" aria-hidden="true"></i>
                @endif
            </button>
        @endif
    </div>
    </div>
    <span class="bushing-drop-hint" aria-hidden="true">Drop here</span>
</{{ $attachmentTag }}>

