@if($errors->has($inputName))
    @foreach ($errors->get($inputName) as $error)
    <label role="alert" for="{{ $inputName }}" class="alert-danger">
        {{ $error }}
    </label>
    @endforeach
@endif
