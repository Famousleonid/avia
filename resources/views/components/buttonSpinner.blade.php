@props(['type' => 'submit', 'class' => '', 'title' => 'press the button', 'text' => '...'])

<button type="{{$type}}" {{ $attributes->merge(['class' => 'compBtn btn ' .$class]) }} " title="{{$title}} ">
    <span id="spinner-loading" class="spinner-border spinner-border-sm d-none"></span><span id="space-loading" class="d-none">&nbsp;&nbsp;</span>
    {{$text}}
</button>

<script type="text/javascript">
    btn = document.querySelector('.compBtn');
    spinner = document.querySelector('#spinner-loading')
    space = document.querySelector('#space-loading')
    btn.onclick = function () {
        spinner.classList.remove('d-none')
        space.classList.remove('d-none')
    };
</script>
