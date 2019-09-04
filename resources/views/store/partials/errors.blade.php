<div class="alert-message error">
    <div class="icon-container error">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
    </div>
    <div>
        @foreach ($error_array as $error_text)
            @if (is_array($error_text) && array_key_exists('html', $error_text))
                {{-- Errors specified as pure-HTML will be embedded unsanitised --}}
                <p>{!! $error_text['html'] !!}</p>
            @else
                {{-- All other strings will be protected against injection --}}
                <p>{{ $error_text }}</p>
            @endif
        @endforeach
    </div>
</div>