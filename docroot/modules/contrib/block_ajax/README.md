## Ajax Block

Provides the ability to load blocks via Ajax method. Once can
easily configure new and existing blocks to load via Ajax.

Essentially a placeholder will be dropped in place instead of
fully rendering the block. The block content will be pulled
from a custom route and then rendered in place via Ajax.

Rendering or display blocks via Ajax is sometimes necessary
to get around various levels of caching on a site.

### Requirements

No special requirements. Essentially requires Drupal Core blocks.

### Install

Install like any other contributed module. Installing through
composer is recommended:

```bash
composer require drupal/block_ajax
drush en block_ajax
```

### Usage

* Visit Structure -> Block layout page.
* Edit or create new block.
* New section should appear on block called "Ajax Block".
* Check "Load block via Ajax".
* Configure any other necessary block settings. Save block.
* Place block in region.
* Test page where block was placed, and it should load via Ajax.

### Roadmap

* Loading settings, like show spinner or other options.
* Event subscriber or possible middleware to invalidate ajax blocks
(this may not be needed.)

### Maintainers

George Anderson (geoanders)
https://www.drupal.org/u/geoanders
