(function ($, Drupal, window, document, undefined) {
  Drupal.behaviors.glazedIlightbox = {
    attach: function(context, settings) {
      $('.cms-portfolio-project.node-images-grid .field--name-field-cms-page-image img').each(function( index ) {
        $(this).once('cmsPortfolio-gallery').wrap('<a class="ilightbox" data-type="image" href="' + this.src + '" data-title="' + this.alt + '" data-caption="' + this.title + '">');
      });
      $('.cms-portfolio-project.node-images-grid .cms-portfolio-images')
        .once('cmsPortfolio-gallery')
        .wrapInner('<div class="row">')
        .find('.twentytwenty-wrapper')
        .addClass('col-sm-12');
      $('.cms-portfolio-project.node-images-grid.node-details-top, .cms-portfolio-project.node-images-grid.node-details-bottom')
        .once('cmsPortfolio-gallery')
        .find('.field--name-field-cms-page-image .field--item')
        .addClass('col-sm-3');
      $('.cms-portfolio-project.node-images-grid.node-details-left, .cms-portfolio-project.node-images-grid.node-details-right')
        .once('cmsPortfolio-gallery')
        .find('.field--name-field-cms-page-image .field--item')
        .addClass('col-sm-6');

      var counter = $('.ilightbox').length;
      var thumbs = true;
      var arrows = true;
      $('.ilightbox', context).iLightBox({
        skin: 'metro-black',
        path: 'horizontal',
        // linkId: deeplink,
        infinite: false,
        //fullViewPort: 'fit',
        smartRecognition: false,
        fullAlone: false,
        //fullStretchTypes: 'flash, video',
        overlay: {
          opacity: .96
        },
        controls: {
          arrows: (counter > 1 ? arrows : false),
          fullscreen: true,
          thumbnail: thumbs,
          slideshow: (counter > 1 ? true : false)
        },
        show: {
          speed: 200
        },
        hide: {
          speed: 200
        },
        social: {
          start: false,
          // buttons: social
        },
        caption: {
          start: true
        },
        styles: {
          nextOpacity: 1,
          nextScale: 1,
          prevOpacity: 1,
          prevScale: 1
        },
        effects: {
          switchSpeed: 400
        },
        slideshow: {
          pauseTime: 5000
        },
        thumbnails: {
          maxWidth: 60,
          maxHeight: 60,
          activeOpacity: .6
        },
        html5video: {
          preload: true
        }
      });
    }
  }
})(jQuery, Drupal, this, this.document);