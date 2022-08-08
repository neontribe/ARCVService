<div class="horizontal-container">
    <div>
        <label for="new-user-name" class="required">
            User Name
        </label>
        <input type="text" id="new-user-name" maxlength="160">
    </div>
    <div>
        <label for="new-user-email" class="required">
            User Email
        </label>
        <input type="email" id="new-user-email">
    </div>
    <button type="button" id="add-user">Add User</button>
</div>
<div id="trader-users-list" class="horizontal-container callout">
    @if(!empty($users))
    @foreach ($users as $user)
        <span class="user-array">
            <input name="users[{{ $user->id }}][id]" type="hidden" value="{{ $user->id }}" >
            <input required
                   name="users[{{ $user->id }}][name]"
                   type="text"
                   value="{{ $user->name }}"
                   maxlength="160"
            >
            <input required
                   name="users[{{ $user->id }}][email]"
                   type="email"
                   value="{{ $user->email }}"
            >
            <div>
                <button type="button" class="remove-row">
                    <i class="glyphicon glyphicon-minus" aria-hidden="true"></i>
                </button>
            </div>
        </span>
    @endforeach
    @endif
</div>


<script>
    $(document).ready(
        function () {
            // bind some buttons
            var el = $("#user_wrapper");
            var addUserButton = $("#add-user");

            // add user wrapper
            $(addUserButton).click(function (e) {
                e.preventDefault();

                // grab the elements
                var newUserNameEl = $('#new-user-name');
                var newUserEmailEl = $('#new-user-email');

                // get the values;
                var newUserName = newUserNameEl.val();
                var newUserEmail = newUserEmailEl.val();

                // key, made of microseconds; very unlikely to be duped.
                var key = Date.now();

                // make some fields
                var userIdInput = '<input name="users[' + key + '][id]" type="hidden" value="" >';
                var nameInput = '<input required name="users[' + key + '][name]" type="text" dusk="trader_name" value="' + newUserName + '" >';
                var emailInput = '<input required name="users[' + key + '][email]" type="email" dusk="trader_email" value="' + newUserEmail + '" >';
                var userDelControl = '<button type="button" class="remove-row"><i class="glyphicon glyphicon-minus" aria-hidden="true"></i></button>';

                // add the new field and controls
                var el = $('#trader-users-list');
                $(el).append(
                    '<span class="user-array">' +
                    userIdInput +
                    nameInput +
                    emailInput +
                    '<div style="display: inline-block; width: 7em; justify-content: center">' + userDelControl + '</div>' +
                    '</span>'
                );

                // reset form
                newUserEmailEl.val('');
                newUserNameEl.val('');
                newUserNameEl.focus();
            });

            // bind removal function
            $(document).on("click", "button.remove-row", function (e) {
                e.preventDefault();
                $(this).closest('span.user-array').remove();
                return false;
            });
        }
    );
</script>
