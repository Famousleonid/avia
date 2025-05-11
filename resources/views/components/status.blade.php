<style>
    .status {
        position: fixed;
        bottom: -20px;
        right: 20px;
        width: auto;
        max-width: 350px;
        font-size: 1.0rem;
        z-index: 2050;
        transform: translateY(100%);
        transition: transform 1s ease-out;
        display: flex;
        align-items: center;
    }

    .status.show {
        transform: translateY(-60px);
    }

    .status.hide {
        transform: translateY(100%);
    }

    .countdown {
        font-size: 0.9rem;
        margin-left: 10px;
        color: #fff;
        background-color: transparent;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: 2px solid gray;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    @media (max-width: 1200px) {
        .status {
            font-size: 1rem;
            width: 100%;
        }
    }
</style>

<div class="col-12">

    @if($errors->any())
        <div class="status alert alert-danger " role="alert">
            <ul class="list-unstyled">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close"  aria-label="Close"></button>
            <span class="countdown">6</span>
        </div>
    @endif

    @if(session()->has('success'))
        <div class="status alert alert-success alert-dismissible " role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close"  aria-label="Close"></button>
            <span class="countdown">6</span>
        </div>
    @endif

    @if(session()->has('status'))
        <div class="status alert alert-info alert-dismissible " role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close"  aria-label="Close"></button>
            <span class="countdown">6</span>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="status alert alert-danger alert-dismissible " role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close"  aria-label="Close"></button>
            <span class="countdown">6</span>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statuses = document.querySelectorAll('.status');

        statuses.forEach(status => {
            const countdownElement = status.querySelector('.countdown');
            let countdownValue = parseInt(countdownElement.textContent); // Начальное значение отсчета

            // Показываем уведомление
            setTimeout(() => {
                status.classList.add('show');
            }, 100);

            // Запускаем обратный отсчет
            let countdownInterval = setInterval(() => {
                if (countdownValue > 0) {
                    countdownValue--;
                    countdownElement.textContent = countdownValue;
                }
            }, 1000); // Каждую секунду обновляется

            // Убираем класс show и добавляем hide через 6 секунд
            setTimeout(() => {
                status.classList.add('hide');
                status.classList.remove('show');
                clearInterval(countdownInterval); // Останавливаем отсчет, когда уведомление исчезает
            }, 6000);

            // Убираем элемент полностью через 7.5 секунд
            setTimeout(() => {
                status.style.display = 'none';
            }, 7500);

            // Обработчик для кнопки закрытия (крестик)
            const closeButton = status.querySelector('.btn-close');
            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    // Начинаем анимацию скрытия при клике на кнопку
                    status.classList.add('hide');
                    status.classList.remove('show');

                    // Останавливаем отсчет при закрытии
                    clearInterval(countdownInterval);

                    // Скрываем элемент полностью после анимации (1 секунда)
                    setTimeout(() => {
                        status.style.display = 'none';
                    }, 1000); // Задержка после завершения анимации
                });
            }
        });
    });
</script>
