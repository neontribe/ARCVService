<div class="horizontal-container">
    <div>
        <label for="user-name"
               class="required"
        >User Name</label>
        <input type="text"
               id="user-name"
               value=""
               class=""
               maxlength="160"
               required
        >
    </div>
    <div>
        <label for="user-email"
               class="required"
        >User Email</label>
        <input type="email"
               id="user-email"
               value=""
               class=""
               required
        >
    </div>
    <div style="width: auto; justify-content: center">
        <button type="button" id="add-user">Add User</button>
    </div>
</div>
<div id="trader-users-list" class="callout" ></div>


<script>
    $(document).ready(
        function () {
            // bind some buttons
            var el = $("#user_wrapper");
            var addUserButton = $("#add-user");

            // add user wrapper
            $(addUserButton).click(function (e) {
                e.preventDefault();

                // Grab the elements
                var userNameEl = $('#user-name');
                var userEmailEl = $('#user-email');

                // get the values;
                var userName = userNameEl.val();
                var userEmail = userEmailEl.val();
                console.log([userName,userEmail]);

                var nameInput = '<input name="" type="text" value="' + userName + '" >';
                var emailInput = '<input name="" type="email" value="' + userEmail + '" >';
                /*
                var childKey = Math.random();
                var nameColumn = '<td class="name-col"><input name="children[' + childKey + '][name-col]" type="hidden" value="' + userName + '" >' + innerTextDate + '</td>';
                var idColumn = (idCheckedEl.length)
                    ? '<td class="verified-col relative"><input type="checkbox" class="styled-checkbox inline-dob" name="children[' + childKey + '][verified]" id="child' + childKey + '" ' + displayVerified + ' value="' + verifiedValue + '"><label for="child' + childKey + '"><span class="visually-hidden">Toggle ID checked</span></label>' + '</td>'
                    : '<td class="verified-col relative"></td>';
                var removeColumn = '<td class="remove-col"><button type="button" class="remove_date_field"><i class="fa fa-minus" aria-hidden="true"></i></button></td>';
                */
                var el = $('#trader-users-list');
                console.log(el);
                $(el).append('<div class="horizontal-container"><div>' + nameInput + '</div><div>' + emailInput +'</div></div>');

                // reset form
                userEmailEl.val('');
                userNameEl.val('');
                userNameEl.focus();
            });

            $(el).on("click", ".remove_date_field", function (e) {
                e.preventDefault();
                $(this).closest('tr').remove();
                return false;
            });
        }
    );
</script>