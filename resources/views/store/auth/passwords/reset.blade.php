@extends('store.layouts.service_master')

@section('title', 'Reset Password')

@section('content')
<div class="content login">
    <div class="login-container">
        <h2>Reset Password</h2>
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        <form role="form" method="POST" action="{{ route('store.password.request') }}">
            {{ csrf_field() }}
            <input type="hidden" name="token" value="{{ $token }}">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" class="login-input email" value="{{ $email or old('email') }}" required autofocus>
            @if ($errors->has('email'))
                <span>
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
            @endif

            <label for="password">New Password</label>
            <input id="password" type="password" name="password" class="login-input" required>
            @if ($errors->has('password'))
                <span>
                    <strong>{{ $errors->first('password') }}</strong>
                </span>
            @endif

            <label for="password-confirm">Confirm New Password</label>
            <input id="password-confirm" type="password" name="password_confirmation" class="login-input" required>
            @if ($errors->has('password_confirmation'))
                <span>
                    <strong>{{ $errors->first('password_confirmation') }}</strong>
                </span>
            @endif

            <button type="submit" class="reset-button submit">
                Reset Password
            </button>
        </form>
    </div>
</div>
@endsection
