/*global jQuery, Drupal, drupalSettings, window*/
/*jslint white:true, multivar, this, browser:true*/

(function($, Drupal, drupalSettings, window) {

  "use strict";

  // Set elements that Glazed Builder will globally recognise
  window.glazed_editable = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'img:not(.not-editable)', 'a:not(.not-editable)', 'i:not(.not-editable)'];
  window.glazed_styleable = [];
  window.glazed_textareas = [];
  window.glazed_formats = [];

  /**
   * Hide the resize image controls
   *
   * @param jQuery input
   *   The input for which the resize image controls should be hidden
   */
  function hideImageStyleControls(input) {
    input.siblings("label:first, .chosen-container:first").hide();
  }

  /**
   * Create an array of image URLs from the image input
   *
   * @var jQuery imageInput
   *   The image input from which the URLs should be extracted
   * @var string delimiter
   *   The delimiter used between filenames stored in the input
   *
   * @return array
   *   An array of image names extracted from the image input
   */
  function getUrlsFromInput(input, delimiter) {
    if (delimiter) {
      return input.val().split(delimiter).filter(function(el) {
        return Boolean(el.length);
      });
    }
    else {
      return [input.val().trim()];
    }
  }

  /**
   * Show the resize image controls
   *
   * @param jQuery input
   *   The input for which the resize image controls should be hidden
   */
  function showImageStyleControls(input) {
    input.siblings("label:first, .chosen-container:first").show();
  }

  /**
   * Create the file upload button users will click to upload an image
   *
   * @var jQuery input
   *   The input used as a reference for inserting the button into the DOM
   */
  function createFileUploadButton(input) {
    // Insert the button into the DOM, and set the button to programatically click
    // the file upload element when the button is created, thereby initiating the
    // browser's file selection dialog.
    input.parent().prepend($("<button/>",  {class:"ac-select-image btn btn-default"}).text(Drupal.t("Select Image")).click(function(e) {
      e.preventDefault();

      // Trigger file upload
      $(this).siblings(".image_upload:first").click();
    }));
  }

  /**
   * Create the file upload button users will click to upload an image
   *
   * @var jQuery input
   *   The input used as a reference for inserting the button into the DOM
   */
  function createEntityBrowserButton(input) {
    // Insert the button into the DOM, and set the button to programatically click
    // the file upload element when the button is created, thereby initiating the
    // browser's file selection dialog.
    input.parent().prepend($("<button/>",  {class:"ac-select-image btn btn-default"}).text(Drupal.t("Select Image")).click(function(e) {
      e.preventDefault();
      // Trigger Entity Browser Selection
      var mediaBrowser = drupalSettings.glazedBuilder.mediaBrowser;
      if (input.hasClass('glazed-builder-multi-image-input')) {
        var eb = 'glazedBuilderMulti';
      }
      else {
        var eb = 'glazedBuilderSingle';
      }
      input.attr('data-uuid', eb);
      // Create Bootstrap Modal
      $('#az-media-modal').remove();
      var header = '<div class="modal-header"><span class="close" data-dismiss="modal" aria-hidden="true">&times;</span><h4 class="modal-title">' + '<img src="' + drupalSettings.glazedBuilder.glazedBaseUrl +
        'images/glazed-logo-white.svg">' + '</h4></div>';
      var entityBrowser = '<iframe data-uuid="' + eb + '" src="' + drupalSettings.path.baseUrl + 'entity-browser/modal/' + mediaBrowser + '?uuid=' + eb + '" frameborder="0"></iframe>';
      var $media_modal = $('<div id="az-media-modal" class="modal glazed" style="display:none"><div class="modal-dialog modal-lg"><div class="modal-content">' + header + '<div class="modal-body">' + entityBrowser + '</div></div></div></div>');
      $('body').prepend($media_modal);
      $media_modal.modal('show');
    }));
  }

  /**
   * Get the file name from a URL
   *
   * @var string url
   *   The URL from which the file name should be extracted
   *
   * @return string
   *    The name of the file
   */
  function getFileNameFromUrl(url) {
    var parts = url.split("/");

    return parts[parts.length - 1];
  }

  /**
   * Alter a URL to link to the image style for that URL
   *
   * @param string url
   *   The URL to be altered
   * @param string imageStyle
   *   The image style that should be applied to that URL. If this is equal to 'original',
   *   The original image URL will be returned, instead of a URL with an image style path
   *
   * @return string
   *   The image style URL for the original image
   */
  function getImageStyleUrl(url, imageStyle) {
    var filesUrl = drupalSettings.glazedBuilder.publicFilesFolder;

    // First check if we're dealing with a local image in public files storage
    if (url.indexOf(filesUrl) !== -1) {
      // Check if we're dealing with a non-image style URL
      if (url.indexOf("/public/") === -1) {
        // Insert the image style into the URL
        return url.replace(filesUrl, filesUrl + "styles/" + imageStyle + "/public/");
      }
      else {
        // If the image style is 'original', then return non-image style URL
      if (imageStyle === "original") {
          return url.replace(/styles\/[^\/]+\/public\//, "");
        }
        // Otherwise swap out the current image style with the new one.
        else {
          return url.replace(/\/styles\/[^\/]+/, "/styles/" + imageStyle);
        }
      }
    }
    return url;
  }

  /**
   * Click handler for the remove button on thumbnails
   */
  function thumbnailCloseButtonClickHandler(e) {
    e.preventDefault();

    var thumbnailContainer, imageList, selectElement;

    thumbnailContainer = $(this).parent().parent();
    imageList = thumbnailContainer.parent();
    selectElement = thumbnailContainer.parent().siblings("select:first");
    // Unset the currently selected image style
    selectElement.find("option[selected='selected']").removeAttr("selected");
    // Set the new image style
    selectElement.find("option[value='original']").attr("selected", "selected").trigger("chosen:updated");
    // Remove the thumbnail
    thumbnailContainer.remove();

    if (!imageList.children("li:first").length) {
      hideImageStyleControls(imageList.siblings(".form-control:first"));
    }

    sortFilenames(imageList, ',');

  }

  /**
   * Sort the filenames in the image input according to the current order
   * of elements in the list.
   *
   * @var jQuery imageList
   *   The <ul> element containing the images
   * @var string delimiter
   *   The delimiter used between filenames stored in the input
   */
  function sortFilenames(imageList, delimiter) {
    var imageInput, urls, fileNames, sorted;

    imageInput = imageList.siblings(".form-control:first");
    urls = getUrlsFromInput(imageInput, delimiter);
    fileNames = [];
    imageList.children("li").each(function() {
      var filename = $(this).children(":first").attr("data-filename");
      if (filename && filename.length) {
        fileNames.push(filename);
      }
    });

    sorted = [];
    $.each(fileNames, function(index) {
      $.each(urls, function(index2) {
        if (urls[index2].endsWith(fileNames[index])) {
          sorted.push(urls[index2]);
          return false;
        }
      });
    });

    imageInput.val(sorted.join(delimiter));
  }

  /**
   * Create a thumbnail from a given filename, and insert it into the DOM
   *
   * @var string fileName
   *   The name of the file to be inserted
   * @var jQuery input
   *   The input used as a reference for inserting the button into the DOM
   * @var string delimiter
   *   The delimiter used between filenames stored in the input
   */
  function insertImageThumbnail(fileUrl, input, delimiter) {
    var imageContainer, closeButton, image, imageList;

    // Create a container for the thumbnail
    imageContainer = $("<div/>", {class:"image-preview", "data-filename":getFileNameFromUrl(fileUrl)});
    // Create the image element
    image = $("<img/>", {src:getImageStyleUrl(fileUrl, "thumbnail")});
    // Create the remove button
    closeButton = $("<a/>", {class:"glyphicon glyphicon-remove", href:"#"}).click(thumbnailCloseButtonClickHandler);

    // Add the image and close button to the container
    imageContainer.append(image).append(closeButton);

    // Retrieve list of images
    imageList = input.siblings(".preview:first");
    // If the list doesn't exist, it needs to be created
    if (!imageList.length) {
      imageList = $("<ul/>", {class:"preview ui-sortable"}).insertAfter(input.siblings("button:first")).sortable({
        stop:function() {
          sortFilenames($(this), delimiter);
        }
      });
    }

    // If multiple images are not allowed, any existing thumbnails are first removed.
    if (!Boolean(delimiter)) {
      imageList.empty();
    }

    // insert the container into the list
    $("<li/>", {class:"added"}).append(imageContainer).appendTo(imageList);

    showImageStyleControls(input);
  }

  /**
   * Create the file upload element used to upload an image. When an image
   * has been uploaded, the URL of the file is inserted into the given input. If multiple
   * files have been uploaded, the URLs are separated by the given delimiter
   *
   * @var jQuery input
   *   The input used as a reference for inserting the element into the DOM
   * @var string delimiter
   *   The delimiter used between filenames stored in the input
   */
  function createFileUploadElement(input, delimiter) {
    // Set up the input that is used to handle the image uploads. This is hidden
    // to the user, but used for transferring the image in the background. When clicked
    // it will handle the upload using AJAX.
    $("<input/>", {type:"file", class:"image_upload", "data-url":drupalSettings.glazedBuilder.fileUploadUrl}).insertBefore(input).fileupload({
      dataType:"json",
      acceptFileTypes:/(\.|\/)(gif|jpe?g|png)$/i,
      done:function(e, data) {
        var imageStyle = input.siblings("select:first").val();

        // This line of code does nothing, but is used
        // to suppress a jslint validation error
        e = e;

        // Loop through the returned files and insert them into the field that the front end will
        // use to insert them into the page
        $.each(data.result.files, function (index) {
          var url;
          // Set the URL to be added, based on the image style selected
          if (imageStyle === "original") {
            url = data.result.files[index];
          }
          else {
            url = getImageStyleUrl(data.result.files[index], imageStyle);
          }

          // Insert filename into input
          if (delimiter) {
            var currentImages = getUrlsFromInput(input, delimiter);

            currentImages.push(url);
            input.val(currentImages.join(delimiter));
	      }
	      else {
            input.val(url);
          }

          // Create a thumbnail for the uploaded image
          insertImageThumbnail(data.result.files[index], input, delimiter);
        });
      }
    });
  }

  /**
   * Change handler for the image style select element
   *
   * @var jQuery selectElement
   *   The select element for image styles
   * @var string delimiter
   *   The delimiter used between URLs in the input
   */
  function imageStyleChangeHandler(selectElement, delimiter) {
    var imageStyle, imageInput, currentImages, newImages, imageStyleUrl;

    // Find the selected option and act on it
    imageStyle = selectElement.val();
    // Get the image input containing the URL of the image
    imageInput = selectElement.siblings(".form-control:first");
    // If a delimiter has been provided, it means multiple images are allowed,
    // so each image needs the image style applied
    if (delimiter) {
      // Create an array of the currently entered images
      currentImages = getUrlsFromInput(imageInput, delimiter);

      // Create an array to hold the images with the new image style URLs
      newImages = [];
      // Loop through each of the current images, creating an array with the new image URLs
      $.each(currentImages, function(index) {
        newImages.push(getImageStyleUrl(currentImages[index], imageStyle));
      });

      // Insert the new image URLs into the image field
      imageInput.val(newImages.join(delimiter));
    }
    else {
      // Get the URL of the image with the applied image stye
      imageStyleUrl = getImageStyleUrl(imageInput.val(), imageStyle);
      // Insert the new uRL into the image input
      imageInput.val(imageStyleUrl);
    }
  }

  /**
   * Create the select element users will use to select an image style
   *
   * @var jQuery input
   *   The input used as a reference for inserting the select element into the DOM
   * @var string delimiter
   *   The delimiter used between URLs in the input
   */
  function createImageStyleInput(input, delimiter) {
    var label, imageStyleSelect, matches;

    // Create the select element used for selecting an image style
    imageStyleSelect = $('<select class="glazed-builder-image-styles"/>').change(function () {
      imageStyleChangeHandler($(this), delimiter);
    });

    // Add an <option> tag for each image style to the image style select element
    $.each(drupalSettings.glazedBuilder.imageStyles, function(key) {
      imageStyleSelect.append($("<option/>", {value:key}).text(drupalSettings.glazedBuilder.imageStyles[key]));
    });

    // When editing an existing image, the image input will contain a URL. This URL
    // is parsed to see if it has an image style applied to it.
    matches = input.val().match(/styles\/([^\/]+)\/public/);
    // If the URL has an image style applied to it, that image style is set as the current selection
    if(matches && matches[1]) {
      imageStyleSelect.find("option[value='" + matches[1] + "']").attr("selected", "selected");
    }

    // Append the newly created elements to the page
    input.before(imageStyleSelect).prepend(label);

    // Use jQuery.chosen() to make a cleaner select element for the image styles.
    imageStyleSelect.chosen({
      search_contains: true,
      allow_single_deselect: true
    });

    hideImageStyleControls(input);
  }

  /**
   * When an image is being edited, a URL will exist in the input. This
   * function creates a thumbnail from that URL.
   *
   * @var jQuery input
   *   The input from which the URL will be retrieved
   * @var string delimiter
   *   The delimiter used between URLs in the input
   */
  function createThumbailFromDefault(input, delimiter) {
    var currentImages;
    // If a value exists, thumbnails need to be created
    if (input.val().length) {
      // Get the list of images that exist in the input
      currentImages = getUrlsFromInput(input, delimiter);

      // Loop through the images creating thumbnails for each image
      $.each(currentImages, function(index) {
        insertImageThumbnail(currentImages[index], input, delimiter);
      });

      // Show the image controls, since there has been an image inserted
      showImageStyleControls(input);
    }
  }

  /**
   * This function is used to launch the code in this script, and is
   * called by external scripts.
   *
   * @var Dom Element input
   *   The input into which URLs should be inserted. The URLs will then
   *   become images in the DOM when the dialog is saved
   * @var string delimiter
   *   The delimiter used between URLs in the input
   */
  window.images_select = function (input, delimiter) {
    var $input = $(input);
    $input.css("display", "block").wrap($("<div/>", {class:"ac-select-image"}));

    if (drupalSettings.glazedBuilder.mediaBrowser.length > 0) {
      createEntityBrowserButton($input);
    }
    else {
      createFileUploadElement($input, delimiter);
      createFileUploadButton($input);
    }
    createImageStyleInput($input, delimiter);
    createThumbailFromDefault($input, delimiter);

    $input.change({input: $input, delimiter: delimiter},  function(event) {
      $input.siblings(".preview:first").empty();
      createThumbailFromDefault(input, delimiter);
    });
  };

}(jQuery, Drupal, drupalSettings, window));
