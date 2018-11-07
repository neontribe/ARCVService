@extends('store.layouts.service_master')

@section('title', 'Request Reset')

@section('content')
<div class="content login">
    <div class="login-container">
        <h2>Reset Password</h2>
        @if (session('status'))
            <div>
                {{ session('status') }}
            </div>
        @endif

        <form role="form" method="POST" action="{{ route('store.password.email') }}">
            {{ csrf_field() }}
            <label for="email">Email Address</label>
            <input id="email" class="login-input" type="email" name="email" value="{{ old('email') }}" required>

            @if ($errors->has('email'))
                <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif
            <button type="submit" class="reset-button submit">
                Send Password Reset Link
            </button>
        </form>
    </div>
</div>
@endsection
