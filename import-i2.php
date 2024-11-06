<?php

use Drupal\Core\Session\UserSession;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\user\Entity\User;
use Drupal\s3fs\StreamWrapper\S3fsStream;

// path to CSV created by export-i7.php
$path = '/var/www/drupal/web/scripts/i7-media-migrate/';
$pass_in = $_SERVER['argv'][5];
print "Working on csv " . $pass_in . "\n";
$csvFile = $path . $pass_in;

// login as uid=1
$userid = 1;
$account = User::load($userid);
$accountSwitcher = Drupal::service('account_switcher');
$userSession = new UserSession([
  'uid'   => $account->id(),
  'name'  => $account->getDisplayName(),
  'roles' => $account->getRoles(),
]);
$accountSwitcher->switchTo($userSession);

// open CSV
$handle = fopen($csvFile, 'r');
if ($handle === FALSE) {
  echo "Error: Unable to open the CSV file.";
  exit(1);
}


$count = 1;
$total_time_start = microtime(true);

while (($row = fgetcsv($handle)) !== FALSE) {
  $nid = $row[0];
  $created = $row[1];
  $mimetype = $row[2];
  $size = $row[3];
  $bundle = $row[4];
  $uri = $row[5];
  $media_use = $row[6];
  $extension = $row[7];
  $file_field = $row[8];
  $height = $row[9];
  $width = $row[10];

  $time_start = microtime(true);
  print "Starting NID $nid on row $count \n";

  // make sure we haven't processed this URI before
  $fid = \Drupal::database()->query('SELECT fid FROM {file_managed} WHERE uri = :uri', [
    ':uri' => $uri,
  ])->fetchField();
  if ($fid) {
    continue;
  }

  $name = $nid . '.' . $extension;
  $file = File::create([
    'filename' => $name,
    'uri' => $uri,
    'status' => 1,
    'created' => $created,
    'filesize' => $size,
    'filemime' => $mimetype,
  ]);
  $file->save();

  $uri = $file->getFileUri();
  $stream = new S3fsStream();
  $stream->writeUriToCache($uri);

  if ($height) {
    $media = Media::create([
      'name' => $name,
      'bundle' => $bundle,
      $file_field => $file->id(),
      'field_media_of' => $nid,
      'field_media_use' => $media_use,
      'field_height' => $height,
      'field_width' => $width,
      'status' => 1,
      'created' => $created,
    ]);
    $media->save();
  } else {
    $media = Media::create([
      'name' => $name,
      'bundle' => $bundle,
      $file_field => $file->id(),
      'field_media_of' => $nid,
      'field_media_use' => $media_use,
      'status' => 1,
      'created' => $created,
    ]);
    $media->save();
  }

  $count += 1;
  $time_end = microtime(true);
  $time = $time_end - $time_start;
  print "Finished NID $nid in $time \n";
}
fclose($handle);

$total_time_stop = microtime(true);
$total_time = $total_time_stop - $total_time_start;
print "Finished batch migration in $total_time \n";
