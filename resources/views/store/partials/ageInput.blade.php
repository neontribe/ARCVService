<div class="age-input">
    <label for="age"
           class="block"
    >Age</label>
    <input id="age"
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
                $(document).on('childInput:submitted', {element: this.element}, this.reset);
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
                    $.data(this, "plugin_" + pluginName, new DobInput(this));
                }
            });
        };
    })(jQuery, window, document);
</script>
@endpushonce
