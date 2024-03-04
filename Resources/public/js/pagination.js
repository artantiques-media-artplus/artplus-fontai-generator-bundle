$(function ()
{
  $('.page-wrapper')
  .on('change', 'select[name="per_page"]', function()
  {
    var element = $(this);
    var url = element.attr('data-href');

    var wrapper = element.closest('div.card');

    if (!wrapper.hasClass('listAjax'))
    {
      wrapper = wrapper.find('.listAjax');
    }

    url += (url.indexOf('?') == -1 ? '?' : '&') + 'per_page=' + element.val();

    loadList(url, wrapper);
  })
  .on('click', 'div.dataTables_paginate a', function(event)
  {
    event.preventDefault();

    var element = $(this);

    loadList(
      element.attr('href'),
      element.closest('div.listAjax'),
      element.is('[data-skip-history]')
    );
  })
  .on('click', '.action_quickedit', function(event)
  {
    event.preventDefault();

    $(this)
    .closest('tr')
    .toggleClass('quickedit-active');
  });

  window.onpopstate = function(event)
  {
    if ('state' in event && event.state && 'wrapperId' in event.state)
    {
      var wrapper = $('#' + event.state.wrapperId);
      
      if (wrapper.length)
      {
        wrapper
        .find('div.listAjaxWrap')
        .load(
          window.location.href + ' #' + event.state.wrapperId + ' div.listAjaxContent'
        );
      }
    }
  }
});

function loadList(url, wrapper, skipHistory)
{
  var wrapperId = wrapper.attr('id');

  wrapper
  .find('div.listAjaxWrap')
  .load(
    url + ' #' + wrapperId + ' div.listAjaxContent',
    function()
    {
      $(this)
      .find('thead')
      .stickyThead();
      
      if (skipHistory || typeof(window.history.pushState) == 'undefined')
      {
        return;
      }
      
      window.history.replaceState(
        {
          wrapperId: wrapperId
        },
        document.title,
        window.location.href
      );

      window.history.pushState(
        {
          wrapperId: wrapperId
        },
        document.title,
        url
      );
    }
  );
}
