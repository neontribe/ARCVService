@section('hoist-head')
    <script src="{{ asset('store/js/moment-2.20.1.min.js')}}"></script>
@endsection

<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/pregnancy-light.svg') }}" alt="logo">
        <h2>Household</h2>
    </div>
    <div>
        <p>To add a household member, complete the box below with their age.</p>
    </div>
    @include('store.partials.add_participant_form', ['verifying' => $verifying])
    <div class="added">
        <label for="child_wrapper">You have added:</label>
        <table>
            <thead>
            <tr>
                <td class="age-col">Age</td>
                <td class="dob-col"></td>
                <td class="remove-col"></td>
            </tr>
            </thead>
            @if(!empty($children))
                <tbody id="existing_wrapper">
                @foreach ( $children as $child )
                    <tr>
                        <td class="age-col">{{ $child->getAgeString() }}</td>
                        <td class="dob-col"></td>
                        <td class="remove-col">
                            <input type="hidden" name="children[{{ $child->id }}][dob]"
                                   value="{{ Carbon\Carbon::parse($child->dob)->format('Y-m') }}"
                            >
                            <button class="remove_date_field">
                                <i class="fa fa-minus" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            @endif
        </table>
    </div>
    <div>
        <table>
            <tbody id="child_wrapper">
            @if(is_array(old('children')) || (!empty(old('children'))))
                @foreach (old('children') as $old_child )
                    <tr class="js-old-child"
                        data-dob={{ $old_child['dob'] }}
                    ></tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>

