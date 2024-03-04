var type = '';
var formArray = ['help_subject', 'help_text', 'help_link'];
var tasksFiles = [];
var imgList = [];
var messageInterval = null;
var donePage = 1;
var progPage = 1;

$(function()
{
  $('.request-help-link').on('click', function(event)
  {
    event.preventDefault();

    var url = $(this).data('target');
    $.popupWindow(url, {
      width: 640,
      height: 800,
      center: 'screen'
    });
  });

  //Init date inputs
  var body = $(document.body);
  var testVal = 'not-a-date';
  var input = $('<input />')
  .attr('type','date')
  .val(testVal);

  var useDatepicker = !(body.hasClass('mobile') || body.hasClass('tablet')) || input.val() == testVal;

  if (useDatepicker)
  {
    $('input[type="date"], input[type="datetime-local"]')
    .each(function()
    {
      var input = $(this);
      var type = input.attr('type');
      var value = input.attr('data-value');
      var isWithTime = (type == 'datetime-local');
      var options = {};

      if (input.attr('data-min'))
      {
        options['minDate'] = new Date();
        options['minDate'].setTime(input.attr('data-min') * 1000);
      }

      input
      .attr('type', 'text')
      .attr('data-type', type)
      .attr('data-format', 'DD.MM.YYYY' + (isWithTime ? ' HH:mm:ss' : ''))
      .attr('data-toggle', 'datetimepicker')
      .attr('data-target', '#' + input.attr('id'))
      .addClass('datetimepicker-input')
      .on('change.datetimepicker', function(event)
      {
        input.attr('data-value', event.date ? event.date.format('YYYY-MM-DD' + (isWithTime ? 'THH:mm:ss' : '')) : null);
      });

      if (value)
      {
        input
        .val(
          new Date(value).toLocaleString(
            navigator.languages[0],
            {
              year: 'numeric',
              month: 'numeric',
              day: 'numeric',
              hour: isWithTime ? 'numeric' : undefined,
              minute: isWithTime ? 'numeric' : undefined
            }
          )
        );
      }
    })
    .initDatetimepicker('cs')
    .on('change.datetimepicker', function(event)
    {
      if (!event.date || event.oldDate === undefined || event.oldDate)
      {
        return;
      }

      var input = $(this);
      var name = input.attr('name');
      var picker = input.data('datetimepicker');

      if (name.match(/(\[|_)to\]$/))
      {
        event.date
        .hours(23)
        .minutes(59)
        .seconds(59)
      }
      else
      {
        event.date
        .hours(0)
        .minutes(0)
        .seconds(0)
      }
      
      picker.date(event.date);
    });
  }
});
