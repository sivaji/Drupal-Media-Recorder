<?php
namespace Drupal\media_recorder;

/**
 * Default controller for the media_recorder module.
 */
class DefaultController extends ControllerBase {

  public function media_recorder_record_file() {

    // Validate that temp file was created.
    if (!isset($_FILES['mediaRecorder']['tmp_name']) || empty($_FILES['mediaRecorder']['tmp_name'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('No file was sent.');
      return;
    }

    // Validate that a upload location was sent.
    if (!isset($_POST['mediaRecorderUploadLocation']) || empty($_POST['mediaRecorderUploadLocation'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('Missing configuration.');
      return;
    }

    // Create a new temporary file to save data.
    try {
      $uri = \Drupal::service("file_system")->tempnam('temporary://', 'mediaRecorder_');
      if (!$uri) {
        throw new Exception("Unable to create temporary file.");
      }
    }
    

      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Get file data.
    try {
      $data = file_get_contents($_FILES['mediaRecorder']['tmp_name']);
      if (!$data) {
        throw new Exception("There was no data sent.");
      }
    }
    

      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Open a new file.
    try {
      $fp = fopen($uri, 'a');
      if (!$fp) {
        throw new Exception("Unable to open temporary file. Please check that your file permissions are set correctly.");
      }
    }
    

      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Write to file.
    if ($data && $fp) {
      try {
        fwrite($fp, $data);
        fclose($fp);
      }
      

        catch (Exception $e) {
        header('HTTP/1.0 419 Custom Error');
        drupal_json_output($e->getMessage());
        return;
      }
    }

    // Change the file name and save to upload location.
    try {
      if (file_prepare_directory($_POST['mediaRecorderUploadLocation'], FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        $target = $_POST['mediaRecorderUploadLocation'] . '/' . uniqid('mediaRecorder_') . '.wav';
        file_unmanaged_move($uri, $target);
        $file = file_uri_to_object($target);
        $file->status = 0;
        file_save($file);
      }
      else {
        throw new Exception("Unable to save recording to directory.");
      }
    }
    

      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Return file information.
    drupal_json_output($file);
  }

  public function media_recorder_record_kaltura_token() {
    $settings = media_recorder_get_settings();

    // Validate that a upload location was sent.
    if (!isset($_POST['uploadTokenId']) || empty($_POST['uploadTokenId'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('Missing configuration.');
      return;
    }

    // Validate that a upload location was sent.
    if (!isset($_POST['mimetype']) || empty($_POST['mimetype'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('Missing configuration.');
      return;
    }

    // Check that Kaltura upload has been enabled.
    if (!isset($settings['kaltura']['enable']) || !$settings['kaltura']['enable'] || !isset($settings['kaltura']['server']) || !$settings['kaltura']['server']) {
      return;
    }

    // Attempt to start a Kaltura session.
    try {

      // Load the default Kaltura server.
      $server = media_kaltura_server_load($settings['kaltura']['server']);
      if (!$server) {
        throw new Exception('Unable to load Kaltura server.');
      }

      // Start a new session with the Kaltura server.
      $kaltura = media_kaltura_start_session($server);
      if (!$kaltura) {
        throw new Exception('Unable to start Kaltura session.');
      }
    }
    
      catch (Exception $e) {
      \Drupal::logger('media_kaltura')->error('There was a problem connecting to the kaltura server: @error', [
        '@error' => $e->getMessage()
        ]);
    }

    // Add a Kaltura media entry from the upload token.
    try {
      $token = $kaltura['client']->uploadToken->get($_POST['uploadTokenId']);
      $type = substr($_POST['mimetype'], 0, strpos($_POST['mimetype'], '/'));

      // Create a new media entry and set values.
      $entry = new KalturaMediaEntry();
      $entry->name = $token->fileName;
      $entry->mediaType = ($type === 'audio') ? KalturaMediaType::AUDIO : KalturaMediaType::VIDEO;

      // Set default profile if enabled.
      if (isset($settings['kaltura']['profile']) && $settings['kaltura']['profile']) {
        $entry->conversionProfileId = $settings['kaltura']['profile'];
      }

      // Set default category if enabled.
      if (isset($settings['kaltura']['category']) && $settings['kaltura']['category']) {
        $entry->categoriesIds = $settings['kaltura']['category'];
      }

      // Upload the file and create a new Kaltura media entry.
      $entry = $kaltura['client']->media->addFromUploadedFile($entry, $token->id);

      // Create a new file.
      if ($entry) {
        $file = file_uri_to_object('kaltura://' . $server->domain . '/' . $server->partner_id . '/' . $server->subpartner_id . '/' . $server->uiconf_id . '/' . $entry->id);
        $file->type = $type;
        $file->filemime = $type . '/kaltura';
        $file->status = 0;
        $file->filesize = 0;
        file_save($file);
      }

      // Allow modules to act on entry create or update operation.
      \Drupal::moduleHandler()->invokeAll('media_kaltura_entry', [
        $entry,
        $file,
        $kaltura['client'],
      ]);
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Return file information.
    drupal_json_output($file);
  }

  public function media_recorder_record_kaltura_entry() {
    $settings = media_recorder_get_settings();

    // Validate that a upload location was sent.
    if (!isset($_POST['entries']) || empty($_POST['entries'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('Missing configuration.');
      return;
    }

    // Check that Kaltura upload has been enabled.
    if (!isset($settings['kaltura']['enable']) || !$settings['kaltura']['enable'] || !isset($settings['kaltura']['server']) || !$settings['kaltura']['server']) {
      return;
    }

    // Attempt to start a Kaltura session.
    try {

      // Load the default Kaltura server.
      $server = media_kaltura_server_load($settings['kaltura']['server']);
      if (!$server) {
        throw new Exception('Unable to load Kaltura server.');
      }

      // Start a new session with the Kaltura server.
      $kaltura = media_kaltura_start_session($server);
      if (!$kaltura) {
        throw new Exception('Unable to start Kaltura session.');
      }
    }
    
      catch (Exception $e) {
      \Drupal::logger('media_kaltura')->error('There was a problem connecting to the kaltura server: @error', [
        '@error' => $e->getMessage()
        ]);
    }

    // Add a Kaltura media entry from the upload token.
    try {
      $entries = json_decode($_POST['entries']);

      foreach ($entries as $entry) {

        // Save the entry with a default category if this is enabled.
        if ($settings['kaltura']['category']) {
          $categoryEntry = new KalturaCategoryEntry();
          $categoryEntry->categoryId = $settings['kaltura']['category'];
          $categoryEntry->entryId = $entry->id;
          $kaltura['client']->categoryEntry->add($categoryEntry);
        }

        // Create a new file associated with this entry.
        if ($entry) {
          $file = file_uri_to_object('kaltura://' . $server->domain . '/' . $server->partner_id . '/' . $server->subpartner_id . '/' . $server->uiconf_id . '/' . $entry->id);
          $file->type = ($entry->type == 1 ? 'video' : 'audio');
          $file->filemime = $file->type . '/kaltura';
          $file->status = 0;
          $file->filesize = 0;
          file_save($file);
        }
      }
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Return file information.
    $file_view = file_view($file);
    unset($file_view['links']);
    unset($file_view['#contextual_links']);
    drupal_json_output([
      'file' => $file,
      'entry' => $entry,
      'preview' => \Drupal::service("renderer")->render($file_view),
    ]);
  }

  public function media_recorder_record_stream_start() {

    // Reset session.
    $_SESSION['media_recorder'] = [];

    // Create a new temporary file to save streamed data.
    try {
      $_SESSION['media_recorder']['tempnam'] = \Drupal::service("file_system")->tempnam('temporary://', 'mediaRecorder_');
      if (!$_SESSION['media_recorder']['tempnam']) {
        throw new Exception("Unable to create temporary file.");
      }
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Get file format.
    try {
      $_SESSION['media_recorder']['format'] = $_POST['format'];
      if (!$_SESSION['media_recorder']['format']) {
        throw new Exception("Unable to get file format.");
      }
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }
  }

  public function media_recorder_record_stream_record() {

    // Validate that temp file was created.
    if (!isset($_SESSION['media_recorder']['tempnam']) || empty($_SESSION['media_recorder']['tempnam'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('Recording session not initiated.');
      return;
    }

    // Validate that blob sequence count was sent.
    if (!isset($_REQUEST['count']) || !is_numeric($_REQUEST['count'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('Stream sequence count invalid.');
      return;
    }

    // Validate that blob exists.
    if (!isset($_FILES['blob']['tmp_name']) || empty($_FILES['blob']['tmp_name'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('Stream invalid.');
      return;
    }

    // Get data from blob.
    try {
      $data = file_get_contents($_FILES['blob']['tmp_name']);
      if (!$data) {
        throw new Exception("Streaming data file is empty.");
      }
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Open new temp file.
    try {
      $fp = fopen($_SESSION['media_recorder']['tempnam'] . $_REQUEST['count'], 'a');
      if (!$fp) {
        throw new Exception("Unable to open temporary file. Please check that your file permissions are set correctly.");
      }
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Write blob to temp file.
    try {
      fwrite($fp, $data);
      fclose($fp);
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Send success response.
    drupal_json_output([
      'count' => $_REQUEST['count'],
      'blob' => $_FILES['blob'],
      'tempnam' => $_SESSION['media_recorder']['tempnam'] . $_REQUEST['count'],
    ]);
  }

  public function media_recorder_record_stream_finish() {

    // Validate that temp file was created.
    if (!isset($_SESSION['media_recorder']['tempnam']) || empty($_SESSION['media_recorder']['tempnam'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('No file found.');
      return;
    }

    // Validate that a upload location was sent.
    if (!isset($_POST['mediaRecorderUploadLocation']) || empty($_POST['mediaRecorderUploadLocation'])) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output('Missing configuration.');
      return;
    }

    // Get all file chunks.
    try {
      $files = file_scan_directory('temporary://', '/' . file_uri_target($_SESSION['media_recorder']['tempnam']) . '[0-9]+$/');
      if (!$files) {
        throw new Exception("Unable to get recorded streams. Please check that your file permissions are set correctly.");
      }
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Sort files in case they are out of order.
    $files = array_keys($files);
    natsort($files);

    // Open temp file.
    try {
      $fp = fopen($_SESSION['media_recorder']['tempnam'], 'a');
      if (!$fp) {
        throw new Exception("Unable to open temporary file. Please check that your file permissions are set correctly.");
      }
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Iterate over file list and append to temp file.
    foreach ($files as $filename) {

      // Get data from file.
      try {
        $data = file_get_contents($filename);
        if (!$data) {
          throw new Exception("Streaming data file is empty.");
        }
      }
      
        catch (Exception $e) {
        header('HTTP/1.0 419 Custom Error');
        drupal_json_output($e->getMessage());
        return;
      }

      // Append data to temp file.
      try {
        fwrite($fp, $data);
      }
      
        catch (Exception $e) {
        header('HTTP/1.0 419 Custom Error');
        drupal_json_output($e->getMessage());
        return;
      }

      // Delete file chunk.
      try {
        unlink($filename);
      }
      
        catch (Exception $e) {
        header('HTTP/1.0 419 Custom Error');
        drupal_json_output($e->getMessage());
        return;
      }
    }

    // Close temp file.
    try {
      fclose($fp);
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Change the file name and save to upload location.
    try {
      if (file_prepare_directory($_POST['mediaRecorderUploadLocation'], FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        $uri = $_SESSION['media_recorder']['tempnam'];
        $target = $_POST['mediaRecorderUploadLocation'] . '/' . uniqid('mediaRecorder_') . '.' . $_SESSION['media_recorder']['format'];
        file_unmanaged_move($uri, $target);
        $file = file_uri_to_object($target);
        $file->status = 0;
        file_save($file);
      }
      else {
        throw new Exception("Unable to save recording to directory.");
      }
    }
    
      catch (Exception $e) {
      header('HTTP/1.0 419 Custom Error');
      drupal_json_output($e->getMessage());
      return;
    }

    // Close session.
    unset($_SESSION['media_recorder']);

    // Return file information.
    drupal_json_output($file);
  }

}
