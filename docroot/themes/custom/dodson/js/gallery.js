/**
 * Gallery component functionality
 */
(function ($, Drupal) {
  Drupal.behaviors.gallery = {
    attach: function (context, settings) {

      var imageGallery = (function(){

        // Attributes
        var $imageGallery = $('.image-gallery');

        var $slickItems = $imageGallery.find('.slick-slide > div > .field__item');
        var $currentSlider = $imageGallery.find('> .field--name-field-card-section');
        var html;

        $.each($slickItems, function(index, item){
          html += $(item).html();
        });

        var featuredImageDiv = '<div class="featured__product-image">'+ html +'</div>';

          /**
           * @method init
           */
          var init = function(){
            insertMainGalleryItem();
            initSlickSliders();
          };

          /**
           * @method insertMainGalleryItem
           */
          var insertMainGalleryItem = function(){
            $imageGallery.once('prepend').prepend(featuredImageDiv);
          };

          var initSlickSliders = function(){

            // Destroy previous initialized slider
            $currentSlider.once('destroySlide').slick('unslick');

            // Init with new settings
            $currentSlider.once('initSlide').slick({
              autoPlay: false,
              mobileFirst: true,
              slideToScroll: 1,
              slidesToShow: 1,
              centerMode: true,
              focusOnSelect: true,
              asNavFor: '.featured__product-image',

              responsive: [
                {
                  breakpoint: 1024,
                  settings: {
                    slidesToShow: 3,
                    centerMode: false
                  }
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
                    slidesToShow: 1
                  }
                }
              ]
            });

            // Top big gallery image
            $('.featured__product-image').once('initBigSlide').slick({
              slidesToShow: 1,
              slidesToScroll: 1,
              arrows: false,
              fade: true
            });

          };

          return {
            init: init
          }

      })();

      imageGallery.init();

    }
  };
})(jQuery, Drupal);
