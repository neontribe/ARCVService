@php($componentID = uniqid("", true))
<div class="age-input">
    <label for="age_{{ $componentID }}"
           class="block"
    >Age</label>
    <input id="age_{{ $componentID }}"
           name="age"
           type="number"
           pattern="[0-9]*"
           min="0" max="120"
    >
</div>

@pushonce('js:ageinput')
<script>
    (function ($, window, document, undefined) {
        'use strict';
        var pluginName = 'ageInput';

        // class constructor
        function AgeInput(el) {
            this.element = el;
            this.init();
        }

        // extender to add functions
        $.extend(AgeInput.prototype, {
            init: function () {
                $(document).on('childRow:updated', {element: this.element}, this.reset);
                $(document).on('childInput:submitted', {element: this.element}, this.getDate);
            },
            getDate: function (e) {
                var instance = $(e.data.element);
                var errorMsg = null;

                // create the date from local age
                var age = instance.find('input[name=age]').val();
                var dateObj = moment().subtract(age, 'years').startOf('month');

                // validate it
                if (!dateObj.isValid()) {
                    errorMsg = 'Invalid Age';
                }
                if (errorMsg) {
                    $(document).trigger('childInput:error', [ errorMsg ]);
                } else {
                    // broadcast that we've validated and include the date
                    $(document).trigger('childInput:validated', [ dateObj, false ]);
                }
            },
            reset: function (e) {
                var instance = $(e.data.element);
                instance.find('input[type=number]').val('');
                instance.find('input[type=number]').filter(':first').focus();
            }
        });

        // set namespace and bind
        $.fn[pluginName] = function () {
            return this.each(function () {
                if (!$.data(this, "plugin_" + pluginName)) {
                    $.data(this, "plugin_" + pluginName, new AgeInput(this));
                }
            });
        };
    })(jQuery, window, document);
</script>
@endpushonce
