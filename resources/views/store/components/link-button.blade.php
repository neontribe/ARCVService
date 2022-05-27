<a href="{{ route(...$route) }}" target="_blank" rel="noopener noreferrer">
    <li>
        <img src="{{ asset($img) }}"
             @isset($id) id={{ $id }} @endisset
        >
        @lang(...$text)
    </li>
</a>