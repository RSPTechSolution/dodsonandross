/**
 * @file
 * Defines Javascript behaviors for the cookies module.
 */
(function (Drupal, $) {
  'use strict';

  /**
   * Define defaults.
   */
  Drupal.behaviors.cookiesVideoEmbedField = {
    consentGiven: function (context) {
      $('iframe.cookies-video-embed-field', context).each(function (i, element) {
        var $element = $(element);
        if ($element.attr('src') !== $element.data('src')) {
          $element.attr('src', $element.data('src'));
        }
      });
    },

    consentDenied: function (context) {
      $('iframe.cookies-video-embed-field, div.video-embed-field-lazy', context).cookiesOverlay('video');
    },

    attach: function (context) {
      var self = this;
      document.addEventListener('cookiesjsrUserConsent', function(event) {
        var service = (typeof event.detail.services === 'object') ? event.detail.services : {};
        if (typeof service.video !== 'undefined' && service.video) {
          self.consentGiven(context);
        } else {
          self.consentDenied(context);
        }
      });
    }
  };

})(Drupal, jQuery);
