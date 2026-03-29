{{--
    components/partials/input-filter.blade.php
    ════════════════════════════════════════════════════════════════════════
    Shared once-per-page listener for the data-input-filter attribute.

    @include this partial from any component that uses data-input-filter.
    The @once guard ensures the listener is registered exactly once no matter
    how many components on the page include this file.

    Supported filter names → regex applied on every 'input' event:
        alpha-space   strips anything that is not a letter or space
                      replicates: value.replace(/[^a-zA-Z ]/, '')

    To add a new filter, add an entry to the FILTERS map below.
--}}
@once('input-filter-js')
    <script>
        (function () {
            const FILTERS = {
                'alpha-space': /[^a-zA-Z ]/g,
            };

            document.addEventListener('input', function (e) {
                const input      = e.target;
                const filterName = input.dataset.inputFilter;

                if (!filterName) return;

                const pattern = FILTERS[filterName];
                if (pattern) {
                    input.value = input.value.replace(pattern, '');
                }
            });
        }());
    </script>
@endonce
