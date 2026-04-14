<a
    href="{{
        route($route, array_merge(
            request()->query(),
            [
                'orderBy'   => $orderBy,
                'direction' => $direction === 'desc' ? 'asc' : 'desc',
            ]
        ))
    }}"
    title="Sort all families alphabetically."
    class="{{ request('orderBy') === $orderBy
        ? $direction === 'desc'
            ? 'fa fa-chevron-up'
            : 'fa fa-chevron-down'
        : 'fa fa-chevron-down'
    }}">
    <span class="visually-hidden">Sort by this column.</span>
</a>
