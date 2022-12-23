/**
 * Header component functionality
 */
(function ($, Drupal) {
  Drupal.behaviors.header = {
    attach: function (context, settings) {

      var header = (function () {

        // Set attributes
        var windowHeight = $(window).height(),
          docHeight = $(document).height(),
          $mobileNavIcon = $('.mobile-nav-icon', context),
          $mainNav = $('.menu--main'),
          $search = $('.txt-search'),
          $searchMobileIcon = $('.search-icon.mobile'),
          $searchMobileCta = $('.search-icon__search');

        /**
         * @method init
         */
        var init = function(){
          eventHandlers();
        };

        /**
         * @method eventHandlers - Handle events
         */
        var eventHandlers = function(){

          $mobileNavIcon.on('click', function(e) {
            ($mainNav.height() === 0)? $mainNav.css('height', '100vh') : $mainNav.css('height', 0);
            $(this).toggleClass('open');
            $('.header').toggleClass('sticky-header');
            e.preventDefault();
          });

          $search.on('keyup', function (e) {
            if (e.which == 13) {
              window.location.href = '/search/node?keys=' + $search.val().trim();
            }
          });

          $searchMobileIcon.on('click', function(e) {
            $('.search-icon__wrapper').toggleClass('open');
            e.preventDefault();
          });
          $searchMobileCta.on('click', function(e) {
            window.location.href = '/search/node?keys=' + $search.val().trim();
          });
          

        };

        return {
          init: init
        }

      })();

      header.init();





    }
  };
})(jQuery, Drupal);
