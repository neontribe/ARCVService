<a
    href="{{
        route($route, [
            'orderBy' => $orderBy,
            'direction' => $direction === 'desc' ? 'asc' : 'desc'
        ])
    }}"
    class="{{ request('orderBy') === $orderBy
        ? $direction === 'desc'
            ? 'glyphicon glyphicon-chevron-up'
            : 'glyphicon glyphicon-chevron-down'
        : 'glyphicon glyphicon-chevron-down'
    }}">
</a>