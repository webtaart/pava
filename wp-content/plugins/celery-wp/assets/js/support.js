var WpCelery = {
  connect: function(selector, slug)
  {
    var $ = jQuery;
    WpCelery.whenAvailable('celery', function() {
      el = $(selector).get(0);
      el.setAttribute("data-celery", slug);
      el.setAttribute("data-celery-version", "v2");
      celery.addEvent(el, "click", celery.load);
    });
  },
  
  whenAvailable: function(name, callback) 
  {
    var interval = 10; // ms
    window.setTimeout(function() {
      if (window[name]) {
        callback(window[name]);
      } else {
        window.setTimeout(arguments.callee, interval);
      }
    }, interval);
  },
};