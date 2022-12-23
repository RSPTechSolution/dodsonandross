<?php

namespace Drupal\minifyhtml\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Minifies the HTML of the response.
 *
 * @see \Symfony\Component\EventDispatcher\EventSubscriberInterface
 */
class MinifyHTMLExit implements EventSubscriberInterface {

  /**
   * Abort flag, when dependencies have not been injected.
   *
   * @var bool
   */
  protected $abort = FALSE;

  /**
   * Config Factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The content that this class minifies.
   *
   * @var string
   */
  protected $content;

  /**
   * Logger Factory object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * A list of placeholders for HTML elements that won't be minified.
   *
   * @var array
   */
  protected $placeholders = [];

  /**
   * Time object.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The placeholder token.
   *
   * @var string
   */
  protected $token;

  /**
   * Constructs a MinifyHTMLExit object.
   */
  public function __construct() {

    // To prevent warnings thrown by func_get_arg(), only attempt to get the
    // args if there are exactly 5.
    $arg_error = TRUE;
    if (func_num_args() == 5) {

      // Assigning the arguments this way prevents a signature mismatch
      // exception that will occur until the cache is cleared to update the
      // service definition. However, the cache cannot be cleared due to the
      // exception.
      // @todo create proper signature in 2.0.0 version.
      $this->config = func_get_arg(0);
      $this->time = func_get_arg(1);
      $this->logger = func_get_arg(2);
      $this->pathMatcher = func_get_arg(3);
      $this->currentPath = func_get_arg(4);

      if ($this->config) {
        $this->token = 'MINIFYHTML_' . md5($this->time->getRequestTime());
        $arg_error = FALSE;
      }
    }

    // Abort minification until cache is cleared.
    if ($arg_error) {
      \Drupal::messenger()->addWarning(t('Minify HTML has been disabled until the cache has been cleared.'));
      $this->abort = TRUE;
    }
  }

  /**
   * Minifies the HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Response event object.
   */
  public function response(FilterResponseEvent $event) {
    if (!$this->abort && $this->config->get('minifyhtml.config')->get('minify')) {

      // Skip excluded pages.
      $pages = $this->config->get('minifyhtml.config')->get('exclude_pages');
      if (!empty($pages) && $this->pathMatcher->matchPath($this->currentPath->getPath(), \mb_strtolower($pages))) {
        return;
      }

      $response = $event->getResponse();

      // Make sure that the following render classes are the only ones that
      // are minified.
      $allowed_response_classes = [
        'Drupal\big_pipe\Render\BigPipeResponse',
        'Drupal\Core\Render\HtmlResponse',
      ];
      if (in_array(get_class($response), $allowed_response_classes)) {
        $this->content = $response->getContent();
        $this->minify();

        // If, for some reason, the minification failed and some artifacts
        // still remain in the source this will cause a mostly white unusable
        // page. The fallback for this scenario is to revert and notify.
        if (strpos($this->content, '%' . $this->token)) {
          $this->logger->get('minifyhtml')->warning('Minifyhtml failed.');
        }
        else {
          $response->setContent($this->content);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    $events[KernelEvents::RESPONSE][] = ['response', -10000];

    return $events;
  }

  /**
   * Helper function to minify HTML.
   */
  protected function minify() {
    $callbacks = [
      'minifyhtmlPlaceholderCallbackTextarea' => '/\\s*<textarea(\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/i',
      'minifyhtmlPlaceholderCallbackPre' => '/\\s*<pre(\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i',
      'minifyhtmlPlaceholderCallbackScript' => '/\\s*<script(\\b[^>]*?>[\\s\\S]*?<\\/script>)\\s*/i',
      'minifyhtmlPlaceholderCallbackIframe' => '/\\s*<iframe(\\b[^>]*?>[\\s\\S]*?<\\/iframe>)\\s*/i',
      'minifyhtmlPlaceholderCallbackStyle' => '/\\s*<style(\\b[^>]*?>[\\s\\S]*?<\\/style>)\\s*/i',
    ];

    // Only strip HTML comments if required.
    if ($this->config->get('minifyhtml.config')->get('strip_comments')) {
      $callbacks['minifyhtmlRemoveHtmlComment'] = '/<!--([\\s\\S]*?)-->/';
    }

    foreach ($callbacks as $callback => $pattern) {
      $content = $this->minifyhtmlCallback($pattern, $callback);
      if (!is_null($content)) {
        $this->content = $content;
      }
    }

    // Minify the page.
    $this->minifyHtml();

    // Restore all values that are currently represented by a placeholder.
    if (!empty($this->placeholders)) {
      $this->content = str_replace(array_keys($this->placeholders), array_values($this->placeholders), $this->content);
    }
  }

  /**
   * Helper function to individually call our <tag> processors.
   *
   * @param string $pattern
   *   The pattern for the search.
   * @param string $callback
   *   The callback function to use.
   *
   * @return string
   *   The content with placeholders.
   */
  protected function minifyhtmlCallback($pattern, $callback) {
    $content = preg_replace_callback($pattern, [$this, $callback], $this->content);

    if ($error = preg_last_error()) {
      $this->logger->get('minifyhtml')->error('Preg error. The error code is @error. You can view what this error code is by viewing http://php.net/manual/en/function.preg-last-error.php', ['@error' => $error]);
    }

    return $content;
  }

  /**
   * Helper function to add place holder for <textarea> tag.
   *
   * @param array $matches
   *   Matches from initial preg_replace().
   *
   * @return string
   *   The placeholder string.
   */
  protected function minifyhtmlPlaceholderCallbackTextarea(array $matches) {
    return $this->minifyPlaceholderReplace(trim($matches[0]));
  }

  /**
   * Helper function to add place holder for <pre> tag.
   *
   * @param array $matches
   *   Matches from initial preg_replace().
   *
   * @return string
   *   The placeholder string.
   */
  protected function minifyhtmlPlaceholderCallbackPre(array $matches) {
    return $this->minifyPlaceholderReplace(trim($matches[0]));
  }

  /**
   * Helper function to add place holder for <iframe> tag.
   *
   * @param array $matches
   *   Matches from initial preg_replace().
   *
   * @return string
   *   The placeholder string.
   */
  protected function minifyhtmlPlaceholderCallbackIframe(array $matches) {
    $iframe = preg_replace('/^\\s+|\\s+$/m', '', $matches[0]);

    return $this->minifyPlaceholderReplace(trim($iframe));
  }

  /**
   * Helper function to add place holder for <script> tag.
   *
   * @param array $matches
   *   Matches from initial preg_replace().
   *
   * @return string
   *   The placeholder string.
   */
  protected function minifyhtmlPlaceholderCallbackScript(array $matches) {
    $search = [];
    $replace = [];

    // Only strip multi-line comments in <script> if required.
    if ($this->config->get('minifyhtml.config')->get('strip_comments')) {
      $search[] = '!/\*.*?\*/!s';
      $replace[] = '';
    }

    // Don't change newline if the type is "application/ld+json".
    if (strpos($matches[0], 'type="application/ld+json"') === FALSE) {
      // Trim each line.
      $search[] = '/^\\s+|\\s+$/m';
      $replace[] = "\n";
      // Remove multiple empty line.
      $search[] = '/\n(\s*\n)+/';
      $replace[] = "\n";
    }
    else {
      $search[] = '/\s\s+/';
      $replace[] = "";
    }

    $script = preg_replace($search, $replace, $matches[0]);

    return $this->minifyPlaceholderReplace(trim($script));
  }

  /**
   * Helper function to add place holder for <style> tag.
   *
   * @param array $matches
   *   Matches from initial preg_replace().
   *
   * @return string
   *   The placeholder string.
   */
  protected function minifyhtmlPlaceholderCallbackStyle(array $matches) {
    $search = [];
    $replace = [];

    // Only strip multi-line comments in <style> if required.
    if ($this->config->get('minifyhtml.config')->get('strip_comments')) {
      $search[] = '!/\*.*?\*/!s';
      $replace[] = '';
    }

    // Trim each line.
    $search[] = '/^\\s+|\\s+$/m';
    $replace[] = '';

    $style = preg_replace($search, $replace, $matches[0]);

    return $this->minifyPlaceholderReplace(trim($style));
  }

  /**
   * Helper function to add tag key and value for further replacement.
   *
   * @param string $content
   *   String before the placeholder replacement.
   *
   * @return string
   *   The placeholder string.
   */
  protected function minifyPlaceholderReplace($content) {
    $placeholder = '%' . $this->token . count($this->placeholders) . '%';
    $this->placeholders[$placeholder] = $content;

    return $placeholder;
  }

  /**
   * Helper function to remove HTML comments.
   *
   * Comments containing IE conditionals will be ignored.
   *
   * @param array $matches
   *   Matches from initial preg_replace().
   *
   * @return string
   *   String with removed HTML comments.
   */
  protected function minifyhtmlRemoveHtmlComment(array $matches) {
    return (0 === strpos($matches[1], '[') || FALSE !== strpos($matches[1], '<![')) ? $matches[0] : '';
  }

  /**
   * Helper function to minify the HTML.
   */
  protected function minifyHtml() {
    $search = [];
    $replace = [];

    // Remove whitespaces after tags, except space.
    $search[] = '/\>[^\S ]+/s';
    $replace[] = '>';

    // Remove whitespaces before tags, except space.
    $search[] = '/[^\S ]+\</s';
    $replace[] = '<';

    // Shorten multiple whitespace sequences.
    $search[] = '/(\s)+/s';
    $replace[] = '\\1';

    // Remove whitespaces around block/undisplayed elements.
    $search[] = '/\\s+(<\\/?(?:area|base(?:font)?|blockquote|body'
      . '|caption|center|col(?:group)?|dd|dir|div|dl|dt|fieldset|form'
      . '|frame(?:set)?|h[1-6]|head|hr|html|legend|li|link|map|menu|meta'
      . '|ol|opt(?:group|ion)|p|param|t(?:able|body|head|d|h||r|foot|itle)'
      . '|ul)\\b[^>]*>)/i';
    $replace[] = '$1';

    // Trim each line.
    $search[] = '/^\\s+|\\s+$/m';
    $replace[] = '';

    $minified = preg_replace($search, $replace, $this->content);

    // Only use minified content if there was not an error during minification.
    if (PREG_NO_ERROR === preg_last_error()) {
      $this->content = $minified;
    }
  }

}
