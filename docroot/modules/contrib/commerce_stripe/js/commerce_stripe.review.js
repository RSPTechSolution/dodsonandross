(function ($, Drupal, drupalSettings, Stripe) {
  Drupal.behaviors.commerceStripeReview = {
    attach: function attach(context) {
      if (!drupalSettings.commerceStripe || !drupalSettings.commerceStripe.publishableKey) {
        return;
      }
      $("[id^=" + drupalSettings.commerceStripe.buttonId + "]", context).once("stripe-processed").each(function (k, el) {
        var $form = $(el).closest("form");
        var stripe = Stripe(drupalSettings.commerceStripe.publishableKey, {
          betas: ["payment_intent_beta_3"]
        });

        var allowSubmit = false;
        $form.on("submit.stripe_3ds", function (e) {
          $form.find(":input.button--primary").prop("disabled", true);
          if (!allowSubmit) {
            $form.find(":input.button--primary").prop("disabled", true);
            var data = {
              payment_method: drupalSettings.commerceStripe.paymentMethod
            };
            if (drupalSettings.commerceStripe.shipping) {
              var address = drupalSettings.commerceStripe.shipping;
              data.shipping = {
                name: address.given_name + " " + address.family_name,
                address: {
                  city: address.locality,
                  country: address.country_code,
                  line1: address.address_line1,
                  line2: address.address_line2,
                  postal_code: address.postal_code,
                  state: address.administrative_area
                }
              };
            }
            stripe.handleCardPayment(drupalSettings.commerceStripe.clientSecret, data).then(function (result) {
              allowSubmit = true;
              if (result.error) {
                Drupal.commerceStripe.displayError(result.error.message)
              }
              $form.submit();

            });
            return false;
          }
          return true;
        });
      });
    },
    detach: function detach(context, settings, trigger) {
      if (trigger !== "unload") {
        return;
      }
      var $form = $("[id^=" + drupalSettings.commerceStripe.buttonId + "]", context).closest("form");
      if ($form.length === 0) {
        return;
      }
      $form.off("submit.stripe_3ds");
    }
  };
})(jQuery, Drupal, drupalSettings, window.Stripe);
