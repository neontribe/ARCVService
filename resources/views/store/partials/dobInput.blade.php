<div class="dob-input">
    <label for="dob-month"
           class="block"
    >Month</label>
    <input id="dob-month"
           name="dob-month"
           type="number"
           pattern="[0-9]*"
           min="0"
           max="12"
    >
</div>
<div class="dob-input">
    <label for="dob-year"
           class="block"
    >Year</label>
    <input id="dob-year"
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
               id="dob-verified"
               name="dob-verified"
        >
        <label for="dob-verified">ID Checked</label>
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
                $(document).on('childInput:submitted', {element: this.element}, this.reset);
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