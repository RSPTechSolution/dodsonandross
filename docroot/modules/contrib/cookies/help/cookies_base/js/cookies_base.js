/**
 * @file
 * Defines Javascript behaviors for the cookies module.
 */;

(function (Drupal, $) {
  'use strict';

  /**
   * Define defaults.
   */
  Drupal.behaviors.cookies_base = {
    // id corresponding to the cookies_service.schema->id.
    id: 'base',

    activate: function (context) {
      // Do stuff here to activate the specific 3rd-party service.
    },

    fallback: function (context) {
      // Do stuff here to display that 3rd-party service is disabled.
    },

    attach: function (context) {
      var self = this;
      document.addEventListener('cookiesjsrUserConsent', function (event) {
        var service = (typeof event.detail.services === 'object') ? event.detail.services : {};
        if (typeof service[self.id] !== 'undefined' && service[self.id]) {
          self.activate(context);
        } else {
          self.fallback(context);
        }
      });
    }
  }
})(Drupal, jQuery);
