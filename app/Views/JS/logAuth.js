$(function () {
    function doLogin() {
        var identifier = $('#identifier').val().trim();
        var password = $('#password').val();
        var $err = $('#error_text');
        $err.text('');

        if (!identifier || !password) {
            $err.text('Please fill in both fields.');
            return;
        }

        $.post('app/Controllers/authController.php', {
            action: 'loginDaUser',
            identifier: identifier,
            password: password
        }, null, 'json')
        .done(function (res) {
            if (res.success) {
                window.location.href = res.redirect;
            } else {
                $err.text(res.message || 'Login failed.');
            }
        })
        .fail(function () {
            $err.text('Something went wrong. Please try again.');
        });
    }

    $('#loginBtn').on('click', doLogin);
    $('#password').on('keypress', function (e) {
        if (e.which === 13) doLogin();
    });

    $('#togglePassword').on('click', function () {
        var $btn = $(this);
        var $pw = $('#password');
        var showing = $btn.attr('aria-pressed') === 'true';
        $pw.attr('type', showing ? 'password' : 'text');
        $btn.attr('aria-pressed', String(!showing));
        $btn.attr('aria-label', showing ? 'Show password' : 'Hide password');
    });
});
