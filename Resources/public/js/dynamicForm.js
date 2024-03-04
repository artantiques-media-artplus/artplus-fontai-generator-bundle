$(function()
{
  $('form')
  .on('change', '[data-dynamic]', function(event)
  {
    var element = $(event.target);
    var form = element.closest('form');
    var targetSelectors = element.attr('data-dynamic').split(',');

    form
    .ajaxSubmit({
      headers: {'X-No-Save': true},
      success: function(response)
      {
        for (i in targetSelectors)
        {
          var elementOld = $(targetSelectors[i]);
          var responseObject = $(response)
          var elementNew = responseObject.find(targetSelectors[i]);

          if (!elementNew.length)
          {
            elementNew = responseObject.filter(targetSelectors[i]);
          }

          elementOld
          .trigger('before-dynamic-redraw')
          .replaceWith(elementNew);

          elementNew.trigger('dynamic-redraw');

          if (elementNew.is(':input') && elementOld.val() != elementNew.val())
          {
            elementNew.trigger('dynamic-reload');
          }
        }
      }
    });
  })
  .on('before-dynamic-redraw', '.chosen', function(event)
  {
    $(event.currentTarget).chosen('destroy');
  })
  .on('dynamic-redraw', '.chosen', function(event)
  {
    $(event.currentTarget).initChosen();
  });
});