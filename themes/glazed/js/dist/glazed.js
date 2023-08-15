/**
 * @file
 * A JavaScript file that styles the page with bootstrap classes.
 *
 * @see sass/styles.scss for more info
 */
(function ($, Drupal, window, document, undefined) {
var glazedMenuState = '';

Drupal.behaviors.fullScreenSearch = {
    attach: function(context, settings) {
        function clearSearchForm() {
            $searchForm.toggleClass("invisible"),
            $('body').toggleClass("body--full-screen-search"),
            setTimeout(function() {
                $searchFormInput.val("")
            }, 350)
        }
        var $searchButton = $(".full-screen-search-button")
          , $searchForm = $(".full-screen-search-form")
          , $searchFormInput = $searchForm.find(".search-query")
          , escapeCode = 27;
        $searchButton.on("touchstart click", function(event) {
            event.preventDefault(),
            $searchForm.toggleClass("invisible"),
            $('body').toggleClass("body--full-screen-search"),
            $searchFormInput.focus()
        }),
        $searchForm.on("touchstart click", function($searchButton) {
            $($searchButton.target).hasClass("search-query") || clearSearchForm()
        }),
        $(document).keydown(function(event) {
            event.which === escapeCode && !$searchForm.hasClass("invisible") && clearSearchForm()
        })
    }
}

Drupal.behaviors.glazed = {
  attach: function(context, settings) {
    // Page Title Background Image Helper
    var bg_img = $('#page-title-full-width-container').attr('data-bg-img');
    if (bg_img) {
      $('<style>#page-title-full-width-container:after{background-image:url("'+ bg_img +'")}</style>').appendTo('head');
    }

    // Menu System
    var windowHeight = $(window).height();
    if ($('#glazed-main-menu .menu').length > 0) {
      glazedMenuGovernor(document);
    }

    // Helper classes
    $('.glazed-util-full-height', context).css('min-height', windowHeight);

    // User page
    $('.page-user .main-container', context).find('> .row > .col-sm-12')
        .once('glazed')
        .removeClass('col-sm-12')
        .addClass('col-sm-8 col-md-offset-2');

    // Main content layout
    $('.glazed-util-content-center-4-col .main-container', context).find('> .row > .col-sm-12')
        .once('glazed')
        .removeClass('col-sm-12')
        .addClass('col-sm-4 col-md-offset-4');

    $('.glazed-util-content-center-6-col .main-container', context).find('> .row > .col-sm-12')
        .once('glazed')
        .removeClass('col-sm-12')
        .addClass('col-sm-6 col-md-offset-3');

    $('.glazed-util-content-center-8-col .main-container', context).find('> .row > .col-sm-12')
        .once('glazed')
        .removeClass('col-sm-12')
        .addClass('col-sm-8 col-md-offset-2');

    $('.glazed-util-content-center-10-col .main-container', context).find('> .row > .col-sm-12')
        .once('glazed')
        .removeClass('col-sm-12')
        .addClass('col-sm-8 col-md-offset-1');

    // Messages, position absolutely when overlay header and no page title
    if (($('.wrap-messages').length > 0)
      && ($('.glazed-header--overlay').length > 0)
      && ($('#page-title').length == 0))
    $('.wrap-messages', context).css({
      'position' : 'absolute',
      'z-index' : '9999',
      'right' : '0',
    });

    // Breadcrumbs
    $('.breadcrumb a', context)
        .once('glazed')
        .after(' <span class="glazed-breadcrumb-spacer">/</span> ');

    // Sidebar nav blocks
    $('.region-sidebar-first .block .view ul, .region-sidebar-second .block .view ul', context)
        .once('glazed')
        .addClass('nav');
  }
};

// Create underscore debounce and throttle functions if they doesn't exist already
if(typeof _ != 'function'){
  window._ = {};
  window._.debounce = function(func, wait, immediate) {
    var timeout, result;

    var later = function(context, args) {
      timeout = null;
      if (args) result = func.apply(context, args);
    };

    var debounced = restArgs(function(args) {
      var callNow = immediate && !timeout;
      if (timeout) clearTimeout(timeout);
      if (callNow) {
        timeout = setTimeout(later, wait);
        result = func.apply(this, args);
      } else if (!immediate) {
        timeout = _.delay(later, wait, this, args);
      }

      return result;
    });

    debounced.cancel = function() {
      clearTimeout(timeout);
      timeout = null;
    };

    return debounced;
  };
  var restArgs = function(func, startIndex) {
    startIndex = startIndex == null ? func.length - 1 : +startIndex;
    return function() {
      var length = Math.max(arguments.length - startIndex, 0);
      var rest = Array(length);
      for (var index = 0; index < length; index++) {
        rest[index] = arguments[index + startIndex];
      }
      switch (startIndex) {
        case 0: return func.call(this, rest);
        case 1: return func.call(this, arguments[0], rest);
        case 2: return func.call(this, arguments[0], arguments[1], rest);
      }
      var args = Array(startIndex + 1);
      for (index = 0; index < startIndex; index++) {
        args[index] = arguments[index];
      }
      args[startIndex] = rest;
      return func.apply(this, args);
    };
  }
  _.delay = restArgs(function(func, wait, args) {
    return setTimeout(function() {
      return func.apply(null, args);
    }, wait);
  });

  window._.throttle = function (func, wait, options) {
    var context, args, result;
    var timeout = null;
    var previous = 0;
    if (!options) options = {};
    var later = function () {
      previous = options.leading === false ? 0 : _.now();
      timeout = null;
      result = func.apply(context, args);
      if (!timeout) context = args = null;
    };
    return function () {
      var now = _.now();
      if (!previous && options.leading === false) previous = now;
      var remaining = wait - (now - previous);
      context = this;
      args = arguments;
      if (remaining <= 0 || remaining > wait) {
        if (timeout) {
          clearTimeout(timeout);
          timeout = null;
        }
        previous = now;
        result = func.apply(context, args);
        if (!timeout) context = args = null;
      } else if (!timeout && options.trailing !== false) {
        timeout = setTimeout(later, remaining);
      }
      return result;
    };
  };
}

$(window).resize(_.debounce(function(){
    if ($('#glazed-main-menu .menu').length > 0) {
      glazedMenuGovernorBodyClass();
      glazedMenuGovernor(document);
    }
    if ($(window).width() > 768) {
      $('.glazed-main-menu').removeClass('glazed-main-menu--to-left');
    }
}, 50));

var navBreak = 'glazedNavBreakpoint' in window ? window.glazedNavBreakpoint : 1200;
if ($('.glazed-header--sticky').length > 0 && $(window).width() > navBreak) {
  var headerHeight = drupalSettings.glazedSettings.headerHeight;
  var headerScroll = drupalSettings.glazedSettings.headerOffset;
  var scroll = 0;

  if (headerHeight && headerScroll) {
    _.throttle($(window).scroll(function () {
      scroll = $(window).scrollTop();
      if (scroll >= headerScroll && scroll <= headerScroll * 2) {
        document.getElementsByClassName("wrap-containers")[0].style.cssText = "margin-top:" + +headerHeight + "px";
      } else if (scroll < headerScroll) {
        document.getElementsByClassName("wrap-containers")[0].style.cssText = "margin-top:0";
      }
    }), 100);
  }
}

if ((drupalSettings.glazedSettings.headerSideDirection === 'right') && $(window).width() <= 768){
  $('.glazed-main-menu').addClass('glazed-main-menu--to-left');
} else {
  $('.glazed-main-menu').removeClass('glazed-main-menu--to-left');
}

function glazedMenuGovernor(context) {
  // Bootstrap dropdown multi-column smart menu
  var navBreak = 1200;
  if('glazedNavBreakpoint' in window) {
    navBreak = window.glazedNavBreakpoint;
  }
  if (($('.body--glazed-header-side').length == 0) && $(window).width() > navBreak) {
    if (glazedMenuState == 'top') {
      return false;
    }
    $('.html--glazed-nav-mobile--open').removeClass('html--glazed-nav-mobile--open');
    $('.glazed-header--side').removeClass('glazed-header--side').addClass('glazed-header--top');
    $('#glazed-main-menu .menu__breadcrumbs').remove();
    $('.menu__level').removeClass('menu__level').css('margin-top', 0).css('height', 'auto');
    $('.menu__item').removeClass('menu__item');
    $('[data-submenu]').removeAttr('data-submenu');
    $('[data-menu]').removeAttr('data-menu');

    var bodyWidth = $('body').innerWidth();
    var margin = 10;
    $('#glazed-main-menu .menu .dropdown-menu', context)
      .each(function() {
        var width = $(this).width();
        if ($(this).find('.glazed-megamenu__heading').length > 0) {
          var columns = $(this).find('.glazed-megamenu__heading').length;
        }
        else {
          var columns = Math.floor($(this).find('li').length / 8) + 1;
        }
        if (columns > 2) {
          $(this).css({
              'width' : '100%', // Full Width Mega Menu
              'left:' : '0',
          }).parent().css({
              'position' : 'static',
          }).find('.dropdown-menu >li').css({
              'width' : 100 / columns + '%',
          });
        }
        else {
          var $this = $(this);
          if (columns > 1) {
            // Accounts for 1px border.
            $this
              .css('min-width', width * columns + 2)
              .find('>li').css('width', width)
          }
          // Workaround for drop down overlapping.
          // See https://github.com/twbs/bootstrap/issues/13477.
          var $topLevelItem = $this.parent();
          // Set timeout to let the rendering threads catch up.
          setTimeout(function() {
            var delta = Math.round(bodyWidth - $topLevelItem.offset().left - $this.outerWidth() - margin);
            // Only fix items that went out of screen.
            if (delta < 0) {
              $this.css('left', delta + 'px');
            }
          }, 0)
        }
      });
    glazedMenuState = 'top';
    // Hit Detection for Header
    if (($('.tabs--primary').length > 0) && ($('#navbar').length > 0)) {
      var tabsRect = $('.tabs--primary')[0].getBoundingClientRect();
      if (($('.glazed-header--navbar-pull-down').length > 0) && ($('#navbar .container-col').length > 0)) {
        var pullDownRect = $('#navbar .container-col')[0].getBoundingClientRect();
        if (glazedHit(pullDownRect, tabsRect)) {
          $('.tabs--primary').css('margin-top', pullDownRect.bottom - tabsRect.top + 6);
        }
      }
      else {
        var navbarRect = $('#navbar')[0].getBoundingClientRect();
        if (glazedHit(navbarRect, tabsRect)) {
          $('.tabs--primary').css('margin-top', navbarRect.bottom - tabsRect.top + 6);
        }

      }
    }
    if (($('#secondary-header').length > 0) && ($('#navbar.glazed-header--overlay').length > 0)) {
      var secHeaderRect = $('#secondary-header')[0].getBoundingClientRect();
      if (glazedHit($('#navbar.glazed-header--overlay')[0].getBoundingClientRect(), secHeaderRect)) {
        $('#navbar.glazed-header--overlay').css('top', secHeaderRect.bottom);
      }
    }
  }
  // Mobile Menu with sliding panels and breadcrumb
  // @dsee glazed-mobile-nav.js
  else {
    if (glazedMenuState == 'side') {
      return false;
    }
    // Temporary hiding while settings up @see #290
    $('#glazed-main-menu').hide();
    // Set up classes
    $('.glazed-header--top').removeClass('glazed-header--top').addClass('glazed-header--side');
    // Remove split-megamenu columns
    $('#glazed-main-menu .menu .dropdown-menu, #glazed-main-menu .menu .dropdown-menu li').removeAttr('style');
    $('#glazed-main-menu .menu').addClass('menu__level');
    $('#glazed-main-menu .menu .dropdown-menu').addClass('menu__level');
    $('#glazed-main-menu .menu .glazed-megamenu').addClass('menu__level');
    $('#glazed-main-menu .menu a').addClass('menu__link');
    $('#glazed-main-menu .menu li').addClass('menu__item');
    // Set up data attributes
    $('#glazed-main-menu .menu a.dropdown-toggle').each(function( index ) {
        $(this).attr('data-submenu', $(this).text())
          .next().attr('data-menu', $(this).text());
      });
    $('#glazed-main-menu .menu a.glazed-megamenu__heading').each(function( index ) {
        $(this).attr('data-submenu', $(this).text())
          .next().attr('data-menu', $(this).text());
      });

      var bc = ($('#glazed-main-menu .menu .dropdown-menu').length > 0);
      var menuEl = document.getElementById('glazed-main-menu'),
          mlmenu = new MLMenu(menuEl, {
              breadcrumbsCtrl : bc, // show breadcrumbs
              initialBreadcrumb : 'menu', // initial breadcrumb text
              backCtrl : false, // show back button
              itemsDelayInterval : 10, // delay between each menu item sliding animation
              // onItemClick: loadDummyData // callback: item that doesn´t have a submenu gets clicked - onItemClick([event], [inner HTML of the clicked item])
          });

      // Close/open menu function
      var closeMenu = function () {
        $('#glazed-menu-toggle').toggleClass( 'navbar-toggle--active' );
        $(menuEl).toggleClass( 'menu--open' );
        $('html').toggleClass( 'html--glazed-nav-mobile--open' );
      };

      // mobile menu toggle
      $('#glazed-menu-toggle').once('glazedMenuToggle').click(function() {
        closeMenu();
      });
      $('#glazed-main-menu').show();

      // Close menu with click on anchor link
      $('.menu__link').click(function () {
        if (!$(this).attr('data-submenu')) {
          closeMenu();
        }
      });

      // See if logo  or block content overlaps menu and apply correction
      if ($('.wrap-branding').length > 0) {
        var brandingBottom = $('.wrap-branding')[0].getBoundingClientRect().bottom;
      }
      else {
        var brandingBottom = 0;
      }
      var $lastBlock = $('#glazed-main-menu .block:not(.block-menu)').last();

      // Show menu after completing setup
      // See if blocks overlap menu and apply correction
      if (($('.body--glazed-header-side').length > 0) && ($(window).width() > navBreak) && ($lastBlock.length > 0) && (brandingBottom > 0)) {
        $('#glazed-main-menu').css('padding-top', brandingBottom + 40);
      }
      if (($lastBlock.length > 0)) {
        var lastBlockBottom = $lastBlock[0].getBoundingClientRect().bottom;
        $('.menu__breadcrumbs').css('top', lastBlockBottom + 20);
        $('.menu__level').css('top', lastBlockBottom + 40);
        var offset = 40 + lastBlockBottom;
        $('.glazed-header--side .menu__level').css('height', 'calc(100vh - ' + offset + 'px)');
      }
      else if (($('.body--glazed-header-side').length > 0) && ($('.wrap-branding').length > 0) && (brandingBottom > 120)) {
        $('.menu__breadcrumbs').css('top', brandingBottom + 20);
        $('.menu__level').css('top', brandingBottom + 40);
        var offset = 40 + brandingBottom;
        $('.glazed-header--side .menu__level').css('height', 'calc(100vh - ' + offset + 'px)');
      }
    glazedMenuState = 'side';
  }
}

// Fixed header on mobile on tablet
var headerHeight = drupalSettings.glazedSettings.headerMobileHeight;
var headerFixed = drupalSettings.glazedSettings.headerMobileFixed;
var navBreak = 'glazedNavBreakpoint' in window ? window.glazedNavBreakpoint : 1200;

if (headerFixed && $('.glazed-header').length > 0 && $(window).width() <= navBreak) {
  if ($('#toolbar-bar').length > 0) {
    $('#navbar').addClass('header-mobile-admin-fixed');
  }
  if ($(window).width() >= 975) {
    $('#navbar').addClass('header-mobile-admin-fixed-active');
  } else {
    $('#navbar').removeClass('header-mobile-admin-fixed-active');
  }
  $('.glazed-boxed-container').css('overflow', 'hidden');
  $('#toolbar-bar').addClass('header-mobile-fixed');
  $('#navbar').addClass('header-mobile-fixed');
  $('#secondary-header').css('margin-top', +headerHeight);
}

function glazedMenuGovernorBodyClass() {
  var navBreak = 1200;
  if('glazedNavBreakpoint' in window) {
    navBreak = window.glazedNavBreakpoint;
  }
  if ($(window).width() > navBreak) {
    $('.body--glazed-nav-mobile').removeClass('body--glazed-nav-mobile').addClass('body--glazed-nav-desktop');
  }
  else {
    $('.body--glazed-nav-desktop').removeClass('body--glazed-nav-desktop').addClass('body--glazed-nav-mobile');
  }
}

// Accepts 2 getBoundingClientRect objects
function glazedHit(rect1, rect2) {
  return !(rect1.right < rect2.left ||
              rect1.left > rect2.right ||
              rect1.bottom < rect2.top ||
              rect1.top > rect2.bottom);
}

})(jQuery, Drupal, this, this.document);
