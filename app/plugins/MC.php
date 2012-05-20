<?php
/**
 * Wiz
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 * 
 * This program is provided to you AS-IS.  There is no warranty.  It has not been
 * certified for any particular purpose.
 *
 * @package    Wiz
 * @author     Nick Vahalik <nick@classyllama.com>
 * @copyright  Copyright (c) 2012 Classy Llama Studios, LLC
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Magento Connect Plugin for Wiz
 *
 * @author Nicholas Vahalik <nick@classyllama.com>
 */
class Wiz_Plugin_MC extends Wiz_Plugin_Abstract {

  /**
   * Downloads a Magento Connect package.
   *
   * @param Magento Connect 1.0 or 2.0 Key
   * @author Nicholas Vahalik <nick@classyllama.com>
   **/
  public function dlAction($options) {
    if (count($options) == 0) {
      echo 'Please supply a Magento Connect 1.0 or 2.0 key.';
    }

    $key = $options[0];
    $selectedVersion = 'latest';
    $selectedBranch = 'stable';

    if (strpos($key, 'connect20') !== FALSE) {
      $extensionKey = substr($key, strrpos($key, '/') + 1);

      if (($releases = $this->_getReleaseInformation($key)) !== FALSE) {
        $latestRelease = array_pop($releases);
        $fileSource = $key . '/' . $latestRelease['v'] . '/' . $extensionKey . '-' . $latestRelease['v'] . '.tgz';
        $fileDestination = $extensionKey . '-' . $latestRelease['v'] . '.tgz';
        $this->downloadFile($fileSource, $fileDestination);
      }
      else {
        throw new Exception('Unable to find release information.  Did you pass the right URL?');
      }
    }
    else {
      throw new Exception('Only support MC 2.0 keys at this time.  Sorry!');
    }
  }

  /**
   * Get the versions that are available on Magento connect for a given Magento Connect key.
   *
   * @param Magento Connect 1.0 or 2.0 Key.
   * @author Nicholas Vahalik <nick@classyllama.com>
   */
  public function versionsAction($options) {
    if (count($options) == 0) {
      echo 'Please supply a Magento Connect 1.0 or 2.0 key.';
    }

    $key = $options[0];

    if (strpos($key, 'connect20') !== FALSE) {
      if (($releases = $this->_getReleaseInformation($key)) !== FALSE) {
        $releases = array_reverse($releases);
        foreach ($releases as $d => $a) {
          $releases[$d]['Version'] = $a['v'];
          $releases[$d]['Status']  = $a['s'];
          $releases[$d]['Date']    = $a['d'];
          unset($releases[$d]['v']);
          unset($releases[$d]['d']);
          unset($releases[$d]['s']);
        }
        echo Wiz::tableOutput($releases);
      }
      else {
        throw new Exception('Unable to find release information.  Did you pass the right URL?');
      }
    }
    else {
      throw new Exception('Only support MC 2.0 keys at this time.  Sorry!');
    }
  }

  public function _getReleaseInformation($key) {
    if (($content = file_get_contents($key . '/releases.xml')) !== FALSE) {
      $releaseXmlData = simplexml_load_string($content);
      foreach ($releaseXmlData->r as $entry) {
        $releases[] = (array)$entry;
      }

      usort($releases, array($this, '_sortReleases'));

      return $releases;
    }

    return FALSE;
  }

  private function _sortReleases($r1, $r2) {
    return $r1['d'] > $r2['d'];
  }

  private function downloadFile($source, $destination) {
      $data = null;
      $chars = array('-', '\\', '|', '/');
      $totalChars = count($chars);
      $iteration = $downloadedSoFar = $removableChars = 0;

      $fileSize = $this->_getDownloadSize($source);

      printf('Downloading %s...' . PHP_EOL, $source);
      $fileDownloadHandler = fopen($source, 'rb');

      if (!$fileDownloadHandler) {
          return FALSE;
      }

      while (!feof($fileDownloadHandler)) {
          $downloadedSoFar = strlen($data);
          echo str_repeat("\x08", $removableChars);
          if ($fileSize) {
              $removableChars = printf("%s %d%%", $chars[$iteration++ % $totalChars], ($downloadedSoFar / $fileSize) * 100);
          }
          else {
              $removableChars = printf("%s %.2fMB downloaded", $chars[$iteration++ % $totalChars], $downloadedSoFar / 1024 / 1024);
          }
          $data .= fread($fileDownloadHandler, 64000);
      }

      echo str_repeat("\x08", $removableChars);
      file_put_contents($destination, $data);

      print('done.' . PHP_EOL);
      return true;
  }

  private function _getDownloadSize($httpFile) {
      $headers = @get_headers($httpFile);
      foreach ($headers as $header) {
          if (substr($header, 0, 14) == 'Content-Length') {
              return (int)substr($header, 16);
          }
      }
      return 0;
  }
}