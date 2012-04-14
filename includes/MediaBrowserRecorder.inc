<?php

/**
 * @file
 * Definition of MediaBrowserInternet.
 */

/**
 * Media browser plugin for Media Internet sources.
 */
class MediaBrowserRecorder extends MediaBrowserPlugin {
  /**
   * Implements MediaBrowserPluginInterface::access().
   */
  public function access($account = NULL) {
    return media_internet_access($account);
  }

  /**
   * Implements MediaBrowserPlugin::view().
   */
  public function view() {
    $build['form'] = drupal_get_form('media_recorder_add');
    return $build;
  }
}
