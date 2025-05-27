(function ($) {
    'use strict';

    const browserWindow = $(window);
    const PLUGINS = {
        classyNav: '#oneMusicNav',
        owlCarousel: {
            welcomeSlide: '.hero-slides',
            testimonials: '.testimonials-slide',
            albumSlides: '.albums-slideshow'
        },
        counterUp: '.counter',
        sticky: '.oneMusic-main-menu',
        circleProgress: ['#circle', '#circle2', '#circle3', '#circle4'],
        audioPlayer: 'audio',
        tooltip: '[data-toggle="tooltip"]'
    };

    // Preloader Animation
    browserWindow.on('load', () => {
        $('.preloader').fadeOut('slow', function() {
            $(this).remove();
        });
    });

    // Initialize all plugins
    function initializePlugins() {
        // ClassyNav
        if ($.fn.classyNav) {
            $(PLUGINS.classyNav).classyNav();
        }

        // OwlCarousel
        if ($.fn.owlCarousel) {
            const welcomeSlide = $(PLUGINS.owlCarousel.welcomeSlide);
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
        }

        // CounterUp
        if ($.fn.counterUp) {
            $(PLUGINS.counterUp).counterUp({
                delay: 10,
                time: 2000
            });
        }

        // Sticky
        if ($.fn.sticky) {
            $(PLUGINS.sticky).sticky({
                topSpacing: 0
            });
        }

        // CircleProgress
        if ($.fn.circleProgress) {
            const circleConfig = {
                size: 160,
                emptyFill: "rgba(0, 0, 0, .0)",
                fill: '#000000',
                thickness: '3',
                reverse: true
            };

            PLUGINS.circleProgress.forEach(id => {
                $(id).circleProgress(circleConfig);
            });
        }

        // AudioPlayer
        if ($.fn.audioPlayer) {
            $(PLUGINS.audioPlayer).audioPlayer();
        }

        // Tooltip
        if ($.fn.tooltip) {
            $(PLUGINS.tooltip).tooltip();
        }
    }

    // Initialize animations
    function initializeAnimations() {
        const animationElements = $("[data-animation]");
        animationElements.each(function() {
            const $this = $(this);
            const animName = $this.data('animation');
            const delay = $this.data('delay');
            const duration = $this.data('duration');

            if (delay) $this.css('animation-delay', delay);
            if (duration) $this.css('animation-duration', duration);
        });
    }

    // Initialize all features
    function initialize() {
        initializePlugins();
        initializeAnimations();

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
    }

    // Start initialization
    initialize();

})(jQuery);