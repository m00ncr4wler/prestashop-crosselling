$(document).ready(function () {
    if (!!$.prototype.bxSlider) {
        $('.bxslider').bxSlider({
            minSlides: 1,
            maxSlides: 99,
            slideWidth: 178,
            slideMargin: 20,
            pager: true,
            nextText: '',
            prevText: '',
            infiniteLoop: false,
            hideControlOnEnd: true
        });
    }
});