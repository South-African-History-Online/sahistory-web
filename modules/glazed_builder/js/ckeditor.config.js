(function ($, Drupal, drupalSettings, CKEDITOR) {
  CKEDITOR.editorConfig = function(config) {
    // Don't add spaces to empty blocks
    config.fillEmptyBlocks = false;
    // Disabling content filtering.
    config.allowedContent = true;
    // Prevent wrapping inline content in paragraphs
    config.autoParagraph = false;
    // Don't move about our Glazed Builder stylesheet link tags
    config.protectedSource.push(/<link.*?>/gi); // Don't move about our Glazed Builder stylesheet link tags
  };
}(jQuery, Drupal, drupalSettings, CKEDITOR));
