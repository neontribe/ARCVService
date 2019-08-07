<div class="alert-message error">
    <div class="icon-container error">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
    </div>
    <div>
        @foreach ($error_array as $error_text)
            <p>{{ $error_text }}</p>
        @endforeach
    </div>
</div>