CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module provides a WebP processor for the ImageAPI Optimize module. When
added to a pipeline, a .webp derivative is created from the source image,
providing 2 versions of a styled image.


 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/imageapi_optimize_webp

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/imageapi_optimize_webp


REQUIREMENTS
------------

This module requires the following modules:

 * Image Optimize (or ImageAPI Optimize) (https://www.drupal.org/project/imageapi_optimize)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.

 * Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


Configuration
----------------

* Configure a new Image Optimize Pipeline

     - First, you will need to Configure a new Image Optimize Pipeline. To do
     this navigate to Configuration >> Media >> Image Optimize pipelines. Fill
     out Image optimize pipeline name. There is a dropdown list that reads
     Select a new processor, choose WebP Deriver then click Add. On the next
     screen fill in the Image quality this represents a percentage. Click Add
     processor.


* Set a Sitewide default pipeline

     - You can set a Sitewide default pipeline that will load in automatically
     on all Image Styles using the Sitewide default pipeline. Navigate to
     Configuration >> Media >> Image Optimize pipelines. Select the preferred
     Pipeline. Click Save configuration.

* Set an individual Image style's Image Optimize Pipeline

     - Now when you travel to Configuration > Media > Image and edit your
     preferred Image style. At the bottom of the form you can select your
     preferred Image Optimize Pipeline or the Sitewide default pipeline.


MAINTAINERS
-----------

 * Michael Lander (michaellander) - https://www.drupal.org/u/michaellander

This project has been sponsored by:

 * Elevated Third

Specializing in Drupal-powered digital experiences that get results. Visit
https://www.elevatedthird.com/
