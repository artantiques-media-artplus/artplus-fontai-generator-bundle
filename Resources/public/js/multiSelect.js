$(function()
{
  $.fn.chosenAjax = function(options)
  {
    return this.each(function()
    {
      var select = $(this).chosen(options);

      if (!('autocomplete_url' in options))
      {
        return;
      }

      select
      .data('autocomplete-loading', false)
      .on('change', function()
      {
        var select = $(this);

        select
        .find('option:not(:selected):not([value=""])')
        .remove();

        select.trigger('chosen:updated');
      });

      var chosen = select.data('chosen');
      var minSearchLength = select.attr('data-autocomplete-length');
      var tags = select.is('[data-autocomplete-tags]');

      if (!minSearchLength)
      {
        minSearchLength = 3;
      }

      chosen.search_field
      .on('keyup', function()
      {
        var timeout = select.data('autocomplete-timeout');

        if (timeout)
        {
          window.clearTimeout(timeout);
        }

        timeout = setTimeout(
          function()
          {
            var value = chosen.search_field.val();

            if (value.length < minSearchLength || value == select.data('autocomplete-value'))
            {
              return;
            }

            var xhr = select.data('autocomplete-request');

            if (xhr)
            {
              xhr.abort();
            }

            var data = select.attr('data-autocomplete-data');
            data = data ? JSON.parse(data) : {};
            data[options.autocomplete_name] = value;

            var xhr = $.ajax({
              url: options.autocomplete_url,
              data: data,
              method: 'POST',
              dataType: 'json',
              crossDomain: true,
              beforeSend: function(xhr)
              {
                chosen.search_results.empty();

                select
                .find('option:not(:selected):not([value=""])')
                .remove();
              }
            })
            .done(function(data)
            {
              select.data('autocomplete-request', false);

              if (tags && !(value in data))
              {
                if (data.length == 0)
                {
                  data = {};
                }

                data[value] = value;
              }

              var hasNewOptions = false;

              $.each(data, function(valueData, title)
              {
                if (!select.find('option[value="' + valueData +'"]').length)
                {
                  $('<option />')
                  .val(valueData)
                  .text(title)
                  .appendTo(select);

                  hasNewOptions = true;
                }
              });

              if (hasNewOptions)
              {
                select
                .data('autocomplete-value', value)
                .trigger('chosen:updated');

                chosen.search_field
                .val(value)
                .trigger('keyup');
              }
            })
            .fail(function()
            {
              select.data('autocomplete-request', false);
            });

            select
            .data('autocomplete-request', xhr)
            .data('autocomplete-timeout', null);
          },
          500
        );

        select.data('autocomplete-timeout', timeout);
      });
    });
  };

  $('select[multiple]:not(.chosen)')
  .each(function()
  {
    var select = $(this);

    select.multiSelect({
      selectableHeader: '<em>' + select.attr('data-title-unselected') + '</em>',
      selectionHeader: '<em>' + select.attr('data-title-selected') + '</em>'
    });
  });

  $.fn.initChosen = function()
  {
    return this.each(function()
    {
      var select = $(this);
      var options = {
        allow_single_deselect: true,
        width: '100%',
        search_contains: true
      };

      var autocompleteUrl = select.attr('data-autocomplete-url');

      if (autocompleteUrl)
      {
        options.autocomplete_url = autocompleteUrl;
        options.autocomplete_name = select.attr('data-autocomplete-name');
      }
      else
      {
        options.disable_search_threshold = 10;
      }

      select.chosenAjax(options);
    });
  };

  $('select.chosen').initChosen();
});