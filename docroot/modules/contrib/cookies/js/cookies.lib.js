/**
 * @todo Remove code if IE11 not supported anymore.
 * Polyfill for CustomEvent constructor. Required for IE9-11.
 * https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent#Polyfill
 */

(function () {
  if (typeof window.CustomEvent === "function") return false;
  function CustomEvent(event, params) {
    params = params || { bubbles: false, cancelable: false, detail: null };
    var evt = document.createEvent('CustomEvent');
    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
    return evt;
  }
  window.CustomEvent = CustomEvent;
})();

(function (Drupal, $) {
  function ucfirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
  }

  $.fn.extend({
    cookiesOverlay: function (serviceName) {
      return this.each(function () {
        // Define default classes.
        var classStr = 'cookies-fallback';
        var baseStr = classStr + '--' + serviceName;
        var classes = [classStr, baseStr];
        // Define basic elements.
        var $element = $(this);
        var $elementWrapper = $element.parent();
        if (!($element.parent().hasClass(baseStr + '--wrap'))) {
          // Create and get the wrapper element. Caution, wrap returns the original set of elements:
          $elementWrapper = $element.wrapAll("<div />").parent().addClass(baseStr + '--wrap');
        }
        if ($element.data('status') !== 'fallback') {
          $element.data('status', 'fallback').addClass(classStr + '--element');
          var $fallback = $('<div />').addClass(classes.join(' '));
          $fallback.addClass(baseStr + '--overlay')
          // Create text box.
          var $textbox = $('<div />').addClass('cookies-fallback--text');
          $textbox.text(Drupal.t('This content is blocked because @service cookies have not been accepted.',
            { '@service': ucfirst(serviceName) }));
          // Create Button.
          var $button = $('<button />').addClass(baseStr + '--btn ' + classStr + '--btn');
          $button.text(Drupal.t('Accept all cookies'));

          // Add link behaviors: Dispatch Event, Remove class disabled from wrapper, set element status active.
          $button.on('click', function (event) {
            event.preventDefault();
            document.dispatchEvent(new CustomEvent('cookiesjsrSetService', { detail: { all: true } }));
          });

          // Create link.
          var $acceptLink = $('<a href="#cookiesjsrAccept"/>').addClass(classStr + '--link');
          $acceptLink.text(Drupal.t('Only accept @service cookies.', { '@service': ucfirst(serviceName) }));
          // Add link behaviors: Dispatch Event, Remove class disabled from wrapper, set element status active.
          $acceptLink.on('click', function (event) {
            event.preventDefault();
            var services = {};
            services[serviceName] = true;
            document.dispatchEvent(new CustomEvent('cookiesjsrSetService', { detail: { services } }));
          });

          // Remove fallback overlay when service becomes enabled.
          document.addEventListener('cookiesjsrUserConsent', function (event) {
            var service = (typeof event.detail.services === 'object') ? event.detail.services : {};
            if (typeof service[serviceName] !== 'undefined' && service[serviceName]) {
              var $wrapper = $(document).find('.' + baseStr + '--wrap');
              $element.data('status', 'active');
              // remove wrapper and overlay:
              $wrapper.after($element);
              $wrapper.remove();
            }
          })

          // Build overlay.
          $fallback.append($textbox);
          $fallback.append($button);
          $fallback.append($acceptLink);
          // Put overlay to page.
          $elementWrapper.append($fallback);
        }
        if (!$elementWrapper.is('.disabled')) {
          var wrapperClasses = classes.map(function (c) { return c + '--wrap' });
          wrapperClasses.push('disabled');
          $elementWrapper.addClass(wrapperClasses.join(' '));
        }
      });
    }
  });
}

)(Drupal, jQuery);
