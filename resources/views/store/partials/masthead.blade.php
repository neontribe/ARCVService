<div class="header">
    @auth
        <div class="header-section">
            <form id="logout-form" action="{{ route('store.logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-button">Log out</button>
            </form>
        </div>
    @endauth

    <div class="header-section">
        <img src="{{ asset('store/assets/logo.png') }}" alt="Rose Vouchers for Fruit & Veg" class="logo">
    </div>

    @auth
        <div class="header-section">
            <ul>
                <li>User: {{ Auth::user()->name }}</li>

                @if (app()->environment() !== 'production')
                    <li>Programme: {{ Auth::user()->centre->sponsor->programme_name }}</li>
                @endif

                <li>Centre:
                    @switch(Auth::user()->centres->count())
                        @case(0)
                            <span>Unknown</span>
                            @break
                        @case(1)
                            {{ Auth::user()->centre->name }}
                            @break
                        @default()
                            <form
                                name="centreUserForm"
                                id="centre-select"
                                method="POST"
                                action="{{ route('store.session.put') }}"
                            >
                                @csrf
                                @method('PUT')
                                <select name="centre" onchange="document.centreUserForm.submit()">
                                    @foreach (Auth::user()->centres as $centre)
                                        <option
                                            value="{{ $centre->id }}"
                                            @selected($centre->id === (int)session('CentreUserCurrentCentreId'))
                                        >{{ $centre->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                    @endswitch
                </li>
            </ul>
        </div>
    @endauth
</div>
