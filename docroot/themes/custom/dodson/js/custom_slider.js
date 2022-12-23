jQuery('.gallery_images-wrap .field-content').slick({
    slidesToShow: 1,
    slidesToScroll: 1,
    arrows: true,
    fade: true,
    asNavFor: '.gallery_images-nav .field__items'
});
jQuery('.gallery_images-nav .field__items').slick({
    slidesToShow: 2,
    slidesToScroll: 1,
    asNavFor: '.gallery_images-wrap .field-content',
    dots: true,
    centerMode: true,
    focusOnSelect: true
});