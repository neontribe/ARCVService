<div>
    <span>Add Household Member:</span>
</div>
<div id="addChildAgeInput" class="age-input-container">
    @include('store.partials.ageInput')
    <button id="add-age" class="link-button link-button-large">
        <i class="fa fa-plus button-icon" aria-hidden="true"></i>
        Add Household Member
    </button>
</div>

<div>
<p><span id="age-error" class="invalid-error"></span></p>
</div>

<script>
    // setup the age input
    $("#addChildAgeInput").ageInput();

    // emit button clicked event
    $("#add-age").click(function (e) {
        e.preventDefault();
        $("#addChildAgeInput").trigger('childInput:submitted');
    });

    // Error message
    $(document).on('childInput:error', function(e, errorMsg) {
        console.log(errorMsg);
        $('#age-error').text(errorMsg);
    });

    // Clear error message
    $('document').on('childInput:validated', function(e) {
        $('#age-error').text('');
    })
</script>