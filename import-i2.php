<?php

use Drupal\Core\Session\UserSession;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\user\Entity\User;

// path to CSV created by export-i7.php
$csvFile = 'export.csv';
// TODO: "Original File" islandora media use taxonomy term ID
$original_file_tid = 123;

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

// skip header row
fgetcsv($handle);

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

  // make sure we haven't processed this URI before
  $fid = \Drupal::database()->query('SELECT fid FROM {file_managed} WHERE uri = :uri', [
    ':uri' => $uri,
  ])->fetchField();
  if ($fid) {
    continue;
  }

  $file = File::create([
    'filename' => $nid . '.' . $extension,
    'uri' => $uri,
    'status' => 1,
    'created' => $created,
  ]);
  $file->save();
  $media = Media::create([
    'name' => $nid . ' - Original File',
    'bundle' => $bundle,
    $file_field => $file->id(),
    'field_media_of' => $nid,
    'field_media_use' => $original_file_tid,
    'status' => 1,
    'created' => $created,
  ]);
  $media->save();
}
fclose($handle);