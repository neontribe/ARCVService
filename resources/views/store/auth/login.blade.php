@extends('store.layouts.service_master')

@section('title', 'Login')

@section('hoist-head')
    <!-- specifically to avoid login page timeout -->
    <meta http-equiv="refresh" content="{{ (config('session.lifetime') * 60) - 30 }};url={{ route('store.login') }}" />

    <div class="cookie-notice">
        <p>We use cookies to authenticate you so we can ensure that we give you the best experience on our website. For more information please read our <a href="/store/privacy_policy.html#cookie">Privacy Policy</a>.</p>
        <button class="cookie-agree">Dismiss</button>
    </div>
@endsection

@section('content')
    <div class="content login">
        <div class="login-container">
            <h2>Log In</h2>
            @if ($errors->has('error_message'))
                <div class="alert alert-danger">
                    <strong>{{ $errors->first('error_message') }}</strong>
                </div>
            @endif
            <form role="form" method="POST" action="{{ route('store.login') }}">
                {{ csrf_field() }}
                <div>
                    <label for="email">Email Address</label>
                    <input id="email" type="email" class="login-input" name="email" value="{{ old('email') }}" required
                           autofocus>
                    @if ($errors->has('email'))
                        <span class="help-block">
                    <strong>{{ $errors->first('email') }}</strong>
                </span>
                    @endif
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" class="login-input" type="password" name="password" required>
                    @if ($errors->has('password'))
                        <span class="help-block">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                    @endif
                </div>
                <button type="submit" class="submit">Log In</button>
                <div class="links">
                    <a href="{{ route('store.password.request') }}">Forgot Your Password?</a>
                    <a href="/store/privacy_policy.html">Privacy Policy</a>
                </div>
            </form>
        </div>
    </div>

     <script>
        $(document).ready(
            function () {
                $('.cookie-agree').click(function (e) {
                    $('.cookie-notice').addClass('collapsed');
                    e.preventDefault();
                });
            }
        );
    </script>
@endsection
