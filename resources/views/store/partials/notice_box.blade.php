<div class="alert-message warning">
    <div class="icon-container warning">
        <i class="fa fa-bell" aria-hidden="true"></i>
    </div>
    <div id="family-warning">
        @foreach( $noticeReasons as $notices )
        <p class="v-spaced">
            {{ $notices['count'] }} {{ str_plural($notices['entity'], $notices['count']) }} currently {{$notices['reason'] }}
        </p>
        @endforeach
    </div>
</div>
