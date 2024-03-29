<div class="alert-message warning">
    <div class="icon-container warning">
        <i class="fa fa-bell" aria-hidden="true"></i>
    </div>
    <div id="family-warning">
        @foreach( $noticeReasons as $notices )
        <p class="v-spaced">
            {{ $notices['count'] === 1 ? 'A' : $notices['count'] }} {{ str_plural(strtolower($notices['entity']),
            $notices['count'])
            }} 
            @if($notices['entity']!='Family')
            {{ $notices['count'] > 1 ? 'are' : 'is' }}
            @endif
            {{ $notices['reason'] }}
        </p>
        @endforeach
    </div>
</div>