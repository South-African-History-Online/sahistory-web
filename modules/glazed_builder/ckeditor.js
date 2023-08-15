
 /**
  * Glazed Builder Asset Loading Functions
  */
  window.glazed_builder_set_ckeditor_config = function(mode) {
    CKEDITOR.disableAutoInline = true;
    // Don't add spaces to empty blocks
    CKEDITOR.config.fillEmptyBlocks = false;
    // Disabling content filtering.
    CKEDITOR.config.allowedContent = true;
    // Prevent wrapping inline content in paragraphs
    CKEDITOR.config.autoParagraph = false;

    // Theme integration
    CKEDITOR.config.contentsCss = ['//cdn.jsdelivr.net/bootstrap/3.3.7/css/bootstrap.min.css'];
    if (typeof drupalSettings.glazed.glazedPath.length != "undefined") {
      CKEDITOR.config.contentsCss.push(drupalSettings.basePath + drupalSettings.glazed.glazedPath +
        'css/glazed.css');
    }

    // Styles dropdown
    if (typeof drupalSettings.glazed_builder.cke_stylesset != "undefined") {
      CKEDITOR.config.stylesSet = drupalSettings.glazed_builder.cke_stylesset;
    }
    else {
      CKEDITOR.config.stylesSet = [{
        name: 'Lead',
        element: 'p',
        attributes: {
          'class': 'lead'
        }
      }, {
        name: 'Muted',
        element: 'p',
        attributes: {
          'class': 'text-muted'
        }
      }, {
        name: 'Highlighted',
        element: 'mark'
      }, {
        name: 'Small',
        element: 'small'
      }, {
        name: 'Button Primary',
        element: 'div',
        attributes: {
          'class': 'btn btn-primary'
        }
      }, {
        name: 'Button Default',
        element: 'div',
        attributes: {
          'class': 'btn btn-default'
        }
      }, ];
    }
    // Fonts dropdown
    if (typeof drupalSettings.glazed_builder.cke_stylesset != "undefined") {
      CKEDITOR.config.font_names = drupalSettings.glazed_builder.cke_fonts;
    }
    else {
      CKEDITOR.config.font_names = "Arial/Arial, Helvetica, sans-serif;Georgia/Georgia, serif;Times New Roman/Times New Roman, Times, serif;Verdana/Verdana, Geneva, sans-serif";
    }

    CKEDITOR.config.fontSize_sizes = '8/8px;9/9px;10/10px;11/11px;12/12px;14/14px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;48/48px;60/60px;72/72px;90/90px;117/117px;144/144px';
    CKEDITOR.config.protectedSource.push(/<link.*?>/gi);

    var palette = [];
    for (var name in window.sooperthemes_theme_palette) {
      palette.push(window.sooperthemes_theme_palette[name].substring(1));
    }

    // Only once apply this settings
    var palletsString = palette.join(',') + ',';
    if ((CKEDITOR.config.hasOwnProperty('colorButton_colors')) && (CKEDITOR.config.colorButton_colors.indexOf(palletsString)) < 0) {
      CKEDITOR.config.colorButton_colors = palletsString + CKEDITOR.config.colorButton_colors;
    }

    if (mode == 'inline') {
      if ('profile' in window.drupalSettings.glazed_builder) {
        CKEDITOR.config.toolbar = window.drupalSettings.glazed_builder.profile.ck_config.inline;
      }
      else {
        CKEDITOR.config.toolbar = [{
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'RemoveFormat']
        }, {
          name: 'colors',
          items: ['TextColor']
        }, {
          name: 'styles',
          items: ['Format', 'Styles', 'FontSize']
        }, {
          name: 'paragraph',
          items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'BulletedList',
            'NumberedList'
          ]
        }, {
          name: 'links',
          items: ['Link', 'Unlink']
        }, {
          name: 'insert',
          items: ['Image', 'Table']
        }, {
          name: 'clipboard',
          items: ['Undo', 'Redo']
        }, ];
      }
    }
    else {
      if ('profile' in drupalSettings.glazed_builder) {
        CKEDITOR.config.toolbar = drupalSettings.glazed_builder.profile.ck_config.modal;
      }
      else {
        CKEDITOR.config.toolbar = [{
          name: 'basicstyles',
          items: ['Bold', 'Italic', 'Underline', 'Strike', 'Superscript', 'Subscript', 'RemoveFormat']
        }, {
          name: 'paragraph',
          items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'BulletedList',
             'Outdent', 'Indent', 'Blockquote', 'CreateDiv'
          ]
        }, {
          name: 'clipboard',
          items: ['Undo', 'Redo', 'PasteText', 'PasteFromWord']
        }, {
          name: 'links',
          items: ['Link', 'Unlink']
        }, {
          name: 'insert',
          items: ['Image', 'HorizontalRule', 'SpecialChar', 'Table', 'Templates']
        }, {
          name: 'colors',
          items: ['TextColor']
        }, {
          name: 'document',
          items: ['Source']
        }, {
          name: 'tools',
          items: ['ShowBlocks', 'Maximize']
        }, {
          name: 'styles',
          items: ['Format', 'Styles', 'FontSize']
        }, {
          name: 'editing',
          items: ['Scayt']
        }, ];
      }
    }

  }
