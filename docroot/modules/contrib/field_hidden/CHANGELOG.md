# Changelog

All notable changes to **drupal/field_hidden** will be documented in this file,
using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.


## [2.0.0]

### Changed
* Drupal 9 compatibility.
* Changelog in standard keepachangelog format; previous was idiosyncratic.


## [8.x-1.0] - 2016-05-01

### Fixed
* Fixed invalid format placeholders on help page (issue #2641872).


## [8.x-1.0-beta1] - 2015-01-24

### Added
* Issue #2400975 by amitsedaiz: A basic port of Field Hidden module to Drupal 8.
* Issue #2400975 by amitsedaiz: Removed field types; the module now only defines
  new field widgets (see issue #2400975 comment #4 -
  https://www.drupal.org/node/2400975#comment-9477383).
* Removed option (field_hidden_instance_settings_hide_defval) to (not) display
  default value element, because obsolete and only exists in D7 version because
  that fixes default value element display via Javascript (which some people
  might find 'offensive').
* Removed module config form, and related permission, because the module no
  longer defines any config vars.
* Removed all admin resources, stylesheets and routes.
* Finished release note.
* Removed copyright text/notice, which ascribed copyright of the module's
  source to a single developer/maintainer.
* Renamed widget classes to accord (verbatim) with the classes they extend.
* Refactored formElement() methods to clarify what kind of (non-Drupal)
  entities we're modifying.
* The widgets now add custom CSS selector to the HTML hidden elements, which
  flag the actual type and that a column is being rendered via Field Hidden.
* Added stylesheet which prevents widgets of multiple-rowed instances from being
  displayed.
* Issue #2400975 by jacobfriis: Added (dysfunctional) support for formatted text
  types; clarified some documentation; and added some @todos.
* Issue #2400975 by jacobfriis: Removed dysfunctional (insufficient) support for
  formatted text types (see issue #2400975 comment #13 -
  https://www.drupal.org/node/2400975#comment-9480383).
* Issue #2400975 by jacobfriis: Updated module help section, rewrote the readme,
  and fixed some documentation.
