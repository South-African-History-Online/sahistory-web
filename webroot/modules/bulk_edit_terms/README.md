CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Bulk Edit Terms module allows the user to bulk update any entity reference
field that is found on any of the selected nodes. It does it using Drupal 8
native actions.

 * For a full description of the module visit:
   https://www.drupal.org/project/bulk_edit_terms

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/bulk_edit_terms


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Bulk Edit Terms module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Content and select the nodes in which the
       terms need to be updated and in the 'Action' dropdown select 'Update
       terms' from the dropdown and select 'Apply'.
    3. A form that contains fields that appear in at least one of the selected
       nodes will appear. The user needs to populate the values as desired (or
       leave blank if you don't want to change a field). Update terms.
    4. It will apply those values to all applicable nodes (ie: only if they have
       the field). For multi-value term fields the module will add on top of the
       existing values. For unique value fields it will replace the existing
       value with the selected one.


MAINTAINERS
-----------

 * Fran Garcia-Linares (fjgarlin) - https://www.drupal.org/u/fjgarlin
 * Mustapha Ben Ellafi (benellefimostfa) - https://www.drupal.org/u/benellefimostfa

Supporting organization:

 * Amazee Labs - https://www.drupal.org/amazee-labs
