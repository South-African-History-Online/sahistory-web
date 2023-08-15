Imce
http://drupal.org/project/imce
====================================


DESCRIPTION
-----------
Imce is an ajax based file manager that supports personal folders.


INSTALLATION
-----------
1. Copy imce directory into your modules(sites/all/modules) directory.
2. Enable the module at /admin/modules.
3. Manage configuration profiles at /admin/config/media/imce.
4. Access your files at /imce


CKEDITOR INTEGRATION
-----------
1. Go to /admin/config/content/formats to edit a text format that uses CKEditor.
2. Enable CKEditor image button without image uploads.
Image uploads must be disabled in order for IMCE link appear in the image dialog.
There is also an image button provided by Imce but it can't be used for editing existing images.


BUEDITOR INTEGRATION
-----------
1. Edit your editor at /admin/config/content/bueditor.
2. Select Imce File Manager as the File browser under Settings.


FILE/IMAGE FIELD INTEGRATION
-----------
1. Go to form settings of your content type. Ex: /admin/structure/types/manage/article/form-display
2. Edit widget settings of a file/image field.
3. Check the box saying "Allow users to select files from Imce File Manager for this field." and save.
4. You should now see the "Open File Browser" link above the upload widget in the content form.
