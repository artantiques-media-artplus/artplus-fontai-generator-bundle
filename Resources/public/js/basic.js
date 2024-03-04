function redirectWithMethod(url, method, input) {
    var form = $('<form />');
    form.attr('action', url);

    if (method) {
        form.attr('method', method ? 'post' : 'get')
            .append(
                $('<input />')
                    .attr('type', 'hidden')
                    .attr('name', '_method')
                    .val(method)
            )
    }
    if (input) {
        $(input).clone().appendTo(form);
    }

    form.appendTo(document.body).trigger('submit');
}

function jqueryAjaxError(jqXHR, textStatus, errorThrown) {
    var errorMsg = errorThrown;
    var errorTitle = 'JS Error - '+textStatus;
    fontaiAlert(errorTitle, errorMsg, 'error');
}

function explode(delimiter, string, limit) {
    var emptyArray = {
        0: ''
    };

    if (
        arguments.length < 2
        || typeof arguments[0] == 'undefined'
        || typeof arguments[1] == 'undefined'
    ) return null;

    if (
        delimiter === ''
        || delimiter === false
        || delimiter === null
    ) return false;

    if (
        typeof delimiter == 'function'
        || typeof delimiter == 'object'
        || typeof string == 'function'
        || typeof string == 'object'
    ) return emptyArray;

    if (delimiter === true) delimiter = '1';

    if (!limit) return string.toString().split(delimiter.toString());
    else {
        // support for limit argument
        var splitted = string.toString().split(delimiter.toString());
        var partA = splitted.splice(0, limit - 1);
        var partB = splitted.join(delimiter.toString());
        partA.push(partB);
        return partA;
    }
}

function empty(mixed_var) {
    var key;
    if (
        mixed_var === ""
        || mixed_var === 0
        || mixed_var === "0"
        || mixed_var === null
        || mixed_var === false
        || typeof mixed_var === 'undefined'
    ) return true;

    if (typeof mixed_var == 'object') {
        for (key in mixed_var) {
            return false;
        }
        return true;
    }

    return false;
}

function validateEmail(email) {
    var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
    var address = email;
    return (reg.test(address) == false) ? false : true;
}

function is_numeric(mixed_var) {
    return (typeof(mixed_var) === 'number' || typeof(mixed_var) === 'string') && mixed_var !== '' && !isNaN(mixed_var);
}


function seoUrl(url) {
    var conv_table = {
        'á': 'a',
        'č': 'c',
        'ď': 'd',
        'é': 'e',
        'ě': 'e',
        'í': 'i',
        'ľ': 'l',
        'ň': 'n',
        'ó': 'o',
        'ř': 'r',
        'š': 's',
        'ť': 't',
        'ú': 'u',
        'ů': 'u',
        'ý': 'y',
        'ž': 'z'
    };
    url = url.toLowerCase();
    var url2 = '';
    for (var i = 0; i < url.length; i++) {
        url2 += (typeof conv_table[url.charAt(i)] != 'undefined' ? conv_table[url.charAt(i)] : url.charAt(i));
    }
    return url2.replace(/[^a-z0-9_]+/g, '-').replace(/^-|-$/g, '');
}

function sortCzech(a, b) {
    a = $(a).text();
    b = $(b).text();
    lenA = a.length;
    lenB = b.length;
    var token = {
        'Á': 'A',
        'á': 'a',
        'Č': 'C',
        'č': 'c',
        'Ď': 'D',
        'ď': 'd',
        'É': 'E',
        'é': 'e',
        'Ě': 'E',
        'ě': 'e',
        'Í': 'I',
        'í': 'i',
        'Ň': 'N',
        'ň': 'n',
        'Ř': 'R',
        'ř': 'r',
        'Š': 'S',
        'š': 's',
        'Ť': 'T',
        'ť': 't',
        'Ú': 'U',
        'ú': 'u',
        'Ů': 'U',
        'ů': 'u',
        'Ý': 'Y',
        'ý': 'y',
        'Ž': 'Z',
        'ž': 'z'
    };

    i = 0;
    do {
        var codeA = token.hasOwnProperty(a[i]) ? token[a[i]].charCodeAt(0) + 0.5 : a.charCodeAt(i);
        var codeB = token.hasOwnProperty(b[i]) ? token[b[i]].charCodeAt(0) + 0.5 : b.charCodeAt(i);
        i++;
    } while (codeA == codeB && i < lenA && i < lenB);

    return codeA - codeB;
}

function formatMoney(val, round) {
    if (round !== null) {
        round = parseInt(round, 10);
        val = Math.round(val * Math.pow(10, round)) / Math.pow(10, round);
    }
    val = val.toString().split('.');
    var tmp = '';
    var len = val[0].length;
    var curLen = 0;

    for (var i = len - 1; i >= 0; i--) {
        if (curLen && !(curLen % 3)) tmp = '&nbsp;' + tmp;
        tmp = val[0][i] + tmp;
        curLen++;
    }
    val[0] = tmp;

    if (typeof(val[1]) != 'undefined') {
        tmp = '';
        len = val[1].length;
        curLen = 0;

        for (i = 0; i < len; i++) {
            if (curLen && (curLen % 3) == false) tmp += '&nbsp;';
            tmp += val[1][i];
            curLen++;
        }
        val[1] = tmp;
    }

    if (round && typeof(val[1]) == 'undefined') {
        val[1] = '';
        for (var i = 0; i < round; i++) {
            val[1] += '0';
        }
    }
    return val.join(',');
}

/*!
* Display popup window.
*
* Requires: jQuery v1.3.2
*/
(function($) {
    var defaults = {
        center:      "screen", //true, screen || parent || undefined, null, "", false
        createNew:   true,
        height:      500,
        left:        0,
        location:    false,
        menubar:     false,
        name:        null,
        onUnload:    null,
        resizable:   false,
        scrollbars:  false, // os x always adds scrollbars
        status:      false,
        toolbar:     false,
        top:         0,
        width:       500
    };

    $.popupWindow = function(url, opts) {
        var options = $.extend({}, defaults, opts);

        // center the window
        if (options.center === "parent") {
            options.top = window.screenY + Math.round(($(window).height() - options.height) / 2);
            options.left = window.screenX + Math.round(($(window).width() - options.width) / 2);
        } else if (options.center === true || options.center === "screen") {
            // 50px is a rough estimate for the height of the chrome above the
            // document area

            // take into account the current monitor the browser is on
            // this works in Firefox, Chrome, but in IE there is a bug
            // https://connect.microsoft.com/IE/feedback/details/856470/ie11-javascript-screen-height-still-gives-wrong-value-on-secondary-monitor

            // IE reports the primary monitor resolution. So, if you have multiple monitors IE will
            // ALWAYS return the resolution of the primary monitor. This is a bug, and there is an
            // open ticket with IE for it. In chrome and firefox it returns the monitor that the
            // browser is currently located on. If the browser spans multiple monitors, whichever
            // monitor the browser has the most real estate on, is the monitor it returns the size for.

            // What this means to the end users:
            // If they have multiple monitors, and their multiple monitors have different resolutions,
            // and they use internet explorer, and the browser is currently located on a secondary
            // monitor, the centering will not be perfect as it will be based on the primary monitors
            // resolution. As you can tell this is pretty edge case.
            var screenLeft = (typeof window.screenLeft !== 'undefined') ? window.screenLeft : screen.left;

            options.top = ((screen.height - options.height) / 2) - 50;
            options.left = screenLeft + (screen.width - options.width) / 2;
        }

        // params
        var params = [];
        params.push('location=' + (options.location ? 'yes' : 'no'));
        params.push('menubar=' + (options.menubar ? 'yes' : 'no'));
        params.push('toolbar=' + (options.toolbar ? 'yes' : 'no'));
        params.push('scrollbars=' + (options.scrollbars ? 'yes' : 'no'));
        params.push('status=' + (options.status ? 'yes' : 'no'));
        params.push('resizable=' + (options.resizable ? 'yes' : 'no'));
        params.push('height=' + options.height);
        params.push('width=' + options.width);
        params.push('left=' + options.left);
        params.push('top=' + options.top);

        // open window
        var random = new Date().getTime();
        var name = options.name || (options.createNew ? 'popup_window_' + random : 'popup_window');
        var win = window.open(url, name, params.join(','));

        // unload handler
        if (options.onUnload && typeof options.onUnload === 'function') {
            var unloadInterval = setInterval(function() {
                if (!win || win.closed) {
                    clearInterval(unloadInterval);
                    options.onUnload();
                }
            }, 50);
        }

        // focus window
        if (win && win.focus) {
            win.focus();
        }

        // return handle to window
        return win;
    };
})(jQuery);