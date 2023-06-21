<div>
    <span>Add children or a pregnancy:</span>
</div>

<div id="addChildDobInput" class="dob-input-container">
    @include('store.partials.dobInput')
    <br /><br /><br />
    <button id="add-dob" class="link-button link-button-large">
        <i class="fa fa-plus button-icon" aria-hidden="true"></i>
        Add Child or Pregnancy
    </button>
</div>

<div>
    <p><span id="dob-error" class="invalid-error"></span></p>
</div>

<script>
    // setup the dobInput
    $("#addChildDobInput").dobInput();

    $("#add-dob").click(function (e) {
        e.preventDefault();
        // broadcast that we've validated and made the date object
        $("#addChildDobInput").trigger('childInput:submitted');
    });

    // Error message
    $(document).on('childInput:error', function(e, errorMsg) {
        console.log(errorMsg);
        $('#dob-error').text(errorMsg);
    });

    // Clear error message
    $('document').on('childInput:validated', function(e) {
        $('#dob-error').text('');
    })
</script>
