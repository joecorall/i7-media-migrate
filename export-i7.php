<?php

// path to CSV of nid/pid mappings
// first/header row: nid,pid
$csvFile = 'pids.csv';
// where this script will write a CSV to
$exportCsv = 'export.csv';
// where your i7 foxml files are stored
$OBJ_DIR="../i7";
// where your i7 datastream binaries are stored
$DATA_URI="s3://repositorydata/datastreamStore/";

// the i7 datastream Objects you want to export/import
// TODO: update numbers to actual islandora media use TIDs
$i7DataStreams = [
  "OBJ" => 1,
  "MODS" => 2,
  "OCR" => 3,
  "TN" => 4,
  "HOCR" => 5,
];

// open CSV
$handle = fopen($csvFile, 'r');
$w = fopen($exportCsv, 'w');
if ($handle === FALSE || $w === FALSE) {
  echo "Error: Unable to open the CSV file.";
  exit(1);
}

// skip header row
fgetcsv($handle);
$header = [
  'nid',
  'created',
  'mimetype',
  'size',
  'bundle',
  'uri',
  'media_use_tid',
  'file_extension',
  'file_field',
];
fputcsv($w, $header);
while (($row = fgetcsv($handle)) !== FALSE) {
  $nid = $row[0];
  $pid = $row[1];
  $xml_file = $OBJ_DIR . '/' . dereference($pid);
  if (!file_exists($xml_file)) {
    continue;
  }
  $xml = file_get_contents($xml_file);
  $xmlObject = simplexml_load_string($xml);

  foreach ($i7DataStreams as $id => $tid) {
    $obj = $xmlObject->xpath('//foxml:datastream[@ID="' . $id . '"]/foxml:datastreamVersion');
    $lastDatastreamVersion = (array)end($obj);
    $xpath = $xmlObject->xpath('//foxml:datastream[@ID="' . $id . '"]/foxml:datastreamVersion/foxml:contentLocation');
    $lastContentLocation = end($xpath);
    if (empty($lastContentLocation['REF'])) {
      continue;
    }
    $ref = (string)$lastContentLocation['REF'];
    $uri = $DATA_URI . dereference($ref);

    // grab some file metadata from the datastream object
    $created = strtotime($lastDatastreamVersion["@attributes"]['CREATED']);
    $mimetype = $lastDatastreamVersion["@attributes"]['MIMETYPE'];
    $size = $lastDatastreamVersion["@attributes"]['SIZE'];
    switch ($mimetype) {
      // TODO: add more mimetypes
      case 'text/plain':
        $bundle = 'extracted_text';
        $extension = 'txt';
        $file_field = 'field_media_file';
        break;
      case 'image/jpeg':
        $bundle = 'image';
        $extension = 'jpg';
        $file_field = 'field_media_image';
        break;
      case 'image/tiff':
        $bundle = 'file';
        $extension = 'tif';
        $file_field = 'field_media_file';
        break;
      case 'image/jp2':
        $bundle = 'file';
        $extension = 'jp2';
        $file_field = 'field_media_file';
        break;
      case 'text/xml':
      case 'application/xml':
        $bundle = 'file';
        $extension = 'xml';
        $file_field = 'field_media_file';
        break;
      case 'text/html':
        $bundle = 'file';
        $extension = 'hocr';
        $file_field = 'field_media_file';
        break;
      case 'application/pdf':
        $bundle = 'document';
        $extension = 'pdf';
        $file_field = 'field_media_document';
        break;
      case 'audio/mpeg':
        $bundle = 'audio';
        $extension = 'mp3';
        $file_field = 'field_media_audio_file';
        break;
      case 'video/mp4':
        $bundle = 'video';
        $extension = 'mp4';
        $file_field = 'field_media_video_file';
        break;
      default:
        echo "Unknown mimetype $mimetype\n";
        continue 2;
    }

    fputcsv($w, [
      $nid,
      $created,
      $mimetype,
      $size,
      $bundle,
      $uri,
      $tid,
      $extension,
      $file_field,
    ]);
  }

}
fclose($handle);
fclose($w);

// from https://github.com/discoverygarden/akubra_adapter/blob/e887885abc4d1f9fd5df47d072105f447e7e67fe/src/Utility/Fedora3/AkubraLowLevelAdapter.php#L37-L58
function dereference($id) : string {
  // Structure like: "the:pid+DSID+DSID.0"
  // Need: "{base_path}/{hash_pattern}/{id}".
  // @see https://github.com/fcrepo3/fcrepo/blob/37df51b9b857fd12c6ab8269820d406c3c4ad774/fcrepo-server/src/main/java/org/fcrepo/server/storage/lowlevel/akubra/HashPathIdMapper.java#L17-L68
  $slashed = str_replace('+', '/', $id);
  $full = "info:fedora/$slashed";
  $hash = md5($full);

  $pattern_offset = 0;
  $hash_offset = 0;
  $subbed = "##";

  while (($pattern_offset = strpos($subbed, '#', $pattern_offset)) !== FALSE) {
    $subbed[$pattern_offset] = $hash[$hash_offset++];
  }

  $encoded = strtr(rawurlencode($full), [
    '_' => '%5F',
  ]);

  return "$subbed/$encoded";
}
