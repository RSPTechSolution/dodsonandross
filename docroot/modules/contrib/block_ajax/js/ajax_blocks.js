(function ($, Drupal) {
  Drupal.behaviors.block_ajax = {
    attach: function (context, drupalSettings) {
      /**
       * Implements ajax block handler.
       */
      let ajaxBlockHandler = function ($block) {
        // Make sure we have block.
        let blockId = $block.data('block-ajax-id');
        if (blockId && drupalSettings.block_ajax.blocks[blockId] !== undefined) {
          let pluginId = $block.data('block-ajax-plugin-id');
          if (!pluginId) {
            return;
          }

          // Block parent.
          let $blockParent = $block.parent();

          let ajaxUrl = 'block/ajax/' + blockId + '?';
          let ajaxUrlParams = {};

          // Get current node.
          let currentNode = drupalSettings.block_ajax.blocks[blockId].current_node;
          if (currentNode) {
            ajaxUrlParams.nid = currentNode;
          }

          // Get current user.
          let currentUser = drupalSettings.block_ajax.blocks[blockId].current_user;
          if (currentUser) {
            ajaxUrlParams.uid = currentUser;
          }

          // Get current term.
          let currentTerm = drupalSettings.block_ajax.blocks[blockId].current_term;
          if (currentTerm) {
            ajaxUrlParams.tid = currentTerm;
          }

          // Setup request.
          let request = $.ajax({
            type: drupalSettings.block_ajax.config['type'],
            url: Drupal.url(ajaxUrl + $.param(ajaxUrlParams)),
            data: {
              plugin_id: pluginId,
              config: drupalSettings.block_ajax.blocks[blockId]
            },
            timeout: drupalSettings.block_ajax.config['timeout'],
            cache: drupalSettings.block_ajax.config['cache'],
            async: drupalSettings.block_ajax.config['async'],
            dataType: drupalSettings.block_ajax.config['dataType'],
            beforeSend: function () {
              if (drupalSettings.block_ajax.blocks[blockId].block_ajax.show_spinner) {
                // Add Ajax progress throbber.
                if (drupalSettings.block_ajax.blocks[blockId].block_ajax.placeholder) {
                  // Add throbber with message.
                  $block.after(Drupal.theme.ajaxProgressThrobber(Drupal.t(drupalSettings.block_ajax.blocks[blockId].block_ajax.placeholder)));
                } else {
                  // Add throbber with no message.
                  $block.after(Drupal.theme.ajaxProgressThrobber());
                }
              }
            }
          });

          // Request done handler.
          request.done(function (data) {
            // No content, let's return.
            if (typeof data.content !== 'string' || !data.content.length) {
              return;
            }

            // Add processed class to block wrapper.
            // Remove contextual-region class and move to inside.
            $blockParent.addClass('block-ajax-processed').removeClass('contextual-region');

            // Replace block content.
            $block.replaceWith(data.content);

            // Move contextual links inside new block content.
            $('.contextual', $blockParent).prependTo($('.block-ajax-block', $blockParent));

            // Add in contextual-region class.
            $('.block-ajax-block', $blockParent).addClass('contextual-region');

            // Attach behaviours to the updated element.
            $block.each(function () {
              // Note that the global drupal settings reference is being used to
              // make sure the actualised global state is being used.
              Drupal.attachBehaviors(this, window.drupalSettings);
            });

            // Remove throbber.
            if (drupalSettings.block_ajax.blocks[blockId].block_ajax.show_spinner) {
              $('.ajax-progress-throbber', $blockParent).remove();
            }
          });

          // Request fail handler.
          request.fail(function (jqXHR, textStatus) {
            // Remove throbber.
            if (drupalSettings.block_ajax.blocks[blockId].block_ajax.show_spinner) {
              $('.ajax-progress-throbber', $blockParent).remove();
            }

            // Throw console log error.
            console.log("Ajax Block: " + blockId + " request failed: " + textStatus);
          });
        }
      };
      /**
       * Initialize and loop over Ajax blocks.
       */
      $('[data-block-ajax-id]', context).once('block_ajax').each(function () {
        let $block = $(this);

        // Load in block via AJAX
        ajaxBlockHandler($block);

        // On RefreshAjaxBlock event
        $block.on('RefreshAjaxBlock', function () {
          // Execute the handler payload.
          ajaxBlockHandler($(this));
        });
      });
    },
    detach: function (context) {
    }
  };
  /**
   * Implements ajax block refreshing command.
   */
  Drupal.AjaxCommands.prototype.AjaxBlockRefreshCommand = function (ajax, response, status) {
    $(response.selector).trigger('RefreshAjaxBlock');
  };
})(jQuery, Drupal);
