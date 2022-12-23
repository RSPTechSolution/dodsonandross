/**
 * Global functionality
 */

/**
 * Header component functionality
 */
(function ($, Drupal) {
  Drupal.behaviors.main = {
    attach: function (context, settings) {

      var main = (function () {


        // Set attributes
        var $verticalMenuItem = $('#block-verticalnav .menu-item--expanded');
        var $verticalMenuItemLinks = $('#block-verticalnav .menu-item--expanded .menu a');
        var $verticalMenuItem2 = $('#block-verticalnav-2 .menu-item--expanded');
        var $verticalMenuItemLinks2 = $('#block-verticalnav-2 .menu-item--expanded .menu a');
        var $menuItem = $('#block-dodson-main-menu .menu-item--expanded');
        var $menuItemLinks = $('#block-dodson-main-menu .menu-item--expanded .menu a');
        var $mainMenuItemMobile = $('#block-dodson-main-menu .menu-item--mobile');
        var $mainMenuNav = $('#block-dodson-main-menu');
        var $leadersBoxes = $('.featured-people > .field--name-field-card-section > .field__item > .paragraph--type--card > .field--name-field-card-section > div');

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
          $($mainMenuNav).removeClass('menu-item--large');

          $mainMenuItemMobile.on('click', function (e) {
            $($mainMenuNav).addClass('menu-item--large');
          });

          $menuItem.once('menuItem').on('click', function (e) {
            $(this).toggleClass('open');
            $(this).find('> .menu').slideToggle();
            e.stopPropagation();
            e.preventDefault();
          });

          $menuItemLinks.not('.menu-item--expanded > a').once('menuItemLinks').on('click', function (e) {
            e.stopPropagation();
          });

          $verticalMenuItem.once('menuItem').on('click', function(e){
            $(this).toggleClass('open');
            $(this).find('> .menu').slideToggle();
            e.stopPropagation();
            e.preventDefault();
          });

          $verticalMenuItemLinks.not('.menu-item--expanded > a').once('menuItemLinks').on('click', function(e){
            e.stopPropagation();
          });

          $verticalMenuItem2.once('menuItem').on('click', function(e){
            $(this).toggleClass('open');
            $(this).find('> .menu').slideToggle();
            e.stopPropagation();
            e.preventDefault();
          });

          $verticalMenuItemLinks2.not('.menu-item--expanded > a').once('menuItemLinks').on('click', function(e){
            e.stopPropagation();
          });

        };

        return {
          init: init
        }

      })();

      main.init();

    }
  };
  Drupal.behaviors.Artplayer = {
    attach: function (context, settings) {
      
    }
  };


})(jQuery, Drupal);

jQuery(document).ajaxComplete(function(){

	var pathname = window.location.pathname;
  if(pathname == '/betty-fine-art') {
    	var href = jQuery('.view-betty-fine-art ul.pager__items li.is-active a').attr('href');
    	if(href.length > 0) {
    		var splited = href.split('&');
    		// history.pushState(null, null, '?'+splited[1]);
    		history.pushState(null, null, href);
    	}
    	jQuery('.view-betty-fine-art select').on('change', function(){
    	    var splited = href.split('&');
    	    history.pushState(null, null, splited[0]);
    	});
  }
	

var elementhidden = [];
  if(jQuery('.hidden .item-list-cat li .views-field-tid .field-content').length > 0){
     jQuery('.hidden .item-list-cat li .views-field-tid .field-content').each(function(index, elem){
          var valueElement = jQuery(elem).text();
          elementhidden.push(valueElement);
    });   
  }
 if(elementhidden.length > 0) {
   jQuery('.js-form-item-field-gallery-category-target-id select option').each(function(i,ele){
      var val = jQuery(ele).attr('value');
      if(val != "All"){
        if(jQuery.inArray(val, elementhidden )  !== -1 ){
        } else{ 
         jQuery(ele).remove();
        } 
      }
  });
 }else{
   jQuery('.view-gallery-items .view-filters').hide();
 }
 })


jQuery(document).ready(function(){


var pathname = window.location.pathname;
var arr = pathname.split('/');
var val = arr[4];
if(val) {
  var nw = val.replace(/[0-9]+/, "*");
}
var new_path = '/'+arr[1]+'/'+arr[2]+'/'+arr[3]+'/'+nw+'/'+arr[5];
  if (window.location.href.indexOf("videos") > -1 || new_path == '/comment/reply/node/*/field_comments' || window.location.href.indexOf("node") > -1) {
      
    
      var video_240 = jQuery('.field--name-field-video-link-240p').text();
      var video_720 = jQuery('.field--name-field-video-link-720p').text();
      var video_480 = jQuery('.field--name-field-video-link-480p').text();
      var video_360 = jQuery('.field--name-field-video-link-360p').text();
      var original_video = jQuery('.field--name-field-video-link').text();

      const player = new Plyr('#player', {
        // title: 'Example Title',
        // clickToPlay: true,
        settings: ["speed", "quality"],
        quality: {
          default: 480,
          options: [720, 480, 360, 240],
          forced: true,
          onChange: null
        }        
      });
      // const player = new Plyr('#player');

      player.source = {
        type: 'video',
        // title: 'Example title',
        sources: [
          {
            src: video_720,
            // type: 'video/mp4',
            size: 720,
          },
          {
            src: video_480,
            // type: 'video/webm',
            size: 480,
          },
          {
            src: video_360,
            // type: 'video/webm',
            size: 360,
          },
          {
            src: video_240,
            // type: 'video/webm',
            size: 240,
          },
        ],
        
      };

      // var art = new Artplayer({
      //     container: '#player',
      //     url: video_480,
      //     // title: 'One More Time One More Chance',
      //     // poster: '/assets/sample/poster.jpg',
      //     volume: 0.5,
      //     isLive: false,
      //     muted: false,
      //     autoplay: false,
      //     pip: true,
      //     autoSize: true,
      //     autoMini: true,
      //     screenshot: true,
      //     setting: true,
      //     loop: true,
      //     flip: true,
      //     rotate: true,
      //     playbackRate: true,
      //     aspectRatio: true,
      //     fullscreen: true,
      //     fullscreenWeb: true,
      //     subtitleOffset: true,
      //     miniProgressBar: true,
      //     localVideo: true,
      //     localSubtitle: true,
      //     networkMonitor: false,
      //     mutex: true,
      //     light: true,
      //     backdrop: true,
      //     theme: '#ffad00',
      //     lang: navigator.language.toLowerCase(),
      //     // moreVideoAttr: {
      //     //     crossOrigin: 'anonymous',
      //     // },
      //     quality: [ 
      //         {
      //             name: '720P',
      //             url: video_720,
      //         },
      //         {
      //             default: true,
      //             name: '480P',
      //             url: video_480,
      //         },
      //         {
      //             name: '360P',
      //             url: video_360,
      //         },
      //         {
      //             name: '240P',
      //             url: video_240,
      //         },
      //     ],
      // });
    }

  jQuery(".video-help-text-block .click-here-text").click(function(){
    jQuery(".video-help-images").slideToggle();
    jQuery(".video-help-text-block .click-here-text").toggleClass("icon-rotate");
  });

  jQuery('.layout-sidebar-first').theiaStickySidebar({
    additionalMarginTop: 80,
    minWidth: 992
  });

  jQuery('.slider---for--wrapper .slider---for').slick({
    slidesToShow: 1,
    slidesToScroll: 1,
    arrows: false,
    fade: true,
    asNavFor: '.slider---nav',
    responsive: [
      {
        breakpoint: 1024,
        settings: {
          slidesToShow: 1,
          centerMode: false
        }
      },
      {
        breakpoint: 767,
        settings: {
          slidesToShow: 1,
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

  jQuery('.slider---nav--wrapper .slider---nav').slick({
    slidesToShow: 5,
    slidesToScroll: 1,
    asNavFor: '.slider---for',
    // dots: true,
    // centerMode: true,
    focusOnSelect: true,
    responsive: [
      {
        breakpoint: 1024,
        settings: {
          slidesToShow: 5,
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
          slidesToShow: 1,
          arrows: false,
          variableWidth: true
        }
      }
    ]
  });

  jQuery('.view-id-gallery .view-content .views-field-field-images .field-content').slick({
    slidesToShow: 4,
    slidesToScroll: 1,
    focusOnSelect: true,
    responsive: [
      {
        breakpoint: 1024,
        settings: {
          slidesToShow: 3,
          centerMode: false
        }
      },
      {
        breakpoint: 575,
        settings: {
          slidesToShow: 1,
          centerMode: true,
          arrows: true,
          variableWidth: true
        }
      },
      {
        breakpoint: 768,
        settings: {
          slidesToShow: 3,
          centerMode: false,
          arrows: true
        }
      },
      {
        breakpoint: 320,
        settings: {
          slidesToShow: 1,
          arrows: false,
          centerMode: true,
          variableWidth: true
        }
      }
    ]
  });


  jQuery('div.search-icon.mobile').on("click", function () {
    if(jQuery(this).hasClass('search-open')){
        jQuery('div.search-icon.mobile.search-open').removeClass('search-open');
        
    }else{
        jQuery(this).addClass('search-open');
    }
    jQuery('div.mobile-nav-icon').removeClass('open');
    jQuery('.block.block-menu.navigation.menu--main').css('height', '0px');
  });


  jQuery('div.mobile-nav-icon').on('click', function(){
    if(jQuery('div.search-icon.mobile').hasClass('search-open')){
        jQuery('div.search-icon.mobile.search-open').removeClass('search-open');
        jQuery('div.search-icon__wrapper.open').removeClass('open');
    }
  });


 function isEmpty( el ){
      return !jQuery.trim(el.html())
 }

  if (isEmpty(jQuery('.view-id-audio_podcast.view-display-id-block_1'))) {
      jQuery('.premium-wrapper').remove();
  }

});