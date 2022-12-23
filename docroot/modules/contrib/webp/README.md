# WebP
Creates a WebP copy of image style derivatives to decrease loading times.

## Description
Whenever an image style derivative is created this module will also create
a WebP copy of the derivative to be served to supporting browsers.

## Requirements
This module requires one of the the following PHP extension:

 * GD - http://php.net/manual/en/book.image.php
 * ImageMagick with webp - https://imagemagick.org/script/webp.php

## Installation
 0. Make sure your hosting supports webp. See [Webp and Drupal](https://dev.acquia.com/blog/webp-and-drupal) for more information.
 1. Install the module.
 2. Install responsive_image module (Drupal 8 core)
 3. Select Responsive image in admin/config/media/responsive-image-style for the style you want to use
 4. Go to your content type, display, and choose responsive image (ie: admin/structure/types/manage/article/display)
 5. Optional clear caches, your html now should display something like this:

``` 
<picture>
<source srcset="/sites/default/files/styles/max_325x325/public/2019-07/IMG_1949-orig_19.webp?itok=yZGyfm_Z 244w, /sites/default/files/styles/large/public/2019-07/IMG_1949-orig_19.JPG?itok=Q4k4z1-p 360w, /sites/default/files/styles/max_650x650/public/2019-07/IMG_1949-orig_19.JPG?itok=hUF_TXH1 488w, /sites/default/files/styles/max_1300x1300/public/2019-07/IMG_1949-orig_19.JPG?itok=_WBWx1bc 975w, /sites/default/files/styles/max_2600x2600/public/2019-07/IMG_1949-orig_19.JPG?itok=RhJu7FQA 1950w" type="image/webp" sizes="(min-width: 1290px) 1290px, 100vw">
<source srcset="/sites/default/files/styles/max_325x325/public/2019-07/IMG_1949-orig_19.JPG?itok=yZGyfm_Z 244w, /sites/default/files/styles/large/public/2019-07/IMG_1949-orig_19.JPG?itok=Q4k4z1-p 360w, /sites/default/files/styles/max_650x650/public/2019-07/IMG_1949-orig_19.JPG?itok=hUF_TXH1 488w, /sites/default/files/styles/max_1300x1300/public/2019-07/IMG_1949-orig_19.JPG?itok=_WBWx1bc 975w, /sites/default/files/styles/max_2600x2600/public/2019-07/IMG_1949-orig_19.JPG?itok=RhJu7FQA 1950w" type="image/jpeg" sizes="(min-width: 1290px) 1290px, 100vw">
</picture>
```

Where the first source will be used by browsers supporting webp while the second will be picked up automatically for those that don't (ie Safari).

More detailed instructions here: admin/structure/types/manage/article/display


## Installation (Old deprecated method)
 1. Install the module.
 2. Add image styles

 \* This method does not work with all hostings, and is not the recommended way to use it.
 \* Images displayed using Drupal core's "Responsive image" formatter
 don't depend on the .htaccess mechanism to serve WebP derivatives.

## Maintainers
* Bart Vanhoutte (Bart Vanhoutte) - https://www.drupal.org/user/1133754
* Alex Moreno - https://www.alexmoreno.net - https://www.drupal.org/u/alexmoreno
