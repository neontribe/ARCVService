@section('hoist-head')
    <script src="{{ asset('store/js/moment-2.20.1.min.js')}}"></script>
@endsection

<div class="col fit-height">
    <div>
        <img src="{{ asset('store/assets/pregnancy-light.svg') }}" alt="logo">
        <h2>Children or pregnancies</h2>
    </div>
    <div>
        <p>To add a child or pregnancy, complete the boxes below with their month and year of birth (or due date) in
            numbers, e.g. '06 2017' for June 2017.
        </p>
    </div>
    @include('store.partials.add_child_form', ['verifying' => $verifying])
    <div class="added">
        <table>
            <thead>
            <tr>
                <td class="age-col">Age</td>
                <td class="dob-col">Month / Year</td>
                @if ( $verifying )
                    <td class="verified-col">ID</td>
                @endif
                @if (!empty($deferrable))
                    <td class="can-defer-col">Defer</td>
                @endif
                <td class="remove-col"></td>
            </tr>
            </thead>
            @if(!empty($children))
                <tbody id="existing_wrapper">
                @foreach ( $children as $child )
                    <tr>
                        <td class="age-col">{{ $child->getAgeString() }}</td>
                        <td class="dob-col">{{ $child->getDobAsString() }}</td>
                        @if ( $verifying )
                            <td class="verified-col relative">
                                <input type="checkbox" class="styled-checkbox inline-dob"
                                       name="children[{{ $child->id }}][verified]"
                                       id="child{{ $child->id }}"
                                       {{ $child->verified ? "checked" : null }} value="1"
                                >
                                <label for="child{{ $child->id }}">
                                    <span class="visually-hidden">Toggle ID checked</span>
                                </label>
                            </td>
                        @endif
                        @if ( $child->can_defer && $can_change_defer)
                            <td class="can-defer-col relative">
                                <input type="checkbox"
                                       class="styled-checkbox inline-dob"
                                       name="children[{{ $child->id }}][deferred]"
                                       id="children[{{ $child->id }}][deferred]"
                                       {{ $child->deferred ? "checked" : null }} value="1"
                                >
                                <label for="children[{{ $child->id }}][deferred]">
                                    <span class="visually-hidden">Toggle canDefer checked</span>
                                </label>
                            </td>
                        @elseif ($child->deferred && !$can_change_defer)
                            <td>{{ $child->deferred ? 'Y' : 'N' }}</td>
                        @else
                            <td></td>
                        @endif
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
                        data-dob={{ $old_child['dob'] }} data-verified={{ $old_child['verified'] ?? 0 }}></tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>
