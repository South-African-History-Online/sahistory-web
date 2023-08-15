<?php
/**
 * @file
 * Template file for the theming Popup announcement overlay
 *
 * Available variables:
 * - $announcement_text: A string containing the announcement text.
 */
?>
<div style="display:none" id="popup-announcement-overlay"></div>
<div style="display:none" id="popup-announcement-wrap">
  <div id="popup-announcement">
    <?php print $announcement_text; ?>
  </div>
  <div id="popup-announcement-close"></div>
</div>
