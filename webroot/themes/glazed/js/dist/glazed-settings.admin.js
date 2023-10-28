(function ($, Drupal) {
  /*global jQuery:false */
  /*global Drupal:false */
  "use strict";

  $(window).on("load", function() {
      // moved this back up here because it was brakign states API changes on checkboxes
      jQuery("[type='checkbox']").bootstrapSwitch();

    // remove color module locks, they are broken when bootstrap theme loads
    $('.color-palette__lock, .color-palette__hook').remove();
  });

  // Drupal.attachBehaviors('#system-theme-settings');

  /**
   * Provide vertical tab summaries for Bootstrap settings.
   */
  Drupal.behaviors.glazedSettingsControls = {
    attach: function (context) {

      $('#system-theme-settings h2 > small').addClass('well form-header');
      var $input = '';
      // jQuery once is not working..
      $('#system-theme-settings .form-type-radio .control-label').not('glazedProcessed').each( function() {
        $(this).addClass('glazedProcessed');
        $input = $(this).find('input').remove();
        $(this).wrapInner('<span class="glazed-label">').prepend($input);
      });

      function glazed_map_color (color) {
        if (color in drupalSettings.glazedSettings.palette) {
          color = drupalSettings.glazedSettings.palette[color];
        }
        return color;
      }

      // CONVERT CHECKBOXES TO SWITCHES
      $.fn.bootstrapSwitch.defaults.onColor = "success";
      $.fn.bootstrapSwitch.defaults.onText = "On";
      $.fn.bootstrapSwitch.defaults.offText = "Off";
      $.fn.bootstrapSwitch.defaults.size = "small";
      $.fn.bootstrapSwitch.defaults.onSwitchChange = function(event, state) {
      // This patched up incompatibility with $ <1.10
      // https://github.com/nostalgiaz/bootstrap-switch/issues/474
        $(this).trigger('change');
        setTimeout(function(){ $('.slider + input').bootstrapSlider('relayout'); }, 10);
      };

      // BOOTSTRAP SLIDER CONFIG

      // Opacity Sliders
      var $opacitySliders = $(
        '#edit-header-top-bg-opacity-scroll, #edit-header-top-bg-opacity, #edit-header-side-bg-opacity, #edit-side-header-background-opacity,#edit-page-title-image-opacity,#edit-header-top-opacity,#edit-header-top-opacity-scroll,#edit-menu-full-screen-opacity'
      );
      var startValue = 1;
      $opacitySliders.each( function() {
        startValue = $(this).val();
        $(this).bootstrapSlider({
          step: 0.01,
          min: 0,
          max: 1,
          tooltip: 'show',
          value: parseFloat(startValue)
        });
      });

      // Line Height Sliders
      var $lhSliders = $('.line-height-slider');
      var startValue = 1;
      $lhSliders.each( function() {
        startValue = $(this).val();
        $(this).bootstrapSlider({
          step: 0.1,
          min: 0,
          max: 3,
          tooltip: 'show',
          formatter: function(value) {return value + 'em';},
          value: parseFloat(startValue)
        });
      });

      // Border Size Sliders
      var $lhSliders = $('.border-size-slider');
      var startValue = 1;
      $lhSliders.each( function() {
        startValue = $(this).val();
        $(this).bootstrapSlider({
          step: 1,
          min: 0,
          max: 30,
          tooltip: 'show',
          formatter: function(value) {return value + 'px';},
          value: parseFloat(startValue)
        });
      });

      // Border Radius Sliders
      var $lhSliders = $('.border-radius-slider');
      var startValue = 1;
      $lhSliders.each( function() {
        startValue = $(this).val();
        $(this).bootstrapSlider({
          step: 1,
          min: 0,
          max: 100,
          tooltip: 'show',
          formatter: function(value) {return value + 'px';},
          value: parseFloat(startValue)
        });
      });

      // Body Font Size
      var $input = $('#edit-body-font-size');
      $input.bootstrapSlider({
        step: 1,
        min: 8,
        max: 30,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Nav Font Size
      $input = $('#edit-nav-font-size');
      $input.bootstrapSlider({
        step: 1,
        min: 8,
        max: 30,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Body Mobile Font Size
      $input = $('#edit-body-mobile-font-size');
      $input.bootstrapSlider({
        step: 1,
        min: 8,
        max: 30,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Nav Mobile Font Size
      $input = $('#edit-nav-mobile-font-size');
      $input.bootstrapSlider({
        step: 1,
        min: 8,
        max: 30,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Other Font Sizes
      var $fsSliders = $('.font-size-slider');
      var startValue = 1;
      $fsSliders.each( function() {
        startValue = $(this).val();
        $(this).bootstrapSlider({
          step: 1,
          min: 8,
          max: 100,
          tooltip: 'show',
          formatter: function(value) {return value + 'px';},
          value: parseFloat(startValue)
        });
      });

      // Scale Factor
      $input = $('#edit-scale-factor');
      $input.bootstrapSlider({
        step: 0.01,
        min: 1,
        max: 2,
        tooltip: 'show',
        value: parseFloat($input.val())
      });

      // Divider Thickness
      $input = $('#edit-divider-thickness');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 20,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Divider Thickness
      $input = $('#edit-block-divider-thickness');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 20,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Divider Length
      $input = $('#edit-divider-length');
      $input.bootstrapSlider({
        step: 10,
        min: 0,
        max: 500,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Divider Length
      $input = $('#edit-block-divider-length');
      $input.bootstrapSlider({
        step: 10,
        min: 0,
        max: 500,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      function formatPosition(pos) {
        var label = Drupal.t('Left');
        if (pos == 2) label = Drupal.t('Center');
        if (pos == 3) label = Drupal.t('Right');
        return label;
      }

      // Divider Position
      $input = $('#edit-divider-position');
      $input.bootstrapSlider({
        step: 1,
        min: 1,
        max: 3,
        selection: 'none',
        tooltip: 'show',
        formatter: formatPosition,
        value: parseFloat($input.val())
      });

      // Headings letter spacing
      $input = $('#edit-headings-letter-spacing');
      $input.bootstrapSlider({
        step: 0.01,
        min: -0.10,
        max: 0.3,
        tooltip: 'show',
        formatter: function(value) {return value + 'em';},
        value: parseFloat($input.val())
      });

      // Block Design Divider Spacing
      $input = $('#edit-block-divider-spacing');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Page Title height
      $input = $('#edit-page-title-height');
      $input.bootstrapSlider({
        step: 5,
        min: 50,
        max: 500,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Header height slider
      $input = $('#edit-header-top-height');
      $input.bootstrapSlider({
        step: 1,
        min: 10,
        max: 200,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Header Mobile Breakpoint slider
      $input = $('#edit-header-mobile-breakpoint');
      $input.bootstrapSlider({
        step: 10,
        min: 480,
        max: 4100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Header Mobile height slider
      $input = $('#edit-header-mobile-height');
      $input.bootstrapSlider({
        step: 1,
        min: 10,
        max: 200,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Header after-scroll height slider
      $input = $('#edit-header-top-height-scroll');
      $input.bootstrapSlider({
        step: 1,
        min: 10,
        max: 200,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Sticky header scroll offset
      $input = $('#edit-header-top-height-sticky-offset');
      $input.bootstrapSlider({
        step: 10,
        min: 0,
        max: 2096,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Side Header after-scroll height slider
      $input = $('#edit-header-side-width');
      $input.bootstrapSlider({
        step: 5,
        min: 50,
        max: 500,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Main Menu Hover Border Thickness
      $input = $('#edit-menu-border-size');
      $input.bootstrapSlider({
        step: 1,
        min: 1,
        max: 20,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Main Menu Hover Border Position Offset
      $input = $('#edit-menu-border-position-offset');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Main Menu Hover Border Position Offset Sticky
      $input = $('#edit-menu-border-position-offset-sticky');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Layout max width
      $input = $('#edit-layout-max-width');
      $input.bootstrapSlider({
        step: 10,
        min: 480,
        max: 4100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Box max width
      $input = $('#edit-box-max-width');
      $input.bootstrapSlider({
        step: 10,
        min: 480,
        max: 4100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Layout Gutter Horizontal
      $input = $('#edit-gutter-horizontal');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Layout Gutter Vertical
      $input = $('#edit-gutter-vertical');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Layout Gutter Vertical
      $input = $('#edit-gutter-container');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 500,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Layout Gutter Horizontal Mobile
      $input = $('#edit-gutter-horizontal-mobile');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Layout Gutter Vertical Mobile
      $input = $('#edit-gutter-vertical-mobile');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 100,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Layout Gutter Vertical
      $input = $('#edit-gutter-container-mobile');
      $input.bootstrapSlider({
        step: 1,
        min: 0,
        max: 500,
        tooltip: 'show',
        formatter: function(value) {return value + 'px';},
        value: parseFloat($input.val())
      });

      // Reflow layout when showing a tab
      // var $sliders = $('.slider + input');
      // $sliders.each( function() {
      //   $slider = $(this);
      //   $('.vertical-tab-button').click(function() {
      //     $slider.bootstrapSlider('relayout');
      //   });
      // });
      $('.vertical-tab-button a').click(function() {
        $('.slider + input').bootstrapSlider('relayout');
      });
      $('input[type="radio"]').change(function() {
        $('.slider + input').bootstrapSlider('relayout');
      });

      // Typographic Scale Master Slider
      var base = 14;
      var factor = 1.25;
      $('#edit-scale-factor').change(function() {
        base = $('#edit-body-font-size').val();
        factor = $(this).bootstrapSlider('getValue');
        $('#edit-h1-font-size, #edit-h1-mobile-font-size').bootstrapSlider('setValue', base * Math.pow(factor, 4)).change();
        $('#edit-h2-font-size, #edit-h2-mobile-font-size').bootstrapSlider('setValue', base * Math.pow(factor, 3)).change();
        $('#edit-h3-font-size, #edit-h3-mobile-font-size').bootstrapSlider('setValue', base * Math.pow(factor, 2)).change();
        $('#edit-h4-font-size, #edit-h4-mobile-font-size, #edit-blockquote-font-size, #edit-blockquote-mobile-font-size').bootstrapSlider('setValue', base * factor).change();
      });

      // Block Design Preset Loader
      var preset = '';
      $('#edit-block-preset').bind('keyup change', function() {
        // Reset defaults
        $('#edit-block-advanced .slider + .form-text').bootstrapSlider('setValue', 0);
        $('#edit-block-divider-thickness').bootstrapSlider('setValue', parseInt($('#edit-divider-thickness').val()));
        $('#edit-block-divider-length').bootstrapSlider('setValue', parseInt($('#edit-divider-length').val()));
        $('#edit-block-divider-spacing').bootstrapSlider('setValue', 10);
        $('#edit-block-divider, #edit-block-divider-custom, #edit-title-sticker').bootstrapSwitch('state', false);
        $('#edit-block-background-custom, #edit-title-background-custom, #edit-block-divider-color-custom').val('');
        $('#edit-block-advanced select').val('');
        $('#edit-title-align-left').prop("checked", true);
        $('#edit-title-font-size-h2').prop("checked", true);

        // Set presets
        preset = $(this).val();
        switch (preset) {
          case 'block_boxed':
            $('#edit-block-padding').bootstrapSlider('setValue', 15);
            $('#edit-block-border').bootstrapSlider('setValue', 5);
            $('#edit-block-border-color').val('text');
            break;
          case 'block_outline':
            $('#edit-block-padding').bootstrapSlider('setValue', 15);
            $('#edit-block-border').bootstrapSlider('setValue', 1);
            $('#edit-block-border-color').val('text');
            break;
          case 'block_well':
            $('#edit-block-well').val('well');
            $('#edit-title-font-size-h3').prop("checked", true);
            break;
          case 'title_inverted':
            $('#edit-title-background').val('text');
            $('#edit-title-well').val('well glazed-util-background-gray');
            $('#edit-title-padding').bootstrapSlider('setValue', 10);
            $('#edit-title-font-size-h3').prop("checked", true);
            break;
          case 'title_inverted_shape':
            $('#edit-title-background').val('text');
            $('#edit-title-well').val('well glazed-util-background-gray');
            $('#edit-title-padding').bootstrapSlider('setValue', 10);
            $('#edit-title-border-radius').bootstrapSlider('setValue', 100);
            $('#edit-title-font-size-h4').prop("checked", true);
            $('#edit-title-align-center').prop("checked", true);
            break;
          case 'title_sticker':
            $('#edit-title-well').val('well glazed-util-background-gray');
            $('#edit-title-padding').bootstrapSlider('setValue', 10);
            $('#edit-title-sticker').bootstrapSwitch('state', true);
            $('#edit-title-font-size-body').prop("checked", true);
            break;
          case 'title_sticker_color':
            $('#edit-title-font-size-body').prop("checked", true);
            $('#edit-title-padding').bootstrapSlider('setValue', 10);
            $('#edit-title-well').val('well bg-primary');
            $('#edit-title-sticker').bootstrapSwitch('state', true);
            break;
          case 'title_outline':
            $('#edit-title-padding').bootstrapSlider('setValue', 15);
            $('#edit-title-border').bootstrapSlider('setValue', 1);
            $('#edit-title-border-color').val('text');
            $('#edit-title-font-size-h4').prop("checked", true);
            break;
          case 'default_divider':
            $('#edit-block-divider').bootstrapSwitch('state', true);
            break;
          case 'hairline_divider':
            $('#edit-block-divider').bootstrapSwitch('state', true);
            $('#edit-block-divider-custom').bootstrapSwitch('state', true);
            $('#edit-block-divider-thickness').bootstrapSlider('setValue', 1);
            break;
        }
        $('#edit-block-advanced input, #edit-block-advanced select').trigger('change');
        if ($('#edit-block-padding').val() == 0) {
          $('#edit-block .block').css('padding', '');
        }
        if ($('#edit-title-padding').val() == 0) {
          $('#edit-block .block-title').css('padding', '');
        }
         $(this).val(preset);
      });


      // TYPOGRAPHY LIVE PREVIEW

      $('#edit-body-line-height').change(function() {
        $('.type-preview, .type-preview p').css('line-height', $(this).bootstrapSlider('getValue'));
      });
      $('#edit-headings-line-height').change(function() {
        $('.type-preview h1, .type-preview h2, .type-preview h3, .type-preview h4').css('line-height', $(this).bootstrapSlider('getValue'));
      });
      $('#edit-divider-thickness').change(function() {
        $('.type-preview hr').css('height', $(this).bootstrapSlider('getValue'));
      });
      var width = '';
      $('#edit-divider-length').change(function() {
        width = $(this).bootstrapSlider('getValue');
        if (width == 0) {
          $('.type-preview hr').css('width', '100%');
        }
        else {
          $('.type-preview hr').css('width', width);
        }
      });
      var position = '';
      var $hr = false;
      $('#edit-divider-position').change(function() {
        var position = $(this).bootstrapSlider('getValue');
        $hr = $('.type-preview hr');
        if (position == 1) {
          $hr.css({'margin-left' : '0','margin-right' : 'auto'});
        }
        if (position == 2) {
          $hr.css({'margin-left' : 'auto','margin-right' : 'auto'});
        }
        if (position == 3) {
          $hr.css({'margin-left' : 'auto','margin-right' : '0'});
        }
      });
      $('#edit-divider-color').change(function() {
        $('.type-preview hr').css('background-color', glazed_map_color($(this).val()));
      });
      $('#edit-divider-color-custom').bind('keyup change', function() {
        $('.type-preview hr').css('background-color', $(this).val());
      });
      $('#edit-blockquote-line-height').change(function() {
        $('.type-preview blockquote, .type-preview blockquote p').css('line-height', $(this).bootstrapSlider('getValue'));
      });
      $('#edit-body-font-size').change(function() {
        $('.type-preview, .type-preview p').css('font-size', $(this).bootstrapSlider('getValue') + 'px');
        $('.lead').css('font-size', '21px');
        $('#edit-scale-factor').change();
      });
      $('#edit-nav-font-size').change(function() {
        $('.glazed-header--top #glazed-main-menu .nav > li > a, .glazed-header--side #glazed-main-menu .nav a').css('font-size', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-h1-font-size').change(function() {
        $('.type-preview h1').css('font-size', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-h2-font-size').change(function() {
        $('.type-preview h2').css('font-size', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-h3-font-size').change(function() {
        $('.type-preview h3').css('font-size', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-h4-font-size').change(function() {
        $('.type-preview h4').css('font-size', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-blockquote-font-size').change(function() {
        $('.type-preview blockquote, .type-preview blockquote p').css('font-size', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-headings-letter-spacing').change(function() {
        $('.type-preview h1, .type-preview h2, .type-preview h3, .type-preview h4')
          .css('letter-spacing', $(this).bootstrapSlider('getValue') + 'em');
      });
      $('#edit-headings-uppercase').on('switchChange.bootstrapSwitch', function(event, state) {
        if (state) {
          $('.type-preview h1, .type-preview h2, .type-preview h3, .type-preview h4').css('text-transform', 'uppercase');
        }
        else {
          $('.type-preview h1, .type-preview h2, .type-preview h3, .type-preview h4').css('text-transform', 'none');
        }
      });

      var value = '';
      // BLOCK DESIGN LIVE PREVIEW
      $('#edit-block-advanced').bind('keyup change', function() {
        $('#edit-block-preset').val('custom');
      });

      $('#edit-block-well').change(function() {
        $('.block-preview .block').removeClass('well bg-primary glazed-util-background-accent1 glazed-util-background-accent2 glazed-util-background-black glazed-util-background-white glazed-util-background-gray');
        $('.block-preview .block').addClass($(this).val());
      });
      $('#edit-block-background').change(function() {
        $('.block-preview .block').css('background-color', glazed_map_color($(this).val()));
      });
      $('#edit-block-background-custom').change(function() {
        $('.block-preview .block').css('background-color', $(this).val());
      });
      $('#edit-block-padding').bind('keyup change', function() {
        $('.block-preview .block').css('padding', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-block-border').change(function() {
        $('.block-preview .block').css('border-width', $(this).bootstrapSlider('getValue') + 'px');
        if ($(this).bootstrapSlider('getValue') > 0) {
          $('.block-preview .block').css('border-style', 'solid');
        }
      });
      $('#edit-block-border-color').change(function() {
        $('.block-preview .block').css('border-color', glazed_map_color($(this).val()));
      });
      $('#edit-block-border-color-custom').bind('keyup change', function() {
        $('.block-preview .block').css('border-color', $(this).val());
      });
      $('#edit-block-border-radius').change(function() {
        $('.block-preview .block').css('border-radius', $(this).bootstrapSlider('getValue') + 'px');
      });
      // Block title
      $('#edit-title-well').change(function() {
        $('.block-preview .block-title').removeClass('well bg-primary glazed-util-background-accent1 glazed-util-background-accent2 glazed-util-background-black glazed-util-background-white glazed-util-background-gray');
        $('.block-preview .block-title').addClass($(this).val());
      });
      $('#edit-title-font-size').change(function() {
        // Retrieve the matching font size from the typography settings
        value = $(this).find(':checked').val();
        value = '#edit-' + value + '-font-size';
        value = $(value).val();
        $('.block-preview .block-title').css('font-size', value + 'px');
      });
      $('#edit-title-align').change(function() {
        $('.block-preview .block-title').css('text-align', $(this).find(':checked').val());
      });
      $('#edit-title-background').change(function() {
        $('.block-preview .block-title').css('background-color', glazed_map_color($(this).val()));
      });
      $('#edit-title-background-custom').bind('keyup change', function() {
        $('.block-preview .block-title').css('background-color', $(this).val());
      });
      $('#edit-title-sticker').on('switchChange.bootstrapSwitch', function(event, state) {
        if (state) {
          $('.block-preview .block-title').css('display', 'inline-block');
        }
        else {
          $('.block-preview .block-title').css('display', 'block');
        }
      });
      $('#edit-title-padding').change(function() {
        $('.block-preview .block-title').css('padding', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-title-border').change(function() {
        $('.block-preview .block-title').css('border-width', $(this).bootstrapSlider('getValue') + 'px');
        if ($(this).bootstrapSlider('getValue') > 0) {
          $('.block-preview .block-title').css('border-style', 'solid');
        }
      });
      $('#edit-title-border-radius').change(function() {
        $('.block-preview .block-title').css('border-radius', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-title-border-color').change(function() {
        $('.block-preview .block-title').css('border-color', glazed_map_color($(this).val()));
      });
      $('#edit-title-border-color-custom').bind('keyup change', function() {
        $('.block-preview .block-title').css('border-color', $(this).val());
      });
      // Block divider
      if ($('#edit-block-divider:checked').length == 0) {
        $('.block-preview hr').hide();
      }
      $('#edit-block-divider').on('switchChange.bootstrapSwitch', function(event, state) {
        if (state) {
          $('.block-preview hr').show();
        }
        else {
          $('.block-preview hr').hide();
        }
      });
      $('#edit-block-divider-color').change(function() {
        $('.block-preview hr').css('background-color', glazed_map_color($(this).val()));
      });
      $('#edit-block-divider-color-custom').bind('keyup change', function() {
        $('.block-preview hr').css('background-color', $(this).val());
      });
      $('#edit-block-divider-thickness').change(function() {
        $('.block-preview hr').css('height', $(this).bootstrapSlider('getValue') + 'px');
      });
      $('#edit-block-divider-length').change(function() {
        if ($(this).bootstrapSlider('getValue') > 0) {
          $('.block-preview hr').css('width', $(this).bootstrapSlider('getValue') + 'px');
        }
        else {
          $('.block-preview hr').css('width', '100%');
        }
      });
      $('#edit-block-divider-spacing').change(function() {
        $('.block-preview hr').css('margin-top', $(this).bootstrapSlider('getValue') + 'px');
        $('.block-preview hr').css('margin-bottom', $(this).bootstrapSlider('getValue') + 'px');
      });


    }
  };

  /**
   * Provide vertical tab summaries for Bootstrap settings.
   *
   * Since the number of settings categories has grown I decided to remove
   * summaries as to lighten this navigation and clear it up.
   */
  // Drupal.behaviors.glazedSettingSummaries = {
  //   attach: function (context) {
  //     var $context = $(context);

  //     // Page Title.
  //     $context.find('#edit-page-title').drupalSetSummary(function () {
  //       var summary = [];

  //       var align = $context.find('input[name="page_title_align"]:checked');
  //       if (align.val()) {
  //         summary.push(Drupal.t('Align @align', {
  //           '@align': align.find('+label').text()
  //         }));
  //       }

  //       var animate = $context.find('input[name="page_title_animate"]:checked');
  //       if (animate.val()) {
  //         summary.push(Drupal.t('@animate', {
  //           '@animate': animate.find('+label').text()
  //         }));
  //       }

  //       if ($context.find(':input[name="page_title_breadcrumbs"]').is(':checked')) {
  //         summary.push(Drupal.t('Crumbs'));
  //       } else {
  //         summary.push(Drupal.t('No Crumbs'));
  //       }
  //       return summary.join(', ');

  //     });

  //     // Menu.
  //     $context.find('#edit-menu').drupalSetSummary(function () {
  //       var summary = [];

  //       var menu = $context.find('input[name="menu_type"]:checked');
  //       if (menu.val()) {
  //         summary.push(Drupal.t('@menu', {
  //           '@menu': menu.find('+label').text()
  //         }));
  //       }
  //       return summary.join(', ');

  //     });

  //     // Colors.
  //     $context.find('#color_scheme_form').drupalSetSummary(function () {
  //       var summary = [];

  //       var scheme = $context.find('select[name="scheme"] :selected');
  //       if (scheme.val()) {
  //         summary.push(Drupal.t('@scheme', {
  //           '@scheme': scheme.text()
  //         }));
  //       }
  //       return summary.join(', ');

  //     });

  //     // Layout.
  //     $context.find('#edit-layout').drupalSetSummary(function () {
  //       var summary = [];

  //       var layoutWidth = $context.find('input[name="layout_max_width"]');
  //       if (layoutWidth.length) {
  //         summary.push(Drupal.t('@layoutWidth', {
  //           '@layoutWidth': layoutWidth.val() + 'px'
  //         }));
  //       }

  //       return summary.join(', ');

  //     });

  //     // Header.
  //     $context.find('#edit-header').drupalSetSummary(function () {
  //       var summary = [];

  //       if ($context.find(':input[name="header_position"]').is(':checked')) {
  //         summary.push(Drupal.t('Side Header'));
  //       } else {
  //         summary.push(Drupal.t('Top Header'));
  //       }
  //       return summary.join(', ');

  //     });

  //     // Typography.
  //     $context.find('#edit-fonts').drupalSetSummary(function () {
  //       var summary = [];

  //       var typography = $context.find('select[name="body_font_face"] :selected');
  //       if (typography.val()) {
  //         summary.push(Drupal.t('Base: @typography', {
  //           '@typography': typography.text()
  //         }));
  //       }
  //       return summary.join(', ');

  //     });
  //   }
  // };

})(jQuery, Drupal);
