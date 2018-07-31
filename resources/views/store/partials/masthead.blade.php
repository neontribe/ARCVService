<div class="header">
    @if (!Auth::guest())
        <div class="logout">
            <form>
                <button type="submit" value="logout" class="logout-button" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    Log out
                </button>
            </form>
            <form id="logout-form" action="{{ route('service.logout') }}" method="POST" style="display: none;">
                {{ csrf_field() }}
            </form>
        </div>
    @endif
    <div class="logo">
        <img src="{{ asset('assets/logo.png') }}" name="logo">
    </div>
    @if (!Auth::guest())
        <div>
            <ul>
                <li>User: {{ Auth::user()->name }} </li>
                <li>Centre: @isset( Auth::user()->centre ) {{ Auth::user()->centre->name }} @endisset</li>
            </ul>
        </div>
    @endif
</div>
