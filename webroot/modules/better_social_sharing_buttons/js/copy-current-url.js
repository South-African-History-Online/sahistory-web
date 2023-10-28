/**
 * Copy the current page url to clipboard when clicking on the .btnCopy button.
 * Based on
 * https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript/30810322#30810322
 */

(function ($) {
  'use strict';

  // Selector of the element that copies link when clicked.
  Drupal.behaviors.copyButtonElements = {
    attach: function (context) {
      var $copyButton = $('.btnCopy');
      $copyButton.once('copy-current-url').each(function () {
        // Adding click event on each anchor element.
        $(this).on('click', function (e) {
          e.preventDefault();
          var popupElements = $('.social-sharing-buttons__popup');
          Drupal.copyTextToClipboard(window.location.href, popupElements);
        });
      });
    }
  };

  // Function to copy current url to clipboard.
  // Shows a popupmessage on screen if url was copied successful.
  Drupal.copyTextToClipboard = function (text, popupElements) {
    if (!navigator.clipboard) {
      Drupal.fallbackCopyTextToClipboard(text, popupElements);
      return;
    }

    navigator.clipboard.writeText(text, popupElements)
      .then(function () {
        Drupal.showCopiedMessage(popupElements);
      }, function (err) {
        console.error('Error copying current url to clipboard: ', err);
      });
  };

  // Fallback copy functionality using using older document.execCommand('copy') for when the normal clipboard
  // functionality (navigator.clipboard) does not work. This generates a textarea with url as content and the copies that
  // content using the document.execCommand('copy') command.
  Drupal.fallbackCopyTextToClipboard = function (text, popupElements) {

    var $inputURL = $("<input>");
    $("body").append($inputURL);
    $inputURL.val(window.location.href).select();

    try {
      document.execCommand("copy");
      Drupal.showCopiedMessage(popupElements);
    }
    catch (err) {
      console.error('Error copying current url to clipboard', err);
    }

    $inputURL.remove();
  };

  // Show a popup if the current url was successfully copied.
  Drupal.showCopiedMessage = function (popupElements) {
    var visibleClass = 'visible',
      $popupElements = popupElements;

    $popupElements.each(function () {
      $(this).addClass(visibleClass);
    });

    setTimeout(function () {
      $popupElements.each(function () {
        $(this).removeClass(visibleClass);
      });
    }, 4000);
  };

})(jQuery, Drupal);