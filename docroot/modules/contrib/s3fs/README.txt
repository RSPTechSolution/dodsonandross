INTRODUCTION
------------

  * S3 File System (s3fs) provides an additional file system to your drupal
    site, alongside the public and private file systems, which stores files in
    Amazon's Simple Storage Service (S3) or any S3-compatible storage service.
    You can set your site to use S3 File System as the default, or use it only
    for individual fields. This functionality is designed for sites which are
    load-balanced across multiple servers, as the mechanism used by Drupal's
    default file systems is not viable under such a configuration.

TABLE OF CONTENTS
-----------------

* REQUIREMENTS
* S3FS INITIAL CONFIGURATION
* CONFIGURE DRUPAL TO STORE FILES IN S3
* COPY LOCAL FILES TO S3
* AGGREGATED CSS AND JS IN S3
* IMAGE STYLES
* CACHE AWS CREDENTIALS
* UPGRADING FROM S3 FILE SYSTEM 7.x-2.x or 7.x-3.x
* TROUBLESHOOTING
* KNOWN ISSUES
* UNINSTALLING
* DEVELOPER TESTING
* ACKNOWLEDGEMENT
* MAINTAINERS

REQUIREMENTS
------------

  * AWS SDK version-3. If module is installed via Composer it gets
    automatically installed.

  * Your PHP must be configured with "allow_url_fopen = On" in your php.ini
    file.
    Otherwise, PHP will be unable to open files that are in your S3 bucket.

  * Ensure the account used to connect to the S3 bucket has sufficient
    privileges.

    * Minimum required actions for read-write are:

      "Action": [
          "s3:ListBucket",
          "s3:ListBucketVersions",
          "s3:PutObject",
          "s3:GetObject",
          "s3:DeleteObjectVersion",
          "s3:DeleteObject",
          "s3:GetObjectVersion"
          "s3:GetObjectAcl",
          "s3:PutObjectAcl",
      ]

    * For read-only buckets you must NOT grant the following actions:

      s3:PutObject
      s3:DeleteObjectVersion
      s3:DeleteObject
      s3:PutObjectAcl

  * Optional: doctrine/cache library for caching S3 Credentials.

S3FS INITIAL CONFIGURATION
--------------------------

  * S3 Credentials configuration:

    * Option 1: Use AWS defaultProvider.

      No configuration of credentials is required. S3fs will utilize the SDK
      default method of checking for environment variables, shared
      credentials/profile files, or assuming IAM roles.

      It is recommended to review the CACHE AWS CREDENTIALS section of this
      guide.

      Continue with configuration below.

    * Option 2: Provide an AWS compatible credentials INI.

      Create an AWS SDK compatible INI file with your configuration in the
      [default] profile. It is recommend that this file be located outside
      the docroot of your server for security.

      Example:
        [default]
        aws_access_key_id = YOUR_ACCESS_KEY
        aws_secret_access_key = YOUR_SECRET_KEY

      Visit /admin/config/media/s3fs and set the path to your INI in
      'Custom Credentials File Location'

      Note: If your INI causes lookups to AWS for tokens please review the
      CACHE AWS CREDENTIALS section of this guide.

      See https://docs.aws.amazon.com/cli/latest/userguide/cli-configure-files.html
      for more information on file format.

    * Option 3: Use the key module:

        Install the Key module from https://www.drupal.org/project/key and select
        your Amazon Web Services credentials from the dropdowns in the "Keys"
        panel at (/admin/config/media/s3fs).

    * Option 4: Set Access Key and Secret Key in settings.php:

      Example:
        $settings['s3fs.access_key'] = 'YOUR ACCESS KEY';
        $settings['s3fs.secret_key'] = 'YOUR SECRET KEY';

      * Reminder: For security reasons you should ensure that all secrets are
        stored outside the document root.

  * Configure your settings for S3 File System (including your S3 bucket name)
    at /admin/config/media/s3fs.

  * Savings the settings page will trigger the bucket region to be detected.

  * If your S3 bucket is configured with BlockPublicAcls then enable the
    'upload_as_private' setting.

    Example:
      $settings['s3fs.upload_as_private'] = TRUE;

    * If s3fs will provide storage for s3:// or public:// files the generated
      links will return 403 errors unless access is granted either with
      presigned urls or through other external means.

  * With the settings saved, go to /admin/config/media/s3fs/actions.

    * First validate your configuration to verify access to your S3 bucket.

    * Next refresh the file metadata cache. This will copy the filenames and
      attributes for every existing file in your S3 bucket into Drupal's
      database. This can take a significant amount of time for very large
      buckets (thousands of files). If this operation times out, you can also
      perform it using "drush s3fs-refresh-cache".

  * Please keep in mind that any time the contents of your S3 bucket change
    without Drupal knowing about it (like if you copy some files into it
    manually using another tool), you'll need to refresh the metadata cache
    again. S3FS assumes that its cache is a canonical listing of every file in
    the bucket. Thus, Drupal will not be able to access any files you copied
    into your bucket manually until S3FS's cache learns of them. This is true
    of folders as well; s3fs will not be able to copy files into folders that
    it doesn't know about.

  * After refreshing the s3fs metadata it is recommended to clear the Drupal
    Cache.

CONFIGURE DRUPAL TO STORE FILES IN S3
-------------------------------------

  * Optional: To enable S3 to be the default for new storage fields visit
    /admin/config/media/file-system and set the "Default download method" to
    "Amazon Simple Storage Service"

  * To begin using S3 for storage either edit an existing field or add a new
    field of type File, Image, etc. and set the "Upload destination" to
    "S3 File System" in the "Field Settings" tab. Files uploaded to a field
    configured to use S3 will be stored in the S3 bucket.

    * Drupal will by default continue to store files it creates automatically
      (such as aggregated CSS) on the local filesystem as they are hard coded
      to use the public:// file handler. To prevent this enable takeover of
      the public:// file handler.

  * To enable takeover of the public and/or private file handler(s you can
    enable s3fs.use_s3_for_public and/or s3fs.use_s3_for_private in
    settings.php. This will cause your site to store newly uploaded/generated
    files from the public/private file system in S3 instead of in local
    storage.

    Example:
      $settings['s3fs.use_s3_for_public'] = TRUE;
      $settings['s3fs.use_s3_for_private'] = TRUE;

    * These settings will cause the existing file systems to become invisible
      to Drupal. To remedy this, you will need to copy the existing files into
      the S3 bucket.

    * Refer to the 'COPY LOCAL FILES TO S3' section of the manual.

  * If you use s3fs for public:// files:

    * You should change your php twig storage folder to a local directory.
      Php twig files stored in S3 pose a security concern (these files would
      be public) in addition to a performance concern(latency).
      Change the php_storage settings in your setting.php. It is recomend that
      this directory be outside out of the docroot.

      Example:
        $settings['php_storage']['twig']['directory'] = '../storage/php';

      If you have a multiple backends you may use a NAS to store it or other
      shared storage system with your others backends.

    * Refer to 'AGGREGATED CSS AND JS IN S3' for important information
      related to bucket configuration to support aggregated CSS/JS files.

    * Clear the Drupal Cache:

      * Whenever making changes to enable/disable public:// or private://
        StreamWrappers it is necessary to clear the Drupal Container Cache

      * The Container Cache can be cleared either with 'drush cr' or by
        visiting admin/config/development/performance and clicking the
        'clearing all caches' button.

COPY LOCAL FILES TO S3
----------------------

  * The migration process is only useful if you have enabled or plan to enable
    public:// or private:// filesystem handling by s3fs.

  * It is possible to copy local files to s3 without activating the
    use_s3_for_public or use_s3_for_private handlers in settings.php
    If activated before the migration existing files will be unavailable during
    the migration process.

  * You are strongly encouraged to use the drush command "drush
    s3fs-copy-local" to do this, as it will copy all the files into the correct
    subfolders in your bucket, according to your s3fs configuration, and will
    write them to the metadata cache.

    See "drush help s3fs:copy-local" for command syntax.

  * If you don't have drush, you can use the
    buttons provided on the S3FS Actions page (admin/config/media/s3fs/actions),
    though the copy operation may fail if you have a lot of files, or very
    large files. The drush command will cleanly handle any combination of
    files.

  * You should not allow new files to be uploaded during the migration process.

  * Once the migration is complete you can, if you have not already, enable
    public:// and/or private:// takeover. The files will be served from S3
    instead of the local filesystem. You may delete the local files when
    you are sure you no longer require them locally.

  * You can perform a custom migrating process by implementing
    S3fsServiceInterface or extending S3fsService and use your custom service
    class in a ServiceProvider (see S3fsServiceProvider).

AGGREGATED CSS AND JS IN S3
---------------------------

  * In previous versions S3FS required that the server be configured as a
    reverse proxy in order to use the public:// StreamWrapper.
    This requirement has been removed. Please read below for new requirements.

  * CSS and Javascript files will be stored in your S3 bucket with all other
    public:// files.

  * Because of the way browsers restrict reqeusts made to domains that differ
    from the original requested domain you will need to ensure you have setup
    a CORS policy on your S3 Bucket or CDN.

  * Sample CORS policy that will allow any site to load files:

    <CORSConfiguration>
      <CORSRule>
        <AllowedOrigin>*</AllowedOrigin>
        <AllowedMethod>GET</AllowedMethod>
      </CORSRule>
    </CORSConfiguration>

  * Please see https://docs.aws.amazon.com/AmazonS3/latest/userguide/cors.html
    for more information.

  * Links inside CSS/JS files will be rewritten to use either the base_url of
    the webserver or optionally a custom hostname.

    Links will generate with https:// if use_https is enabled otherwise links
    will generate //servername/path notation to allow for protocol agnostic
    loading of content. If your server supports HTTPS it is recommended to
    enable use_https.

IMAGE STYLES
------------

  * S3FS display image style from Amazon trough dynamic routes /s3/files/styles/
    to fix the issues around style generated images being stored in S3.
    (read more at https://www.drupal.org/node/2861975)

  * If you are using Nginx as webserver, it is neccessary to add additional
    block to your Nginx site configuration:

    location ~ ^/s3/files/styles/ {
            try_files $uri @rewrite;
    }

CACHE AWS CREDENTIALS
---------------------

  * Some authentication methods inside of the AWS ecosystem make calls to
    AWS servers in order to obtain credentials. Using an IAM role assigned
    to an instance is an example of such a method.

  * AWS does not charge for these API calls but may rate limit the requests
    leading to random errors when a request is rejected.

  * In order to avoid rate limits and increase performance it is recommended
    to enable the caching of S3 Credentials that rely on receiving tokens
    from AWS.

  * WARNING: Enabling caching will store a copy of the credentials in plain
    text on the filesystem.

    * Depending upon configuration the credentials may be short lived STS
      credentials or may be long-lived access_keys.

  * Enable Credential Caching:

    * Install doctrine/cache
      composer require "doctrine/cache:~1.4"

    * Configure a directory to store the cached credentials.

      * The directory can be entered into the 'Cached Credentials Folder'
      setting on /admin/config/media/s3fs.

      * This directory should be stored outside the docroot of the server.
        and should not be included in backups or replication.

      * The directory will be created if it does not exist.

      * Directories and files will be crated with a umask of 0012 (rwxrw----).


UPGRADING FROM S3 FILE SYSTEM 7.x-2.x or 7.x-3.x
------------------------------------------------

  * Please read the 'S3FS INITIAL CONFIGURATION'
    and 'CONFIGURE DRUPAL TO STORE FILES IN S3' sections. for how to
    configure settings that can not be migrated.

  * The $conf settings have been changed and are no longer recommend.
    When not using the settings page it is recommend to use Drupal
    configuration management to import configuration overrides.

  * d7_s3fs_config can be used to import the majority of configurations from
    the previous versions. Please verify all settings after importing.

    The following settings can not be migrated into configuration:
    - awssdk_access_key
    - awssdk_secret_key
    - s3fs_use_s3_for_public
    - s3fs_use_s3_for_private
    - s3fs_domain_s3_private

  * After configuring s3fs in D8/D9 perform a config validation and a metadata
    cache refresh to import the current list of files stored in the S3 bucket.

  * d7_s3fs_s3_migrate, d7_s3fs_public_migrate, and d7_s3fs_private_migrate
    can be used to import file entries from s3://, public://, and private://
    schemes when s3fs was used to manage files in D7.  d7_s3fs_s3_migrate
    should be ran in almost all migrations while the public:// and private://
    migrations should only be executed if s3fs takeover was enabled for them
    in D7.

    These scripts copy the managed file entries from D7 into D8 without
    copying the actual files because they are already stored in the s3
    bucket.

    When public:// or private:// files are stored in s3fs the D8/9 core
    d7_file(public://) and/or d7_file_private(private://) migrations should
    not be executed as the s3fs migration tasks will perform all required
    actions.

  * If you use some functions or methods from .module or other files in your
    custom code you must find the equivalent function or method.

TROUBLESHOOTING
---------------

  * In the unlikely circumstance that the version of the SDK you downloaded
    causes errors with S3 File System, you can download this version instead,
    which is known to work:
    https://github.com/aws/aws-sdk-php/releases/download/3.22.7/aws.zip

  * IN CASE OF TROUBLE DETECTING THE AWS SDK LIBRARY:
    Ensure that the aws folder itself, and all the files within it, can be read
    by your webserver. Usually this means that the user "apache" (or "_www" on
    OSX) must have read permissions for the files, and read+execute permissions
    for all the folders in the path leading to the aws files.

  * Folder Integrity Constraint Violation during Metadata Refresh:

    This error should not be ignored. While s3fs will appear to function the
    metadata table that is critical to module operations will be in an
    unknown operational state which could lead to file errors and data loss.

    Known possible causes and solutions:

      * An Object exists at '/path/to/object' and
        '/path/to/object/another_object' in the bucket.

        Solution:
        Either remove/rename the root object or remove/rename all objects with
        same path prefix as the root object.

      * A directory record existed in the 's3fs_file' table for
        '/path/to/object' and a single object exists in the bucket at that path
        with no objects located below that prefix.

        This can occur if a directory was created for an object, but not
        deleted with rmdir() prior to a new object being added into the bucket.

        Solution:
        Ensure there are no new attempts to write a file below the prefix of
        the known object as this will replace the file record with a directory
        record.

        The directory record should have been deleted by the completion of the
        cache refresh.

        Perform a second refresh to ensure this was the actual
        cause of the constraint violation.

KNOWN ISSUES
------------

  * Files must not contain UTF8 4-byte characters due to SQL limits.
    @see https://www.drupal.org/project/s3fs/issues/3266062

  * Moving/renaming 'directories' is not supported. Objects must be moved
    individually.
    @see https://www.drupal.org/project/s3fs/issues/3200867

  * The max file size supported for writing is currently 5GB.
    @see https://www.drupal.org/project/s3fs/issues/3204634

  * These problems are from Drupal 7, now we don't know if they happen in 8.
    If you tried that options or know new issues, please create a new issue
    in https://www.drupal.org/project/issues/s3fs?version=8.x

      * Some curl libraries, such as the one bundled with MAMP, do not come
        with authoritative certificate files. See the following page for
        details:
        http://dev.soup.io/post/56438473/If-youre-using-MAMP-and-doing-something

      * Because of a limitation regarding MySQL's maximum index length for
        InnoDB tables, the maximum uri length that S3FS supports is 255
        characters. The limit is on the full path including the s3://,
        public:// or private:// prefix as they are part of the uri.

        This limit is the same limit as imposed by Drupal for max managed file
        lengths, however some unmanaged files (image derivatives) could be
        impacted by this limit.

      * eAccelerator, a deprecated opcode cache plugin for PHP, is incompatible
        with AWS SDK for PHP. eAccelerator will corrupt the configuration
        settings for the SDK's s3 client object, causing a variety of different
        exceptions to be thrown. If your server uses eAccelerator, it is highly
        recommended that you replace it with a different opcode cache plugin,
        as its development was abandoned several years ago.

UNINSTALLING
------------

Removing s3fs from an installation is similar to removing a hard drive from an
existing system, any files presently stored on the S3 bucket will no longer be
accessible once the module is removed. Care must be taken to ensure the site
has been prepared before uninstalling the s3fs module.

* Prior to starting migration from s3fs the site should be placed into
  maintenance mode and all tasks that will write new files to the s3fs managed
  streams should be disabled.

* Migrating files from s3fs public:// and private:// takeover before
  uninstalling:

  Migrating from public/private takeover is the easiest method migrate from.
  Files can be copied from the S3 bucket using any available S3 tools and
  placed in the appropriate folders inside Drupal.

  Once files have been migrated public/private takeover should be disabled in
  settings.php and the Drupal Cache refreshed.

* Migrating from s3:// before uninstalling:

  It is not possible for the s3fs module to be able to determine where files
  should be placed upon removal. As with the public/private takeover you may
  copy the files from the S3 bucket using the most convenient tool and place
  them in the replacement location.

  File paths will need to be re-written in the Database to refer to new paths.

  In addition, you must ensure no fields or system components are configured to
  utilize s3:// for file storage.

  An alternative solution to rewriting paths may be to use a replacement
  streamWrapper that allows registering the 's3://' scheme to a new location.

* Removal of the s3fs module:

  Once public/private takeover has been disabled and no configuration in
  Drupal refer to any of the s3fs provided streamWrapper paths you may
  uninstall the s3fs module in the same manner as any other module.

  Failure to remove all references to s3fs managed paths and files could
  result in unexpected errors.

DEVELOPER TESTING
-----------------

PHPUnit tests exist for this project.  Some tests may require configuration
before they can be executed.

  * S3 Configuration

    Default configuration for S3 is to attempt to reach a localstack
    server started with EXTERNAL_HOSTNAME of 's3fslocalstack' using hostnames
    's3.s3fslocalstack' and 's3fs-test-bucket.s3.s3fslocalstack'. This can be
    overridden by editing the prepareConfig() section of
    src/Tests/S3fsTestBase.php or by setting the following environment
    variables prior to execution:

      * S3FS_AWS_NO_CUSTOM_HOST=true - Use default AWS servers.
      * S3FS_AWS_CUSTOM_HOST = Custom S3 host to connect to.
      * S3FS_AWS_KEY - AWS IAM user key
      * S3FS_AWS_SECRET - AWS IAM secret
      * S3FS_AWS_BUCKET - Name of S3 bucket
      * S3FS_AWS_REGION - Region of bucket.


ACKNOWLEDGEMENT
---------------

  * Special recognition goes to justafish, author of the AmazonS3 module:
    http://drupal.org/project/amazons3

  * S3 File System started as a fork of her great module, but has evolved
    dramatically since then, becoming a very different beast. The main benefit
    of using S3 File System over AmazonS3 is performance, especially for image-
    related operations, due to the metadata cache that is central to S3 File
    System's operation.


MAINTAINERS
-----------

Current maintainers:

  * webankit (https://www.drupal.org/u/webankit)

  * coredumperror (https://www.drupal.org/u/coredumperror)

  * zach.bimson (https://www.drupal.org/u/zachbimson)

  * neerajskydiver (https://www.drupal.org/u/neerajskydiver)

  * Abhishek Anand (https://www.drupal.org/u/abhishek-anand)

  * jansete (https://www.drupal.org/u/jansete)

  * cmlara (https://www.drupal.org/u/cmlara)
