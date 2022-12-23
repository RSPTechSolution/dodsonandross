<?php

namespace Drupal\s3fs\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\s3fs\S3fsService;
use Drupal\s3fs\S3fsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures s3fs settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The S3fs Service.
   *
   * @var \Drupal\s3fs\S3fsServiceInterface
   */
  protected $s3fs = NULL;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor for s3fs settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\s3fs\S3fsServiceInterface $s3fs
   *   The s3fs service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_Handler
   *   Module Handler Service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, S3fsServiceInterface $s3fs, ModuleHandlerInterface $module_Handler) {
    parent::__construct($configFactory);
    $this->s3fs = $s3fs;
    $this->moduleHandler = $module_Handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('s3fs'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 's3fs_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      's3fs.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('s3fs.settings');

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Amazon Web Services Credentials'),
      '#description' => $this->t(
        "To configure your Amazon Web Services credentials, enter the values in the appropriate fields below.
        To set access and secret key you must use \$settings['s3fs.access_key'] and \$settings['s3fs.secret_key'] in your site's settings.php file."
      ),
    ];

    if ($this->moduleHandler->moduleExists('key')) {
      $key_collection_url = Url::fromRoute('entity.key.collection')->toString();
      $form['credentials']['keymodule'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Keys'),
        '#description' => $this->t('Use keys managed by the key module. <a href=":keys">Manage keys</a>', [
          ':keys' => $key_collection_url,
        ]),
        '#tree' => FALSE,
      ];

      $form['credentials']['keymodule']['keymodule_access_key_name'] = [
        '#type' => 'key_select',
        '#title' => $this->t('Amazon Web Services Access Key'),
        '#empty_option' => $this->t('- Select -'),
        '#default_value' => $config->get('keymodule.access_key_name'),
        '#key_filters' => ['type' => 'authentication'],
        '#description' => $this->t('Select a key containing the AWS access key.'),
        '#states' => [
          'visible' => [
            ':input[id=edit-use-instance-profile]' => ['checked' => FALSE],
          ],
        ],
      ];

      $form['credentials']['keymodule']['keymodule_secret_key_name'] = [
        '#type' => 'key_select',
        '#title' => $this->t('Amazon Web Services Secret Key'),
        '#description' => $this->t('Select a key containing the AWS secret key.'),
        '#empty_option' => $this->t('- Select -'),
        '#default_value' => $config->get('keymodule.secret_key_name'),
        '#key_filters' => ['type' => 'authentication'],
        '#states' => [
          'visible' => [
            ':input[id=edit-use-instance-profile]' => ['checked' => FALSE],
          ],
        ],
      ];
    }

    $form['credentials']['credentials_file'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Credentials File Location'),
      '#default_value' => $config->get('credentials_file'),
      '#description' => $this->t(
        "A custom profile or ini file location. This will add a custom AWS SDK
         ini provider in addition to the AWS SDK defaultProvider.
         \$settings['s3fs.access_key'] and \$settings['s3fs.secret_key'] will
         take priority over this setting."
      ),
    ];

    $form['credentials']['use_credentials_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Credential Caching'),
      '#default_value' => $config->get('use_credentials_cache'),
      '#description' => $this->t(
        'Allow the AWS SDK to locally cache credentials. This is recommended
        if using EC2, IAM, or similar authentication methods that require an
        API call to AWS Metadata servers to obtain usable credentials.
      '),
    ];

    $form['credentials']['credentials_cache'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Credential Caching'),
      '#states' => [
        'visible' => [
          ':input[id=edit-use-credentials-cache]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['credentials']['credentials_cache']['credentials_cache_dir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cached Credentials Directory'),
      '#default_value' => $config->get('credentials_cache_dir'),
      '#description' => $this->t(
        "Copy of Cached credentials will be stored in this directory to
          avoid calling the metadata servers."
      ),
    ];
    if (!class_exists('\Doctrine\Common\Cache\FilesystemCache')) {
      $form['credentials']['credentials_cache_dir']['#disabled'] = TRUE;
    }

    $form['bucket'] = [
      '#type' => 'textfield',
      '#title' => $this->t('S3 Bucket Name'),
      '#default_value' => $config->get('bucket'),
      '#description' => $this->t("If you don't set this field, you must set bucket name in your settings.php \$config['s3fs.settings']['bucket']."),
    ];
    $form['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bucket Region'),
      '#default_value' => $config->get('region'),
      '#description' => $this->t(
        "The region in which your bucket resides. This value is set
        automatically when you <b>Save Configuration</b> on this page.
        You may override this in your settings.php with
         \$config['s3fs.settings']['region']."
      ),
      '#disabled' => TRUE,
    ];
    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced Configuration Options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $advanced = &$form['advanced'];
    $advanced['use_cname'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CNAME'),
      '#default_value' => $config->get('use_cname'),
      '#description' => $this->t('Serve files from a custom domain by using an appropriately named bucket, e.g. "mybucket.mydomain.com".'),
    ];
    $advanced['cname_settings_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CNAME Settings'),
      '#states' => [
        'visible' => [
          ':input[id=edit-use-cname]' => ['checked' => TRUE],
        ],
      ],
    ];
    $advanced['cname_settings_fieldset']['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN Domain Name'),
      '#default_value' => $config->get('domain'),
      '#description' => $this->t('If serving files from CloudFront, the bucket name can differ from the domain name.'),
    ];
    $advanced['cname_settings_fieldset']['domain_root'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Map Domain Name to specific path'),
      '#options'       => [
        'none' => $this->t('Entire bucket (mybucket)'),
        'root' => $this->t('Root folder (mybucket/root)'),
        'public' => $this->t('Public folder (mybucket/root/public).'),
      ],
      '#default_value' => $config->get('domain_root'),
      '#description'   => $this->t("Map the domain name to the location from where the file should be pulled.
        This is useful when using a service such as Cloudfront where the origin path can be a specific
        folder in a bucket, rather than the entire bucket. For example, with a root folder of \"staging\" and
        a public folder of \"s3fs-public\" (for a full path of \"mybucket/staging/s3fs-public\"), the
        Public folders option will allow the full path to be mapped directly to the domain name
        (\"my-cloudfront-id.cloudfront.net\"). By default, the domain name maps to the entire bucket.
        <b>NOTE:</b> This setting impacts both the s3:// and public:// StreamWrappers, if using the \"Public\" option
        it is likely that s3:// links will not work."
      ),
    ];

    $advanced['use_customhost'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a Custom Host'),
      '#default_value' => $config->get('use_customhost'),
      '#description' => $this->t('Connect to an S3-compatible storage service other than Amazon.'),
    ];
    $advanced['customhost_settings_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom Host Settings'),
      '#states' => [
        'visible' => [
          ':input[id=edit-use-customhost]' => ['checked' => TRUE],
        ],
      ],
    ];
    $advanced['customhost_settings_fieldset']['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#default_value' => $config->get('hostname'),
      '#description' => $this->t('Custom service hostname, e.g. "https://objects.example.com" with http(s) protocol.'),
      '#states' => [
        'visible' => [
          ':input[id=edit-s3fs-use-customhost]' => ['checked' => TRUE],
        ],
      ],
    ];
    $advanced['use_path_style_endpoint'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use path-style endpoint'),
      '#default_value' => $config->get('use_path_style_endpoint'),
      '#description' => $this->t('Send requests to a path-style endpoint, instead of a virtual-hosted-style endpoint. For example, %path_style, insead of %virtual_hosted_style.', [
        '%path_style' => 'http://s3.amazonaws.com/bucket',
        '%virtual_hosted_style' => 'http://bucket.s3.amazonaws.com',
      ]),
    ];
    $advanced['disable_version_sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable version sync'),
      '#default_value' => $config->get('disable_version_sync'),
      '#description' => $this->t('Check if the bucket does not support listObjectVersions.'),
    ];
    $advanced['cache_control_header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('S3 Object Cache-Control Header'),
      '#default_value' => $config->get('cache_control_header'),
      '#description' => $this->t('The cache control header to set on all S3 objects for CDNs and browsers, e.g.
      "public, max-age=300".'
      ),
    ];
    $advanced['encryption'] = [
      '#type' => 'select',
      '#options' => [
        '' => 'None',
        'AES256' => 'AES256',
        'aws:kms' => 'aws:kms',
      ],
      '#title' => $this->t('Server-Side Encryption'),
      '#default_value' => $config->get('encryption'),
      '#description' => $this->t(
        'If your bucket requires @ENCRYPTION, you can specify the encryption algorithm here',
        [
          '@ENCRYPTION' => Link::fromTextAndUrl($this->t('server-side encryption'),
            Url::fromUri('http://docs.aws.amazon.com/AmazonS3/latest/dev/UsingServerSideEncryption.html'
            ))->toString(),
        ]
      ),
    ];

    $advanced['use_https'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always serve files from S3 via HTTPS'),
      '#default_value' => $config->get('use_https'),
      '#description' => $this->t(
        'Forces S3 File System to always generate HTTPS URLs for files in your bucket,
      e.g. "https://mybucket.s3.amazonaws.com/smiley.jpg".<br>
      Without this setting enabled, URLs for your files will use the same scheme as the page they are served from.'
      ),
    ];
    $advanced['ignore_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore the file metadata cache'),
      '#default_value' => $config->get('ignore_cache'),
      '#description' => $this->t(
        "If you need to debug a problem with S3, you may want to temporarily ignore the file metadata cache.
      This will make all file system reads hit S3 instead of the cache.<br>
      <b>This causes s3fs to work extremely slowly, and should never be enabled on a production site.</b>"
      ),
    ];
    $advanced['read_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bucket is read-only'),
      '#default_value' => $config->get('read_only'),
      '#description' => $this->t(
        "Enable this option if the account used to access the bucket has read-only permissions:"
      ),
    ];
    $advanced['disable_cert_verify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable SSL/TLS verification'),
      '#default_value' => $config->get('disable_cert_verify'),
      '#description' => $this->t(
        "Enable if your bucket is using a locally self-signed certificate.<br>
        <b>PLEASE NOTE:</b> This is not recommended as it is a security risk."
      ),
    ];
    $advanced['disable_shared_config_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Shared Config Files'),
      '#default_value' => $config->get('disable_shared_config_files'),
      '#description' => $this->t(
        "Disables the searching of shared config files
          (such as ~/.aws/config). This may be necessary if open_basedir is
          enabled on the server."
      ),
    ];
    $advanced['redirect_styles_ttl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The TTL of the redirect cache to the s3 styles'),
      '#default_value' => $config->get('redirect_styles_ttl'),
      '#description' => $this->t('Styles will be redirected to S3 and Dynamic Page Cache module will cache the response for the specified TTL.'),
    ];
    $advanced['use_s3_for_public'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use S3 for public:// files'),
      '#default_value' => Settings::get('s3fs.use_s3_for_public'),
      '#disabled' => TRUE,
      '#description' => $this->t(
        "Enable this option to store all files which would be uploaded to or created in the web server's local file system
      within your S3 bucket instead. To replace public:// stream wrapper with s3fs stream, include the following in settings.php:<br>
      <em>\$settings['s3fs.use_s3_for_public'] = TRUE;</em><br><br>
      <b>PLEASE NOTE:</b> If you intend to use Drupal's performance options which aggregate your CSS or Javascript
      files, or will be using any other system that writes CSS or Javascript files into your site's public:// file system,
      you must perform some additional configuration to make those files work correctly when stored in S3.
      Please see the section titled \"Aggregated CSS and JS in S3\" in the README for details."
      ),
    ];
    $advanced['use_cssjs_host'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Custom CSS/JS Asset Host.'),
      '#default_value' => $config->get('use_cssjs_host'),
      '#description' => $this->t('Use a custom host for assets links inside
      CSS/JS files.'),
      '#states' => [
        'visible' => [
          ':input[id=edit-use-s3-for-public]' => ['checked' => TRUE],
        ],
      ],
    ];
    $advanced['use_cssjs_host_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom CSS/JS Host Settings'),
      '#states' => [
        'visible' => [
          ':input[id=edit-use-cssjs-host]' => ['checked' => TRUE],
        ],
      ],
    ];
    $advanced['use_cssjs_host_fieldset']['cssjs_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#default_value' => $config->get('cssjs_host'),
      '#description' => $this->t('Custom hostname, e.g. "objects.example.com" without http(s) protocol.'),
      '#states' => [
        'visible' => [
          ':input[id=edit-use-cssjs-host]' => ['checked' => TRUE],
        ],
      ],
    ];

    $php_storage = Settings::get('php_storage');
    $twig_storage = !empty($php_storage['twig']['directory'])
      ? $php_storage['twig']['directory']
      : PublicStream::basePath() . '/php';

    $advanced['twig_storage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twig compiled storage folder'),
      '#default_value' => $twig_storage,
      '#disabled' => TRUE,
      '#description' => $this->t(
        "<b>PLEASE NOTE:</b> If you intend use s3fs for public:// you should change your php twig storage folder to a local
        directory, php twig files in S3 produce latency and security issues (these files would be public). Please change
        the php_storage settings in your setting.php and choose a directory, out of docroot recommended. Example:<br>
        <em>\$settings['php_storage']['twig']['directory'] = '../storage/php';</em>"
      ),
      '#states' => [
        'visible' => [
          ':input[id=edit-use-s3-for-public]' => ['checked' => TRUE],
        ],
      ],
    ];

    $advanced['upload_as_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Upload all files as private in S3'),
      '#default_value' => Settings::get('s3fs.upload_as_private'),
      '#disabled' => TRUE,
      '#description' => $this->t(
        "Enable this option to store all files as private files in S3. To enable this feature include the following in settings.php:<br>
        <em>\$settings['s3fs.upload_as_private'] = TRUE;</em>"
      ),
    ];

    $advanced['use_s3_for_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use S3 for private:// files'),
      '#default_value' => Settings::get('s3fs.use_s3_for_private'),
      '#disabled' => TRUE,
      '#description' => $this->t(
        "Enable this option to store all files which would be uploaded to or created in the private://
      file system (files available only to authneticated users) within your S3 bucket instead. To replace private:// stream wrapper with s3fs stream, include the following in settings.php:<br>
        <em>\$settings['s3fs.use_s3_for_private'] = TRUE;</em>"
      ),
    ];
    $advanced['root_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Root Folder'),
      '#default_value' => $config->get('root_folder'),
      '#description' => $this->t(
        "S3 File System uses the specified folder as the root of the file system within your bucket (if blank, the bucket
      root is used). This is helpful when your bucket is used by multiple sites, or has additional data in it which
      s3fs should not interfere with.<br>
      The metadata refresh function will not retrieve metadata for any files which are outside the Root Folder.<br>
      This setting is case sensitive. Do not include leading or trailing slashes.<br>
      Changing this setting <b>will not</b> move any files. If you've already uploaded files to S3 through S3 File
      System, you will need to manually move them into this folder."
      ),
    ];
    $advanced['additional_folders'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Additional Folder Settings'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
      '#description' => $this->t(
        "Like the root folder, changing these settings <b>will not</b> move any files. If you've already uploaded files
      to S3 through S3 File System, you will need to manually move them into the corresponding folders."),
    ];
    $additional_folders = &$advanced['additional_folders'];
    $additional_folders['public_folder'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Public Folder'),
      '#default_value' => $config->get('public_folder'),
      '#description'   => $this->t(
        'The name of the folder in your bucket (or within the root folder) where public:// files will be stored.'
      ),
    ];
    $additional_folders['private_folder'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Private Folder'),
      '#default_value' => $config->get('private_folder'),
      '#description'   => $this->t(
        'The name of the folder in your bucket (or within the root folder) where private:// files will be stored.'
      ),
    ];
    $advanced['file_specific'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File-specific Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $file_specific = &$advanced['file_specific'];
    $file_specific['presigned_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Presigned URLs'),
      '#default_value' => $config->get('presigned_urls'),
      '#rows' => 5,
      '#description' => $this->t(
        'A list of timeouts and paths that should be delivered through a presigned url.<br>
      Enter one value per line, in the format timeout|path. e.g. "60|private_files/*". Paths use regex patterns
      as per @link. If no timeout is provided, it defaults to 60 seconds.<br>',
        [
          '@link' => Link::fromTextAndUrl($this->t('preg_match'), Url::fromUri('http://php.net/preg_match'))
            ->toString(),
        ]
      ),
    ];
    $file_specific['saveas'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Force Save As'),
      '#default_value' => $config->get('saveas'),
      '#rows' => 5,
      '#description' => $this->t(
        'A list of paths for which users will be forced to save the file, rather than displaying it in the browser.<br>
      Enter one value per line. e.g. "video/*". Paths use regex patterns as per @link.<br>',
        [
          '@link' => Link::fromTextAndUrl($this->t('preg_match'), Url::fromUri('http://php.net/preg_match'))
            ->toString(),
        ]
      ),
    ];
    $file_specific['torrents'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Torrents'),
      '#default_value' => $config->get('torrents'),
      '#rows' => 5,
      '#description' => $this->t(
        'A list of paths that should be delivered via BitTorrent.<br>
      Enter one value per line, e.g. "big_files/*". Paths use regex patterns as per @link.<br>
      <b>Private files and paths which are already set as Presigned URLs or Forced Save As cannot be delivered as torrents.</b>',
        [
          '@link' => Link::fromTextAndUrl($this->t('preg_match'), Url::fromUri('http://php.net/preg_match'))
            ->toString(),
        ]
      ),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (!class_exists('\Doctrine\Common\Cache\FilesystemCache')) {
      $values['use_credentials_cache'] = FALSE;
      $values['credentials_cache_dir'] = '';
    }

    // Bucket region detection.
    if ($this->configFactory->get('s3fs.settings')->hasOverrides('region')) {
      $region = $this->configFactory->get('s3fs.settings')->get('region');
    }
    else {
      if (!empty($values['use_customhost']) && !empty($values['hostname'])) {
        // S3Client must always have a region, use a safe default.
        $region = S3fsService::DEFAULT_S3_REGION;
      }
      else {
        // Let the api get the region for the specified bucket.
        // Make sure we always have a safe region set before calling AWS.
        $values['region'] = S3fsService::DEFAULT_S3_REGION;

        $testClientConfig = $values;
        $testClientConfig['keymodule'] = [];

        if ($this->moduleHandler->moduleExists('key')) {
          if (!empty($values['keymodule_access_key_name']) && !empty($values['keymodule_secret_key_name'])) {
            $testClientConfig['keymodule']['access_key_name'] = $values['keymodule_access_key_name'];
            $testClientConfig['keymodule']['secret_key_name'] = $values['keymodule_secret_key_name'];
          }
          unset($testClientConfig['keymodule_access_key_name']);
          unset($testClientConfig['keymodule_secret_key_name']);
        }

        $client = $this->s3fs->getAmazonS3Client($testClientConfig);
        try {
          $region = $client->determineBucketRegion($values['bucket']);
          if (!$region) {
            // Error with trying to determine region, use the default.
            $region = S3fsService::DEFAULT_S3_REGION;
          }
        }
        catch (\Exception $e) {
          $region = S3fsService::DEFAULT_S3_REGION;
        }
      }
    }

    $config = $this->config('s3fs.settings');

    $config
      ->set('credentials_file', $values['credentials_file'])
      ->set('use_credentials_cache', $values['use_credentials_cache'])
      ->set('credentials_cache_dir', rtrim($values['credentials_cache_dir'], '/'))
      ->set('bucket', $values['bucket'])
      ->set('region', $region)
      ->set('use_cname', $values['use_cname'])
      ->set('domain_root', $values['domain_root'])
      ->set('use_customhost', $values['use_customhost'])
      ->set('hostname', UrlHelper::stripDangerousProtocols($values['hostname']))
      ->set('domain', $values['domain'])
      ->set('use_path_style_endpoint', $values['use_path_style_endpoint'])
      ->set('disable_version_sync', $values['disable_version_sync'])
      ->set('cache_control_header', $values['cache_control_header'])
      ->set('encryption', $values['encryption'])
      ->set('use_https', $values['use_https'])
      ->set('ignore_cache', $values['ignore_cache'])
      ->set('read_only', $values['read_only'])
      ->set('disable_cert_verify', $values['disable_cert_verify'])
      ->set('disable_shared_config_files', $values['disable_shared_config_files'])
      ->set('redirect_styles_ttl', $values['redirect_styles_ttl'])
      ->set('use_cssjs_host', $values['use_cssjs_host'])
      ->set('cssjs_host', $values['cssjs_host'])
      ->set('root_folder', trim($values['root_folder'], '\/'))
      ->set('public_folder', trim($values['public_folder'], '\/'))
      ->set('private_folder', trim($values['private_folder'], '\/'))
      ->set('presigned_urls', $values['presigned_urls'])
      ->set('saveas', $values['saveas'])
      ->set('torrents', $values['torrents']);

    if ($this->moduleHandler->moduleExists('key')) {
      $config->set('keymodule.access_key_name', $values['keymodule_access_key_name'])
        ->set('keymodule.secret_key_name', $values['keymodule_secret_key_name']);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
