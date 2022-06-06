<div class="age-input-container">
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
</div>

<script>
    (function ($, window, document) {
        $.ARC = $.ARC || {};
        $.ARC.ageInput = function () {

            /**
             * Reset and maybe focus
             * @param focus
             */
            var reset = function (focus) {
                $('#age').val('');
                if (focus) {
                    $('#age').focus();
                }
            };
            $(document).on('childInput:submitted', reset(true));
            // export reset
            return {
                reset : reset
            }
        }
    }(jQuery));
    $("age").ARC.ageInput();
</script>
