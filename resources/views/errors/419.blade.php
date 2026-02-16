{{-- resources/views/errors/419.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>419 — Session expired</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #fff8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .error-container {
            text-align: center;
            max-width: 500px;
        }

        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #fd7e14;
        }

        .error-message {
            font-size: 24px;
            margin-bottom: 20px;
        }

        img {
            max-width: 250px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="error-container">
    <div class="error-code">419</div>
    <div class="error-message">Session expired ☕</div>

    <img src="{{asset('/img/419.jpeg')}}" alt="404" class="img-fluid">

    <p>It looks like you've been inactive for too long. Please refresh the page or log in again.</p>
    <a href="{{ url()->previous() ?? url('/') }}" class="btn btn-warning mt-3">Return</a>
</div>
</body>
</html>
