<div class="header">
    @if (!Auth::guest())
        <div class="logout">
            <form>
                <button type="submit" value="logout" class="logout-button" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    Log out
                </button>
            </form>
            <form id="logout-form" action="{{ route('store.logout') }}" method="POST" style="display: none;">
                {{ csrf_field() }}
            </form>
        </div>
    @endif
    <div class="logo">
        <img src="{{ asset('store/assets/logo.png') }}" name="logo">
    </div>
    @if (!Auth::guest())
        <div>
            <ul>
                <li>User: {{ Auth::user()->name }} </li>
                <li>Centre:
                    @if( Auth::user()->centres->count() == 1 )
                        {{ Auth::user()->centre->name }}
                    @elseif (Auth::user()->centres->count() > 1)
                        <form name="centreUserForm" method="post" action="{{ route('store.session.put') }}">
                            {!! method_field('put') !!}
                            {!! csrf_field() !!}
                            <select name="centre" onchange="document.centreUserForm.submit()">
                                @foreach (Auth::user()->centres as $centre)
                                    <option
                                            value="{{ $centre->id }}"
                                            @if(session('CentreUserCurrentCentreId'))
                                                @if($centre->id == session('CentreUserCurrentCentreId'))
                                                    selected
                                                @endif
                                            @else
                                                @if($centre->id == Auth::user()->centre->id)
                                                    selected
                                                @endif
                                            @endif
                                    >{{ $centre->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <span>Unknown</span>
                    @endif
                </li>
            </ul>
        </div>
    @endif
</div>
