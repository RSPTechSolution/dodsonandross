CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This module adds an exclude pages filter for blocks. To exclude specific
pages after the wild card or in between wildcards, simply prefix the path
pattern with a prefixed '!' in the block page visibility configuration.

This works for visibility set to "show for the listed pages", in this case,
the exclude paths will hide the block on pages that match the despite the
wildcard set to show. - Or - the other way around, if the page list is set to
"hide for the listed pages" the excluded paths will show the block on pages
where the pattern matches despite the wild card set to hide.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/block_exclude_pages

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/node/add/project-issue/block_exclude_pages

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------

Once the module is enabled go to the block's configurations page you want to
make configuration changes to. and edit the "pages" visibility setting.

Basic example where a wildcard is used to display the block on pages under
the user path:

  /user/* <-- this will make the block visible on all pages under the path.

But let's say you want to exclude a specific page or another path directory
under the path "/user/?":

  !/user/jc <-- now you will be able to specifically exclude the "jc" page

or/and:

  !/user/jc/* <-- exclude on all pages under "jc/?"


MAINTAINERS
-----------

Current maintainers:
 * Jaime Contreras (TXC) - https://www.drupal.org/u/jcontreras

This project has been sponsored by:
 * TEXAS CREATIVE (TXC)
    Identity and brand development, brand management, advertising campaigns,
    marketing communications, and interactive services that include email
    campaigns, media planning, media buying and the development of responsive
    websites; all under one roof.
    Visit https://texascreative.com/for more information.
