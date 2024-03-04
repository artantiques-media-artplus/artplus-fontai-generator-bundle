$(function()
{
  $('form.edit')
  .each(function()
  {
    var form = $(this);
    
    form
    .data('initial-values', form.serialize())
    .attr('data-modification-check', 1);
  });

  $(window)
  .on('beforeunload', function(event)
  {
    var isModified = false;

    $('form[data-modification-check]:not([data-modification-check-exclude])')
    .each(function()
    {
      if (isModified)
      {
        return;
      }

      var form = $(this);

      form
      .find('textarea:hidden')
      .each(function()
      {
        var textarea = $(this);
        var id = textarea.attr('id');

        if (typeof(CKEDITOR) != 'undefined' && id in CKEDITOR.instances)
        {
          CKEDITOR.instances[id].updateElement();
          textarea.val(textarea.val().trim());
        }
      });

      if (form.data('initial-values') != form.serialize())
      {
        isModified = true;
      }
    });

    if (isModified)
    {
      return false;
    }
  });
});