<div class="subnav">
    <ul>
        @if ( Request::route()->getName() != 'service.dashboard' )
          <li class="left"><a href="{{ URL::route("service.base") }}"><i class="fa fa-arrow-left" aria-hidden="true"></i>Return to main menu</a></li>
        @else
          <div class="left"></div>
        @endif
        <h1>{{ $headerTitle }}</h1>
        @if ( Request::route()->getName() == 'service.registration.edit' )
            <li class="right"><a href="{{ URL::route("service.registration.index") }}"><i class="fa fa-search" aria-hidden="true"></i> Find another family</a></li>
        @else
        	<div class="right"></div>
        @endif
    </ul>
</div>
