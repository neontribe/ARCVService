<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/pregnancy-light.svg') }}" alt="logo">
        <h2>Household</h2>
    </div>
    <div>
        <p>To add a household member, complete the box below with their age.</p>
    </div>
    @include('store.partials.add_household_member_form', ['verifying' => $verifying])
    <div class="added">
        <label for="child_wrapper">You have added:</label>
        <table>
            <thead>
                <tr>
                    <td class="age-col">Age</td>
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

