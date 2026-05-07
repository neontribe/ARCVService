@props([
    'href',
    'icon',
    'id' => null,
])

<a href="{{ $href }}" class="link" {{ $id ? "id={$id}" : '' }}>
    <div class="link-button link-button-large">
        <i class="fa fa-{{ $icon }} button-icon" aria-hidden="true"></i>{{ $slot }}
    </div>
</a>
