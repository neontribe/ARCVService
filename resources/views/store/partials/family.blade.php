<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/pregnancy-light.svg') }}" alt="logo">
        <h2>Children or pregnancies</h2>
    </div>
    <div>
    <p>To add a child or pregnancy, complete the boxes below with their month and year of birth (or due date) in numbers, e.g. '06 2017' for June 2017.
    </p>
    </div>
    @include('store.partials.add_child_form', ['verifying' => $verifying])
    <div class="added">
        <label for="child_wrapper">You have added:</label>
        <table>
            <thead>
                <tr>
                    <td class="age-col">Age</td>
                    <td class="dob-col">Month / Year</td>
                    @if ( $verifying )
                    <td class="verified-col">ID</td>
                    @endif
                    <td class="remove-col"></td>
                </tr>
            </thead>
            <tbody id="child_wrapper">
                @if(is_array(old('children')) || (!empty(old('children'))))
                    @foreach (old('children') as $old_child )
                    <tr class="js-old-child" data-dob={{ $old_child['dob'] }} data-verified={{ $old_child['verified'] ?? 0 }}></tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
            