$(function()
{
  $('.page-wrapper')
  .on('change', 'input[data-toggle-checked]', function()
  {
    var toggler = $(this);
    var selector = toggler.attr('data-toggle-checked');
    
    $(selector).prop('checked', toggler.is(':checked'));
  })
  .on('change', 'input[type="checkbox"]:not([data-toggle-checked])', function()
  {
    var input = $(this);
    var togglers = input.closest('.form-row').find('input[data-toggle-checked]');

    togglers.each(function()
    {
      var toggler = $(this);
      var selector = toggler.attr('data-toggle-checked');

      if (input.is($(toggler.attr('data-toggle-checked'))))
      {
        toggler.prop('checked', $(selector + ':not(:checked)').length == 0);
      }
    })
  });
});