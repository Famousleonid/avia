<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Form attachments</title>
    <style>body{margin:0;padding:16px;font:16px sans-serif;background:#eef1f4;color:#172b3a}h1{font-size:20px}.attachment-figure{background:white;margin:16px auto;padding:12px;max-width:1100px;overflow:auto}.attachment-figure img{max-width:100%;height:auto}</style>
</head>
<body>
<h1>Form attachments</h1>
@if($travelerImages->isEmpty() && empty($figPagesHtml))<p>No form attachments.</p>@endif
@include('admin.tdr-processes.partials.traveler-template-images')
@foreach($figPagesHtml as $figure)
    <section class="attachment-figure"><h2>{{ $figure['caption'] }}</h2>{!! $figure['html'] !!}</section>
@endforeach
</body></html>
