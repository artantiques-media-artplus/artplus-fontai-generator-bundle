$(function () {
    $('.page-wrapper')
        .on('click', '[data-confirm]', handleAction)
        .on('click', '[data-url]', handleAction);
});

function handleAction(e) {
    var $el = $(this);
    var confirmTitle = $el.data('confirm');
    var confirmUrl = $el.data('url');
    var method = $el.data('method');
    var input = $el.data('input');
    var newWindow = $el.data('new-window');

    if (!empty(confirmTitle)) {
        fontaiAlert(confirmTitle, '', 'confirm', function (isConfirm) {
            if (isConfirm) {
                redirectWithMethod(confirmUrl, method, input);
            } else {
                swal.close();
            }
        });

        e.preventDefault();
        e.stopImmediatePropagation();
    } else {
        if (newWindow == '1') {
            window.open(confirmUrl);
        } else {
            redirectWithMethod(confirmUrl, method, input);
        }

        e.preventDefault();
        e.stopImmediatePropagation();
    }
}