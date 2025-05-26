(function ($) {
    'use strict';

    const browserWindow = $(window);

    // Preloader Animation
    browserWindow.on('load', () => {
        $('.preloader').fadeOut('slow', function() {
            $(this).remove();
        });
    });

    // Navigation Menu
    if ($.fn.classyNav) {
        $('#oneMusicNav').classyNav();
    }

    // Carousel Sliders
    if ($.fn.owlCarousel) {
        const welcomeSlide = $('.hero-slides');
        const testimonials = $('.testimonials-slide');
        const albumSlides = $('.albums-slideshow');

        // Hero Section Slider
        welcomeSlide.owlCarousel({
            items: 1,
            margin: 0,
            loop: true,
            nav: false,
            dots: false,
            autoplay: true,
            autoplayTimeout: 7000,
            smartSpeed: 1000,
            animateIn: 'fadeIn',
            animateOut: 'fadeOut'
        });

        // Animation handling for slider transitions
        welcomeSlide.on('translate.owl.carousel', () => {
            $("[data-animation]").each(function() {
                const anim_name = $(this).data('animation');
                $(this).removeClass('animated ' + anim_name).css('opacity', '0');
            });
        });

        welcomeSlide.on('translated.owl.carousel', () => {
            welcomeSlide.find('.owl-item.active').find("[data-animation]").each(function() {
                const anim_name = $(this).data('animation');
                $(this).addClass('animated ' + anim_name).css('opacity', '1');
            });
        });

        // Animation settings
        $("[data-delay]").each(function() {
            $(this).css('animation-delay', $(this).data('delay'));
        });

        $("[data-duration]").each(function() {
            $(this).css('animation-duration', $(this).data('duration'));
        });

        // Testimonials Slider
        testimonials.owlCarousel({
            items: 1,
            margin: 0,
            loop: true,
            dots: false,
            autoplay: true
        });

        // Album Slideshow
        albumSlides.owlCarousel({
            items: 5,
            margin: 30,
            loop: true,
            nav: true,
            navText: ['<i class="fa fa-angle-double-left"></i>', '<i class="fa fa-angle-double-right"></i>'],
            dots: false,
            autoplay: true,
            autoplayTimeout: 5000,
            smartSpeed: 750,
            responsive: {
                0: { items: 1 },
                480: { items: 2 },
                768: { items: 3 },
                992: { items: 4 },
                1200: { items: 5 }
            }
        });
    }

    // Masonry Gallery
    if ($.fn.imagesLoaded) {
        $('.oneMusic-albums').imagesLoaded(() => {
            const $grid = $('.oneMusic-albums').isotope({
                itemSelector: '.single-album-item',
                percentPosition: true,
                masonry: {
                    columnWidth: '.single-album-item'
                }
            });

            $('.catagory-menu').on('click', 'a', function() {
                $grid.isotope({ filter: $(this).attr('data-filter') });
            });
        });
    }

    // Video Popup
    if ($.fn.magnificPopup) {
        $('.video--play--btn').magnificPopup({
            disableOn: 0,
            type: 'iframe',
            mainClass: 'mfp-fade',
            removalDelay: 160,
            preloader: true,
            fixedContentPos: false
        });
    }

    // ScrollUp Button
    if ($.fn.scrollUp) {
        browserWindow.scrollUp({
            scrollSpeed: 1500,
            scrollText: '<i class="fa fa-angle-up"></i>'
        });
    }

    // Counter Animation
    if ($.fn.counterUp) {
        $('.counter').counterUp({
            delay: 10,
            time: 2000
        });
    }

    // Sticky Navigation
    if ($.fn.sticky) {
        $(".oneMusic-main-menu").sticky({
            topSpacing: 0
        });
    }

    // Progress Bar Animation
    if ($.fn.circleProgress) {
        const circleConfig = {
            size: 160,
            emptyFill: "rgba(0, 0, 0, .0)",
            fill: '#000000',
            thickness: '3',
            reverse: true
        };

        ['#circle', '#circle2', '#circle3', '#circle4'].forEach(id => {
            $(id).circleProgress(circleConfig);
        });
    }

    // Audio Player
    if ($.fn.audioPlayer) {
        $('audio').audioPlayer();
    }

    // Tooltip Initialization
    if ($.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Prevent Default Anchor Click
    $('a[href="#"]').on('click', e => e.preventDefault());

    // WOW Animation
    if (browserWindow.width() > 767) {
        new WOW().init();
    }
    
    // Category Menu Active State
    $('.catagory-menu a').on('click', function() {
        $('.catagory-menu a').removeClass('active');
        $(this).addClass('active');
    });

})(jQuery);