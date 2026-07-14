<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Marketing WO file</title>
</head>
<body style="margin:0;padding:24px;background:#f4f6f8;color:#27313a;font-family:Arial,sans-serif;">
<div style="max-width:640px;margin:0 auto;padding:24px;background:#fff;border:1px solid #dfe3e7;border-radius:8px;">
    <h2 style="margin:0 0 18px;">W{{ $workorder->number }} — New {{ $file->categoryLabel() }} file</h2>
    <p><strong>File:</strong> {{ $file->display_name }}</p>
    <p><strong>Customer:</strong> {{ $customer?->name ?: '—' }}</p>
    <p><strong>Added by:</strong> {{ $file->uploader?->selection_name ?: 'System' }}</p>
    <p><strong>Added at:</strong> {{ format_project_date($file->created_at) }} {{ $file->created_at?->format('H:i') }}</p>
    @if($file->comment)
        <p><strong>Comment:</strong><br>{{ $file->comment }}</p>
    @endif
    <p style="margin:24px 0 0;">
        <a href="{{ $openUrl }}" style="display:inline-block;padding:10px 16px;border-radius:5px;background:#0d6efd;color:#fff;text-decoration:none;">Open Marketing WO</a>
    </p>
    <p style="margin:18px 0 0;color:#6c757d;font-size:13px;">The file is stored in Avia. It is not attached to this email.</p>
</div>
</body>
</html>
