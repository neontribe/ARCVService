@php($componentID = uniqid("", true))
<div class="dob-input">
    <label for="dob-month_{{$componentID}}"
           class="block"
    >Month</label>
    <input id="dob-month_{{$componentID}}"
           class="dob-month"
           name="dob-month"
           type="number"
           pattern="[0-9]*"
           min="0"
           max="12"
    >
</div>
<div class="dob-input">
    <label for="dob-year_{{$componentID}}"
           class="block"
    >Year</label>
    <input id="dob-year_{{$componentID}}"
           class="dob-year"
           name="dob-year"
           type="number"
           pattern="[0-9]*"
           min="0"
           max="{{ Carbon\Carbon::now()->year }}"
    >
</div>
@if ( $verifying )
    <div class="dob-input relative">
        <input type="checkbox"
               class="styled-checkbox"
               id="dob-verified_{{$componentID}}"
               class="dob-verified"
               name="dob-verified"
        >
        <label for="dob-verified_{{$componentID}}">ID Checked</label>
    </div>
@endif

@pushonce('js:dobinput')
    <script>
        (function ($, window, document, undefined) {
            'use strict';
            var pluginName = 'dobInput';

            // class constructor
            function DobInput(el) {
                this.element = el;
                this.init();
            }

            // extender to add functions
            $.extend(DobInput.prototype, {
                init: function () {
                    $(document).on('childRow:updated', {element: this.element}, this.reset);
                    $(this.element).on('childInput:submitted', {element: this.element}, this.getDate);
                },
                getDate: function (e) {
                    // get the data
                    var instance = $(e.data.element);
                    var month = instance.find('input[name=dob-month]').val();
                    var year = instance.find('input[name=dob-year]').val();
                    // true false or null, if we arn't verifying.
                    var verified = (instance.find('input[name=dob-verified]').length > 0)
                        ? instance.find('input[name=dob-verified]').is(":checked")
                        : null
                    ;
                    var errorMsg = null;

                    // If input fields are too small, return
                    if (month.length < 1 || year.length <= 2) {
                        errorMsg = 'Invalid Date';
                    } else {
                        // try to make a date
                        var dateObj = moment(year + '-' + month, "YYYY-M", true).startOf('month');
                        // basic validation
                        if (!dateObj.isValid()) {
                            switch (dateObj.invalidAt()) {
                                case (1) : // month
                                    errorMsg = 'Invalid Month';
                                    break;
                                case (0) : // year
                                    errorMsg = 'Invalid Year';
                                    break;
                                default :
                                    errorMsg = 'Invalid Date';
                            }
                        }
                        // check month
                        if (dateObj.isAfter(
                            moment().startOf('month').add(9, 'month')
                        )) {
                            errorMsg = 'Invalid Date: over 9 months away.';
                        }
                    }

                    // broadcast
                    if (errorMsg) {
                        $(document).trigger('childInput:error', [ errorMsg ]);
                    } else {
                        // broadcast that we've validated and include the date
                        $(document).trigger('childInput:validated', [ dateObj, verified ]);
                    }
                },
                reset: function (e) {
                    var instance = $(e.data.element);
                    instance.find('input[type=number]').val('');
                    instance.find(":checkbox:checked").prop('checked', false);
                    instance.find('input[type=number]').filter(':first').focus();
                }
            });

            // set namespace and bind
            $.fn[pluginName] = function () {
                return this.each(function () {
                    if (!$.data(this, "plugin_" + pluginName)) {
                        $.data(this, "plugin_" + pluginName, new DobInput(this));
                    }
                });
            };
        })(jQuery, window, document);
    </script>
@endpushonce