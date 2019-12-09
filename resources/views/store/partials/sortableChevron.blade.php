<a
    href="{{
        route($route, [
            'orderBy' => $orderBy,
            'direction' => $direction === 'desc' ? 'asc' : 'desc'
        ])
    }}"
    class="{{ request('orderBy') === $orderBy
        ? $direction === 'desc'
            ? 'fa fa-chevron-up'
            : 'fa fa-chevron-down'
        : 'fa fa-chevron-down'
    }}">
</a>