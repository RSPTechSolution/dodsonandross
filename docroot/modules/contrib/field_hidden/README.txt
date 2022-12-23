(Drupal Field Hidden module)

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * How to use
 * Differences vs. Drupal 7
 * Requirements
 * Installation and uninstallation

INTRODUCTION
------------

Field Hidden defines HTML input[type='hidden'] element widgets for these core
field types:
 * decimal, float, integer
 * text, long text

HOW TO USE
---------

Select the 'Hidden field' widget for a field in the entity type's 'Manage form
display' dialogue.
The 'Hidden field' widget is not the same as selecting '- Hidden - '; the
latter excludes the field entirely from forms (leaving the field un-editable).
 
DIFFERENCES VS. DRUPAL 7
------------------------

D8 Field Hidden only defines widgets, it doesn't define field types.

REQUIREMENTS
------------

 * Drupal 8.x
 
INSTALLATION AND UNINSTALLATION
-------------------------------

Ordinary installation and uninstallation.

Uninstalling while some field instances still use the 'Hidden field' widget
will most likely result in errors when adding/editing their entities.
