<div class="subnav">
    <ul>
        @if ( Request::route()->getName() !== 'store.dashboard' )
          <li class="left"><a href="{{ URL::route('store.dashboard') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i>Return to main menu</a></li>
        @else
          <div class="left"></div>
        @endif
        <h1 @if ( Request::route()->getName() !== 'store.dashboard' ) class="small-title" @endif>{{ $headerTitle }}</h1>
        @if ( Request::route()->getName() == 'store.registration.edit' )
            <li class="right"><a href="{{ URL::route('store.registration.index') }}">
            @if ($programme === 0)
                <i class="fa fa-search" aria-hidden="true"></i> Find another family</a></li>
            @else
                <i class="fa fa-search" aria-hidden="true"></i> Find another household</a></li>
            @endif
        @else
        	<div class="right"></div>
        @endif
    </ul>
</div>
