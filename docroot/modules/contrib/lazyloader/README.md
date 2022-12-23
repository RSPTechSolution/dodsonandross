CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This is a small helper module which will automatically lazyload all images for
sites with multiple images, which will make the site load faster.

All images will only load when it's visible to the browser window.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/lazyloader

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/lazyloader


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Image Lazyloader module as you would normally install a
   contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Media > Image Lazyloader to
       configure.

Available Settings:
    1. Enable/Disable
    2. Distance - image distance from the viewable browser window before the
       actual image loads
    3. Placeholder Image - stand-in image
    4. Loader Icon - animating icon (shamelessly borrowed from Ajaxblocks)
    5. Excluded Pages - page paths to be excluded from image lazyload

For other images:
You can also manually lazyload your other images not processed by Drupal image
module by formatting your img markup to this:

Attributes:
    1. src = path to a placeholder image
    2. data-src = path to actual image
    3. width = add width for best result
    4. height = add height for best result
    5. Add a container block

Example:

```
<div class="image-container">
  <img src"/sites/default/files/image_placeholder.gif" data-src="/sites/default/files/actual_image.jpg" alt="Image" />
</div>
```

MAINTAINERS
-----------

 * Chris Jansen (legolasbo) - https://www.drupal.org/u/legolasbo
 * Daniel Honrade (danielhonrade) - https://www.drupal.org/u/danielhonrade
 * Anton Kuzmenko (qzmenko) - https://www.drupal.org/u/qzmenko

Sponsors

 * Promet Source - https://www.prometsource.com
 * Daniel Honrade - http://www.danielhonrade.com
