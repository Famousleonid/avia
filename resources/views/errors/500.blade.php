<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ошибка сервера</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }

        .error-box {
            text-align: center;
        }

        .error-code {
            font-size: 96px;
            font-weight: 800;
            color: #dc3545;
        }

        .error-message {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .btn-home {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="error-box">
    <div class="error-code">500</div>
    <div class="error-message">Упс! Что-то пошло не так...</div>
    <img src="{{asset('/img/500.png')}}" alt="404" class="img-fluid" width="850">
    <p>Попробуйте обновить страницу или вернуться позже.</p>
    <a href="{{ url('/') }}" class="btn btn-danger btn-home">На главную</a>
</div>
</body>
</html>
