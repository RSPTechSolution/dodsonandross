
README.txt for ACL 8.x-1.x

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Support/Customizations
 * Maintainers


INTRODUCTION
------------

The ACL module, short for Access Control Lists, is an API for other modules to
create lists of users and give them access to nodes.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/acl

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/acl


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the ACL module as you would normally install a
   contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. It has no UI of its own and will not do anything by itself; install this
       module only if some other module tells you to.


TROUBLESHOOTING
---------------

Even though ACL does not do anything by its own, Core recognizes it as a node
access module, and it requires you to rebuild permissions upon installation.

The client module is fully responsible for the correct use of ACL. It is very
unlikely that ACL should cause errors. 

If there is a node access problem, or if you intend to implement a module that
uses ACL, we highly recommend to use the Devel Node Access module as outlined
in the step-by-step instructions in
http://drupal.org/node/add/project-issue/acl


SUPPORT/CUSTOMIZATIONS
----------------------

Support by volunteers is available on:

 * https://www.drupal.org/project/issues/acl?status=All&version=8.x

Please consider helping others as a way to give something back to the community
that provides Drupal and the contributed modules to you free of charge.

For paid support and customizations of this module, help with implementing an
ACL client module, or other Drupal work, contact the maintainer through his
contact form:

 * https://www.drupal.org/u/salvis


MAINTAINERS
-----------

 * Hans Salvisberg (salvis) - https://www.drupal.org/u/salvis
 * Earl Miles (merlinofchaos) - https://www.drupal.org/u/merlinofchaos

Supporting organizations:

 * Salvisberg Software & Consulting -
   https://www.drupal.org/salvisberg-software-consulting

Acknowledgments:

 * Originally written for Drupal 5 and maintained by merlinofchaos.
 * Ported to Drupal 6 and 7 and maintained by salvis.
 * Ported to Drupal 8 by id.tarzanych (Serge Skripchuk).

