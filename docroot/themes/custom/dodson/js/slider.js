/**
 * Carousel component functionality
 */

(function ($, Drupal) {
  Drupal.behaviors.slider = {
    attach: function (context, settings) {

      var slider = (function () {

        // Component attributes
        var $window = $(window);
        var $sliderWrapper = $('.item-list-vertical, .slider, .image-gallery');

        /**
         * @method init
         * Add any module methods that needs to run on load here
         */
        var init = function () {
          initSlider();
        }

        /**
         * @method initSlider - Init the slick slider
         */
        var initSlider = function () {


          $($sliderWrapper, context).once('mySecondBehavior').each(function (index, slider) {

            // Find the component type
            var componentType = ($(slider).find('.view-content').length) ? 'view' : 'card';

            // Set the slider init div
            var $sliderInitDiv = (componentType === 'view') ? $(slider).find('.view-content') : $(slider).find('> .field--name-field-card-section');

            // Define the number of children
            var childrenNumber = (componentType === 'view') ? $(slider).find('.view-content').children().length : $(slider).find('> .field--name-field-card-section').children().length;

            // Desktop settings
            var desktopSettings;
            var pathname = window.location.pathname;

            if(pathname == '/videos') {
              var childNumber = 3;
            }else if(pathname == '/articles') {
              var childNumber = 3;
            }else{
              var childNumber = 4;
            }

            desktopSettings = {
              slidesToShow: (childrenNumber < childNumber || ($(slider).parents('.betty-art-preview').length || $(slider).parents('.bodysex-leaders').length)) ? childrenNumber : childNumber,
              centerMode: false,
              arrows: true
            }

            $sliderInitDiv.once('initSliders').slick({
              autoPlay: false,
              mobileFirst: true,
              slideToScroll: 1,
              slidesToShow: 1,
              centerMode: true,
              focusOnSelect:true,

              responsive: [
                {
                  breakpoint: 1024,
                  settings: desktopSettings
                },
                {
                  breakpoint: 767,
                  settings: {
                    slidesToShow: 3,
                    centerMode: false
                  }
                },
                {
                  breakpoint: 320,
                  settings: {
                    slidesToShow: 1,
                    arrows: false,
                    variableWidth: true
                  }
                }
              ]
            });



          });

          //Fix for articles landing
          $('.views-field-title', context).once('myThirdBehavior').each(function (item) {
            $(this).prependTo(jQuery(this).next().find('.paragraph--type--atom-rtf-with-summary-'));
          });

        };

        /**
         * @method destroySlider - destroy slick slider
         */
        var destroySliders = function () {
          $sliderWrapper.slick('unslick');
        };

        return {
          init: init,
          destroySliders: destroySliders
        }

      })();


      slider.init();


    }

  };
})(jQuery, Drupal);
