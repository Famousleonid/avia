<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Page not found</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f2f2f2;
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
            font-size: 80px;
            font-weight: bold;
            color: #0d6efd;
        }

        .error-message {
            font-size: 24px;
            margin-bottom: 20px;
        }

        img {
            width: 80%;
            max-width: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="error-container">
    <div class="error-code">404</div>
    <div class="error-message">Oops! There is no such page. 🙈</div>

    <img src="https://http.cat/404" alt="Кот 404">

    <p class="mb-4">You might be lost. Let's get back to the main page.</p>
    <a href="{{ url('/') }}" class="btn btn-primary">Home</a>
</div>
</body>
</html>
