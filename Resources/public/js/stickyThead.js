$.fn.stickyThead = function()
{
  return this.each(function()
  {
    var thead = this;
    var table = thead.closest('table');
    var stickyThead = thead.cloneNode(true);
    var scrollableWrapper = table.closest('.table-responsive');

    var stickyTable = document.createElement('table');
    stickyTable.setAttribute('class', table.getAttribute('class'));
    stickyTable.append(stickyThead);

    var stickyWrapper = document.createElement('div');
    stickyWrapper.classList.add('sticky-thead');
    stickyWrapper.append(stickyTable);

    function resize()
    {
      stickyWrapper.style.width = table.parentNode.offsetWidth + 'px';
      stickyTable.style.width = table.offsetWidth + 'px';

      stickyThead
      .querySelectorAll('th')
      .forEach(function(element, index)
      {
        element.style.width = thead.querySelector('th:nth-child(' + (index + 1) + ')').offsetWidth + 'px';
      });
    }

    function scrollHorizontal()
    {
      stickyWrapper.scrollLeft = scrollableWrapper.scrollLeft;
    }

    function scrollVertical()
    {
      var top = thead.getBoundingClientRect().top;
      var isVisible = top <= 0 && top > -table.offsetHeight;

      if (isVisible && !stickyWrapper.classList.contains('visible'))
      {
        resize();
        scrollHorizontal();
      }

      stickyWrapper.classList[isVisible ? 'add' : 'remove']('visible');
    }

    table.before(stickyWrapper);
    scrollVertical();

    window.addEventListener('resize', function()
    {
      resize();
      scrollVertical();
      scrollHorizontal();
    });

    window.addEventListener('scroll', scrollVertical);
    scrollableWrapper.addEventListener('scroll', scrollHorizontal);
    
  });
}

$(function()
{
  $('.dataTable thead').stickyThead();
});