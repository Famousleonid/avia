@auth
    <link rel="stylesheet" href="{{ asset('css/screen-messages.css') }}?v={{ filemtime(public_path('css/screen-messages.css')) }}">
    <dialog id="screenMessageDialog" aria-labelledby="screenMessageTitle" aria-describedby="screenMessageText"
            data-poll-url="{{ route('messages.screen.index') }}">
        <div class="screen-message-heading">
            <span id="screenMessageIcon" aria-hidden="true">i</span>
            <div><h2 id="screenMessageTitle">Message for you</h2><div id="screenMessageSender"></div></div>
        </div>
        <div id="screenMessageText"></div>
        <div class="screen-message-footer">
            <div id="screenMessageError" role="alert" hidden></div>
            <button type="button" id="screenMessageDismiss">Close — don't show again</button>
        </div>
    </dialog>
    <script src="{{ asset('js/messages/screen.js') }}?v={{ filemtime(public_path('js/messages/screen.js')) }}" defer></script>
@endauth
