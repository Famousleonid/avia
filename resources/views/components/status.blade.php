<style>
    .status {
        position: fixed;
        top: 0;
        left: 50%;
        transform: translate(-50%, 0);
        width: 50%;
        font-size: 1.2rem;
        z-index: 2050;
        opacity: 1;
        transition: opacity 0.5s ease-out, transform 0.5s ease-out;
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
        <div class="status alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="list-unstyled">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session()->has('success'))
        <div class="status alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session()->has('status'))
        <div class="status alert alert-info alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="status alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statuses = document.querySelectorAll('.status');
        statuses.forEach(status => {
            setTimeout(() => {
                status.style.opacity = '0';
                status.style.transform = 'translate(-50%, -20px)';
                setTimeout(() => {
                    status.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
</script>
