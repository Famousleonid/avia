<span class="process-content">{!! nl2br(e(format_process_number($text))) !!}@if(!empty($showRequirementStar))<span class="process-requirement-print-star">*</span>@endif
@if(!empty($comment))<span class="process-comment">{{ $comment }}</span>@endif
@if(!empty($description))<span class="process-description">{{ $description }}</span>@endif</span>
