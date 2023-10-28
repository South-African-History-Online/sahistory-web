<?php

namespace Drupal\uc_file\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * The number of bogus requests one IP address can make before being banned.
 */
define('UC_FILE_REQUEST_LIMIT', 50);

/**
 * Download file chunk.
 */
define('UC_FILE_BYTE_SIZE', 8192);

/**
 * Download statuses.
 */
define('UC_FILE_ERROR_OK'                     , 0);
define('UC_FILE_ERROR_NOT_A_FILE'             , 1);
define('UC_FILE_ERROR_TOO_MANY_BOGUS_REQUESTS', 2);
define('UC_FILE_ERROR_INVALID_DOWNLOAD'       , 3);
define('UC_FILE_ERROR_TOO_MANY_LOCATIONS'     , 4);
define('UC_FILE_ERROR_TOO_MANY_DOWNLOADS'     , 5);
define('UC_FILE_ERROR_EXPIRED'                , 6);
define('UC_FILE_ERROR_HOOK_ERROR'             , 7);

/**
 * Handles administrative view of files that may be purchased and downloaded.
 */
class DownloadController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a DownloadController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->formBuilder = $form_builder;
  }

  /**
   * Table builder for user downloads.
   */
  public function userDownloads(AccountInterface $user) {
    // Create a header and the pager it belongs to.
    $header = array(
      array('data' => $this->t('Purchased'  ), 'field' => 'u.granted', 'sort' => 'desc'),
      array('data' => $this->t('Filename'   ), 'field' => 'f.filename'),
      array('data' => $this->t('Description'), 'field' => 'p.description'),
      array('data' => $this->t('Downloads'  ), 'field' => 'u.accessed'),
      array('data' => $this->t('Addresses'  )),
    );

    $build['#title'] = $this->t('File downloads');

    $files = array();

    $query = $this->database->select('uc_file_users', 'u')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->condition('uid', $user->id())
      ->orderByHeader($header)
      ->limit(UC_FILE_PAGER_SIZE);
    $query->leftJoin('uc_files', 'f', 'u.fid = f.fid');
    $query->leftJoin('uc_file_products', 'p', 'p.pfid = u.pfid');
    $query->fields('u', array(
        'granted',
        'accessed',
        'addresses',
        'file_key',
        'download_limit',
        'address_limit',
        'expiration',
      ))
      ->fields('f', array(
        'filename',
        'fid',
      ))
      ->fields('p', array('description'));

    $count_query = $this->database->select('uc_file_users')
      ->condition('uid', $user->id());
    $count_query->addExpression('COUNT(*)');

    $query->setCountQuery($count_query);

    $result = $query->execute();

    $row = 0;
    foreach ($result as $file) {

      $download_limit = $file->download_limit;

      // Set the JS behavior when this link gets clicked.
      $onclick = array(
        'attributes' => array(
          'onclick' => 'Drupal.behaviors.ucFileUpdateDownload(' . $row . ', ' . $file->accessed . ', ' . ((empty($download_limit)) ? -1 : $download_limit) . ');', 'id' => 'link-' . $row
        ),
      );

      // Expiration set to 'never'
      if ($file->expiration == FALSE) {
        $file_link = Link::createFromRoute(\Drupal::service('file_system')->basename($file->filename), 'uc_file.download_file', ['file' => $file->fid], $onclick)->toString();
      }

      // Expired.
      elseif (REQUEST_TIME > $file->expiration) {
        $file_link = \Drupal::service('file_system')->basename($file->filename);
      }

      // Able to be downloaded.
      else {
        $file_link = Link::createFromRoute(\Drupal::service('file_system')->basename($file->filename), 'uc_file.download_file', ['file' => $file->fid], $onclick)->toString() . ' (' . $this->t('expires on @date', ['@date' => \Drupal::service('date.formatter')->format($file->expiration, 'uc_store')]) . ')';
      }

      $files[] = array(
        'granted' => $file->granted,
        'link' => $file_link,
        'description' => $file->description,
        'accessed' => $file->accessed,
        'download_limit' => $file->download_limit,
        'addresses' => $file->addresses,
        'address_limit' => $file->address_limit,
      );
      $row++;
    }

    $build['downloads'] = array(
      '#theme' => 'uc_file_user_downloads',
      '#header' => $header,
      '#files' => $files,
    );

    if (\Drupal::currentUser()->hasPermission('administer users')) {
      $build['admin'] = $this->formBuilder()->getForm('Drupal\uc_file\Form\UserForm', $user);
    }

    return $build;
  }

  /**
   * Checks access for a list of the user's purchased file downloads.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessUserDownloads(AccountInterface $account) {
    $user = \Drupal::currentUser();
    return AccessResult::allowedIf(
      $user->id() &&
     ($user->hasPermission('view all downloads') || $user->id() == $account->id())
    );
  }

  /**
   * Handles file downloading and error states.
   *
   * @param int $fid
   *   The fid of the file specified to download.
   * @param string $key
   *   The hash key of a user's download.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   */
  public function download($fid, Request $request) {
    $user = \Drupal::currentUser();

    // Error messages for various failed download states.
    $admin_message = $this->t('Please contact the site administrator if this message has been received in error.');
    $error_messages = array(
      UC_FILE_ERROR_NOT_A_FILE              => $this->t('The file you requested does not exist.'),
      UC_FILE_ERROR_TOO_MANY_BOGUS_REQUESTS => $this->t('You have attempted to download an incorrect file URL too many times.'),
      UC_FILE_ERROR_INVALID_DOWNLOAD        => $this->t('The following URL is not a valid download link.') . ' ',
      UC_FILE_ERROR_TOO_MANY_LOCATIONS      => $this->t('You have downloaded this file from too many different locations.'),
      UC_FILE_ERROR_TOO_MANY_DOWNLOADS      => $this->t('You have reached the download limit for this file.'),
      UC_FILE_ERROR_EXPIRED                 => $this->t('This file download has expired.') . ' ',
      UC_FILE_ERROR_HOOK_ERROR              => $this->t('A hook denied your access to this file.') . ' ',
    );

    $ip = $request->getClientIp();
    if ($user->hasPermission('view all downloads')) {
      $file_download = uc_file_get_by_id($fid);
    }
    else {
      $file_download = uc_file_get_by_uid($user->id(), $fid);
    }

    if (isset($file_download->filename)) {
      $file_download->full_path = uc_file_qualify_file($file_download->filename);
    }
    else {
      throw new AccessDeniedHttpException();
    }

    // If it's ok, we push the file to the user.
    $status = UC_FILE_ERROR_OK;
    if (!$user->hasPermission('view all downloads')) {
      $status = $this->validateDownload($file_download, $user, $ip);
    }
    if ($status == UC_FILE_ERROR_OK) {
      $this->transferDownload($file_download, $ip);
    }

    // Some error state came back, so report it.
    else {
      drupal_set_message($error_messages[$status] . $admin_message, 'error');

      // Kick 'em to the curb. >:)
      $this->redirectDownload($user->id());
    }

    drupal_exit();
  }

  /**
   * Performs first-pass authorization. Calls authorization hooks afterwards.
   *
   * Called when a user requests a file download, function checks download
   * limits then checks for any implementation of hook_uc_download_authorize().
   * Passing that, the function $this->transferDownload() is called.
   *
   * @param int $fid
   *   The fid of the file specified to download.
   * @param string $key
   *   The hash key of a user's download.
   */
  protected function validateDownload($file_download, &$user, $ip) {

    $request_cache = cache()->get('uc_file_' . $ip);
    $requests = ($request_cache) ? $request_cache->data + 1 : 1;

    $message_user = ($user->id()) ? $this->t('The user %username', ['%username' => $user->getUsername()]) : $this->t('The IP address %ip', ['%ip' => $ip]);

    if ($requests > UC_FILE_REQUEST_LIMIT) {
      return UC_FILE_ERROR_TOO_MANY_BOGUS_REQUESTS;
    }

    // Must be a valid file.
    if (!$file_download || !is_readable($file_download->full_path)) {
      cache()->set('uc_file_' . $ip, $requests, REQUEST_TIME + 86400);
      if ($requests == UC_FILE_REQUEST_LIMIT) {
        // $message_user has already been sanitized.
        \Drupal::logger('uc_file')->warning('@username has been temporarily banned from file downloads.', ['@username' => $message_user]);
      }

      return UC_FILE_ERROR_INVALID_DOWNLOAD;
    }

    $addresses = $file_download->addresses;

    // Check the number of locations.
    if (!empty($file_download->address_limit) && !in_array($ip, $addresses) && count($addresses) >= $file_download->address_limit) {
      // $message_user has already been sanitized.
      \Drupal::logger('uc_file')->warning('@username has been denied a file download by downloading it from too many IP addresses.', ['@username' => $message_user]);

      return UC_FILE_ERROR_TOO_MANY_LOCATIONS;
    }

    // Check the downloads so far.
    if (!empty($file_download->download_limit) && $file_download->accessed >= $file_download->download_limit) {
      // $message_user has already been sanitized.
      \Drupal::logger('uc_file')->warning('@username has been denied a file download by downloading it too many times.', ['@username' => $message_user]);

      return UC_FILE_ERROR_TOO_MANY_DOWNLOADS;
    }

    // Check if it's expired.
    if ($file_download->expiration && REQUEST_TIME >= $file_download->expiration) {
      // $message_user has already been sanitized.
      \Drupal::logger('uc_file')->warning('@username has been denied an expired file download.', ['@username' => $message_user]);

      return UC_FILE_ERROR_EXPIRED;
    }

    // Check any if any hook_uc_download_authorize() calls deny the download
    $module_handler = $this->moduleHandler();
    foreach ($module_handler->getImplementations('uc_download_authorize') as $module) {
      $name = $module . '_uc_download_authorize';
      $result = $name($user, $file_download);
      if (!$result) {
        return UC_FILE_ERROR_HOOK_ERROR;
      }
    }

    // Everything's ok!
    // $message_user has already been sanitized.
    \Drupal::logger('uc_file')->notice('@username has started download of the file %filename.', ['@username' => $message_user, '%filename' => \Drupal::service('file_system')->basename($file_download->filename)]);
  }

  /**
   * Sends the file's binary data to a user via HTTP and updates the database.
   *
   * @param $file_user
   *   The file_user object from the uc_file_users.
   * @param string $ip
   *   The string containing the IP address the download is going to.
   */
  protected function transferDownload($file_user, $ip) {

    // Create the response.
    $response = new BinaryFileResponse();

    // Check if any hook_uc_file_transfer_alter() calls alter the download.
    $module_handler = $this->moduleHandler();
    foreach ($module_handler->getImplementations('uc_file_transfer_alter') as $module) {
      $name = $module . '_uc_file_transfer_alter';
      $file_user->full_path = $name($file_user, $ip, $file_user->fid, $file_user->full_path);
    }

    // This could get clobbered, so make a copy.
    $filename = $file_user->filename;

    // Gather relevant info about the file.
    $size = filesize($file_user->full_path);
    $mimetype = file_get_mimetype($filename);

    // Workaround for IE filename bug with multiple periods / multiple dots
    // in filename that adds square brackets to filename -
    // eg. setup.abc.exe becomes setup[1].abc.exe
    if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
      $filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
    }

    // Check if HTTP_RANGE is sent by browser (or download manager).
    $range = NULL;
    if (isset($_SERVER['HTTP_RANGE'])) {
      if (substr($_SERVER['HTTP_RANGE'], 0, 6) == 'bytes=') {
        // Multiple ranges could be specified at the same time,
        // but for simplicity only serve the first range
        // See http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
        list($range, $extra_ranges) = explode(',', substr($_SERVER['HTTP_RANGE'], 6), 2);
      }
      else {
        $response->headers->set('Status', '416 Requested Range Not Satisfiable');
        $response->headers->set('Content-Range', 'bytes */' . $size);
        return $response;
        ;
      }
    }

    // Figure out download piece from range (if set).
    if (isset($range)) {
      list($seek_start, $seek_end) = explode('-', $range, 2);
    }

    // Set start and end based on range (if set),
    // else set defaults and check for invalid ranges.
    $seek_end = intval((empty($seek_end)) ? ($size - 1) : min(abs(intval($seek_end)), ($size - 1)));
    $seek_start = intval((empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)), 0));

    // Only send partial content header if downloading a piece of the file (IE
    // workaround).
    if ($seek_start > 0 || $seek_end < ($size - 1)) {
      $response->headers->set('Status', '206 Partial Content');
    }

    // Standard headers, including content-range and length
    $response->headers->set('Pragma', 'public');
    $response->headers->set('Cache-Control', 'cache, must-revalidate');
    $response->headers->set('Accept-Ranges', 'bytes');
    $response->headers->set('Content-Range', 'bytes ' . $seek_start . '-' . $seek_end . '/' . $size);
    $response->headers->set('Content-Type', $mimetype);
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->headers->set('Content-Length', $seek_end - $seek_start + 1);

    // Last-Modified is required for content served dynamically.
    $response->headers->set('Last-Modified', gmdate("D, d M Y H:i:s", filemtime($file_user->full_path)) . " GMT");

    // Etag header is required for Firefox3 and other managers.
    $response->headers->set('ETag', md5($file_user->full_path));

    // Open the file and seek to starting byte.
    $fp = fopen($file_user->full_path, 'rb');
    fseek($fp, $seek_start);

    // Start buffered download.
    while (!feof($fp)) {

      // Reset time limit for large files.
      drupal_set_time_limit(0);

      // Push the data to the client.
      print(fread($fp, UC_FILE_BYTE_SIZE));
      flush();

      // Suppress PHP notice that occurs when output buffering isn't enabled.
      // The ob_flush() is needed because if output buffering *is* enabled,
      // clicking on the file download link won't download anything if the buffer
      // isn't flushed.
      @ob_flush();
    }

    // Finished serving the file, close the stream and log the download
    // to the user table.
    fclose($fp);

    $this->logDownload($file_user, $ip);

  }

  /**
   * Processes a file download.
   *
   * @param $file_user
   * @param string $ip
   */
  protected function logDownload($file_user, $ip) {

    // Add the address if it doesn't exist.
    $addresses = $file_user->addresses;
    if (!in_array($ip, $addresses)) {
      $addresses[] = $ip;
    }
    $file_user->addresses = $addresses;

    // Accessed again.
    $file_user->accessed++;

    // Calculate hash
    $file_user->file_key = \Drupal::csrfToken()->get(serialize($file_user));

    $key = NULL;
    if (isset($file_user['fuid'])) {
      $key = $file_user['fuid'];
    }

    // Insert or update (if $key is already in table) uc_file_users table.
    db_merge('uc_file_users')
      ->key(['fuid' => $key])
      ->fields($file_user)
      ->execute();
  }

  /**
   * Send 'em packin.
   *
   * @param int $uid
   */
  protected function redirectDownload($uid = NULL) {

    // Shoo away anonymous users.
    if ($uid == 0) {
      throw new AccessDeniedHttpException();
    }
    // Redirect users back to their file page.
    else {
      if (!headers_sent()) {
        return new RedirectResponse(Url::fromRoute('uc_file.user_downloads', ['user' => $uid]));
      }
    }
  }

}
