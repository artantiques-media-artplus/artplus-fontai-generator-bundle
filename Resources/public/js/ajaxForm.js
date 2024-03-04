$(function ()
{
    bindAjaxForms();
});

function bindAjaxForms(parent)
{
    if (!parent)
    {
        parent = $(document.body);
    }

    parent.find('form.ajaxForm').each(function (i, form)
    {
        form = $(form);
        var action = form.attr('action');

        if (!action)
        {
            action = window.location.href;
        }

        var anchorPos = action.lastIndexOf('#');
        var anchor = '';
        
        if (anchorPos != -1)
        {
            anchor = action.substring(anchorPos, action.length);
            action = action.substring(0, anchorPos);
        }

        form.ajaxForm({
            url: action + (form.parents('#cboxLoadedContent').length ? (action.indexOf('?') == -1 ? '?' : '&') + '_noFlash=1' : '') + anchor,
            dataType: 'json',
            beforeSubmit: function(arr, form, options)
            {
                for (i in arr)
                {
                    var el = form.find('[name="' + arr[i].name + '"]');
                    var dataValue = el.attr('data-value');
                    if (dataValue)
                    {
                        arr[i].value = dataValue;
                    }
                }

                var callback = form.data('beforeSubmit');
                
                if (callback && !callback(arr, form, options))
                {
                    return false;
                }
            },
            beforeSerialize: function (form, options)
            {
                if (typeof(CKEDITOR) != 'undefined')
                {
                    for (i in CKEDITOR.instances)
                    {
                        CKEDITOR.instances[i].updateElement();
                    }
                }
            },
            success: ajaxForm_success,
            error: ajaxForm_error
        });
    });
}

function ajaxForm_success(response, statusText, xhr, $form)
{
    $form.find('.invalid-feedback').remove();
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.has-error').removeClass('has-error');

    if (response.success)
    {
        $(document.activeElement).blur();

        if ($form.parents('#cboxLoadedContent').length)
        {
            $('<option value="' + response.id + '">' + response.value + '</option>').insertAfter(quickCreate_field.find('option:first')).attr('selected', 'selected');
            $.colorbox.close();
        }
        else
        {
            $form.attr('data-modification-check-exclude', 1);
            
            var url = response.redirect ? decodeURIComponent((response.redirect + '').replace(/\+/g, '%20')) : '';
      
            if (url[0] == '/')
            {
                url = window.location.origin + url;
            }

            fontaiAlert('Uloženo', '', 'success', function (isConfirm)
            {
                var urlBaseOld = window.location.href.split('#', 2);
                var urlBaseNew = url.split('#', 2);
                var isReloadRequired = (urlBaseOld[0] == urlBaseNew[0]);

                window.location.href = url;

                if (isReloadRequired)
                {
                    window.location.reload(true);
                }
            });
        }
    }
    else
    {
        var displayAlertOverride = false;
        var message = '<table style="width: 100%;">';

        for (var key in response.errors)
        {
            if (!key)
            {
                displayAlertOverride = true;
                message += '<tr><td class="text-left" colspan="2">' + response.errors[key][1] + '</td></tr>';
            }
            else
            {
                message += '<tr><th class="text-left">' + response.errors[key][0] + '</th><td class="text-left">' + response.errors[key][1] + '</td></tr>';
            }
            
            var inputName = key.replace('{', '[').replace('}', ']');
            var input = $('input[name="' + inputName + '"], select[name="' + inputName + '"], textarea[name="' + inputName + '"]');
            var wrapper = input.parent();
            var messageInput = '<div class="invalid-feedback">' + response.errors[key][1] + '</div>';

            if (wrapper.find('.cke').length > 0)
            {
                wrapper.find('.cke').after(messageInput);
                input.addClass('is-invalid');
            }
            else
            {
                input.parent().append(messageInput).addClass('is-invalid');
            }

            wrapper.addClass('has-error');
        }
        message += '</table>';

        if (displayAlertOverride || !$form.hasClass('no-error-alert'))
        {
            fontaiAlert(response.title, message, 'error', false);
        }
    }
}

function ajaxForm_error(req, error)
{
    if (error === 'error')
    {
        error = req.responseText;
    }

    var errorMsg = error;
    var errorTitle = 'There was a communication error';
    
    fontaiAlert(errorTitle, errorMsg, 'error');
}

