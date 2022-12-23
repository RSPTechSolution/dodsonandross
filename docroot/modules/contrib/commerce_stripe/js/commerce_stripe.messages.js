(function ($, Drupal) {
  'use strict';
  Drupal.commerceStripe = {
    displayError: function (errorMessage) {
      $('#payment-errors').html(Drupal.theme('commerceStripeError', errorMessage));
    }
  }
  Drupal.theme.commerceStripeError = function (message) {
    return $('<div class="messages messages--error"></div>').html(message);
  }
})(jQuery, Drupal);
