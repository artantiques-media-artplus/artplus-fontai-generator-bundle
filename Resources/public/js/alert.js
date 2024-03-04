var fontaiAlertOkButton = '';
fontaiAlertOkButton += '<button type="button" onclick="swal.close();" class="btn btn-primary btn-with-icon">';
    fontaiAlertOkButton += '<span class="btn-with-icon-icon"><svg class="icon icon-tick"><use xlink:href="#icon-tick"></use></svg></span>';
    fontaiAlertOkButton += '<strong class="btn-with-icon-text">OK</strong>';
fontaiAlertOkButton += '</button>';

var fontaiAlertErrorButton = '';
fontaiAlertErrorButton += '<button type="button" onclick="swal.close();" class="btn btn-danger btn-with-icon">';
    fontaiAlertErrorButton += '<span class="btn-with-icon-icon"><svg class="icon icon-tick"><use xlink:href="#icon-tick"></use></svg></span>';
    fontaiAlertErrorButton += '<strong class="btn-with-icon-text">Zavřít</strong>';
fontaiAlertErrorButton += '</button>';

var fontaiAlertConfirmButtons = '';
fontaiAlertConfirmButtons += '<button type="button" onclick="swal.close(\'confirm\');" class="btn btn-success btn-with-icon">';
    fontaiAlertConfirmButtons += '<span class="btn-with-icon-icon"><svg class="icon icon-tick"><use xlink:href="#icon-tick"></use></svg></span>';
    fontaiAlertConfirmButtons += '<strong class="btn-with-icon-text">OK</strong>';
fontaiAlertConfirmButtons += '</button>';
fontaiAlertConfirmButtons += '<button type="button" onclick="swal.close();" class="btn btn-danger btn-with-icon">';
    fontaiAlertConfirmButtons += '<span class="btn-with-icon-icon"><svg class="icon icon-tick"><use xlink:href="#icon-tick"></use></svg></span>';
    fontaiAlertConfirmButtons += '<strong class="btn-with-icon-text">Cancel</strong>';
fontaiAlertConfirmButtons += '</button>';

function fontaiAlert(title, message, type, returnFce) {
    if (type === undefined) {
        type = 'error';
    }

    var timer = null;
    var icon = 'alert-question';
    var buttons = fontaiAlertOkButton;
    switch (type) {
        case 'confirm': buttons = fontaiAlertConfirmButtons; break;
        case 'error': icon = 'alert-warning'; buttons = fontaiAlertErrorButton; break;
        case 'success': icon = 'tick'; timer = 2000; break;
        case 'notice': icon = 'tick'; break;
    }

    var contentNode;
    contentNode = buildContentNode(title, message, buttons, icon);
    swal({
        title: title,
        content: contentNode,
        icon: "error",
        button: false,
        timer: timer
    }).then(function(value) {
        if (returnFce !== undefined && returnFce instanceof Function) {
            var confirmed = value === true;
            returnFce(confirmed);
        }
    });
}

function buildContentNode(title, message, buttons, icon) {
    var messageHtml = '';
    messageHtml += '<div class="fontai-alert-icon"><svg class="icon icon-'+icon+'"><use xlink:href="#icon-'+icon+'"></use></svg></div>';
    messageHtml += '<div class="fontai-alert-message">';
        messageHtml += '<div class="fontai-alert-message-title"><h2>'+title+'</h2></div>';
        messageHtml += '<div class="fontai-alert-message-content">'+message+'</div>';
        messageHtml += '<div class="fontai-alert-message-buttons">'+buttons+'</div>';
    messageHtml += '</div>';

    var contentNode = document.createElement('div');
    contentNode.className = 'fontai-alert';
    contentNode.innerHTML = messageHtml;

    return contentNode;
}
