<?php

// path to CSV of nid/pid mappings
// first/header row: nid,pid
$csvFile = 'pids.csv';
// where this script will write a CSV to
$exportCsv = 'export.csv';
// where your i7 foxml files are stored
$OBJ_DIR="../i7";
// where your i7 datastream binaries are stored
$DATA_URI="private://";

// the i7 datastream Objects you want to export/import
//Default
$i7DataStreamsDefault = [
  "OBJ" => 3,
  "MODS" => 34,
  "OCR" => 1,
  "TN" => 6,
  "HOCR" => 3412,
  "DC" => 3410,
  "TECHMD" => 19,
  "JP2" => 3411,
];

$bookCModel = [
  "DC" => 3410,
  "MODS" => 34,
  "OCR" => 1,
  "PDF" => 3413,
  "TN" => 6,
];

$compoundCModel = [
  "DC" => 3410,
  "DEED" => 3415,
  "MODS" => 34,
  "TN" => 6,
];

$newspaperPageCModel = [
  "DC" => 3410,
  "HOCR" => 3412,
  "JP2" => 3411,
  "JPG" => 5,
  "MODS" => 34,
  "OBJ" => 3,
  "OCR" => 1,
  "TECHMD" => 19,
  "TN" => 6,
];

$oralhistoriesCModel = [
  "DC" => 3410,
  "MODS" => 34,
  "OBJ" => 3,
  "TECHMD" => 19,
  "TN" => 6,
  "TRANSCRIPT_WORD" => 3413,
];

$sp_basic_image = [
  "DC" => 3410,
  "MODS" => 34,
  "OBJ" => 3,
  "TECHMD" => 19,
  "TN" => 6,
];

$sp_large_image_cmodel = [
  "DC" => 3410,
  "JP2" => 3411,
  "JPG" => 5,
  "MODS" => 34,
  "OBJ" => 3,
  "TECHMD" => 19,
  "TIFF" => 3,
  "TN" => 6,
];

$sp_pdf = [
  "DC" => 3410,
  "FULL_TEXT" => 1,
  "MODS" => 34,
  "OBJ" => 3,
  "TECHMD" => 19,
  "TN" => 6,
];

$sp_streaming = [
  "DC" => 3410,
  "MODS" => 34,
  "OBJ" => 3,
  "TECHMD" => 19,
  "TN" => 6,
];

$sp_videoCModel = [
  "DC" => 3410,
  "MODS" => 34,
  "OBJ" => 3,
  "TECHMD" => 19,
  "TN" => 6,
];

$sp_audioCModel = [
  "DC" => 3410,
  "MODS" => 34,
  "OBJ" => 3,
  "TECHMD" => 19,
  "TN" => 6,
];

$i7DataStreams = [];

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
  'field_height',
  'field_width',
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

  echo $xml_file, "\n" ;

  // PUT IN CHECK FOR MODEL THEN USE THE RIGHT MEDIA USE SETUP
  // USE this to check model, then clean up string for model, then assign right model/media use
  if ($rels_ext = $xmlObject->xpath("/foxml:digitalObject/foxml:datastream[@ID='RELS-EXT']")) {
    $test_id = "RELS-EXT";
    print "At least That works \n";
    $xmlObject->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
    $xmlObject->registerXPathNamespace('fm', 'info:fedora/fedora-system:def/model#');

    $xpath_test = $xmlObject->xpath('/foxml:digitalObject/foxml:datastream/foxml:datastreamVersion[last()]/foxml:xmlContent/rdf:RDF/rdf:Description/fm:hasModel/@rdf:resource');

    $model_full_path = $xpath_test[0]['resource'];
    $model_full = explode(":", $model_full_path);
    $model = $model_full[2];
    if ($model == "bookCModel") {
      $i7DataStreams = $bookCModel;
    } elseif ($model == "compoundCModel") {
      $i7DataStreams = $compoundCModel;
    } elseif ($model == "newspaperPageCModel" || $model == "pageCModel") {
      $i7DataStreams = $newspaperPageCModel;
    } elseif ($model == "oralhistoriesCModel") {
      $i7DataStreams = $oralhistoriesCModel;
    } elseif ($model == "sp_basic_image") {
      $i7DataStreams = $sp_basic_image;
    } elseif ($model == "sp_large_image_cmodel") {
      $i7DataStreams = $sp_large_image_cmodel;
    } elseif ($model == "sp_pdf") {
      $i7DataStreams = $sp_pdf;
    } elseif ($model == "sp_streaming") {
      $i7DataStreams = $sp_streaming;
    } elseif ($model == "sp_videoCModel") {
      $i7DataStreams = $sp_videoCModel;
    } elseif ($model == "sp-audioCModel") {
      $i7DataStreams = $sp_audioCModel;
    } else {
      $i7DataStreams = $i7DataStreamsDefault;
    }
    //print $model_full . "\n";
    print $model . "\n";
    print_r($i7DataStreams);
  }


  foreach ($i7DataStreams as $id => $tid) {
    $field_height = "";
    $field_width = "";
    $obj = $xmlObject->xpath('//foxml:datastream[@ID="' . $id . '"]/foxml:datastreamVersion');
    $lastDatastreamVersion = (array)end($obj);
    $xpath = $xmlObject->xpath('//foxml:datastream[@ID="' . $id . '"]/foxml:datastreamVersion/foxml:contentLocation');
    $lastContentLocation = end($xpath);

    if (empty($lastContentLocation['REF'])) {
      continue;
    }

    if ($id == "JP2") {
      if ($rels_int = $xmlObject->xpath("/foxml:digitalObject/foxml:datastream[@ID='RELS-INT']")) {
        $field_width = $xmlObject->xpath("/foxml:digitalObject/foxml:datastream/foxml:datastreamVersion[last()]/foxml:xmlContent/*[namespace-uri()='http://www.w3.org/1999/02/22-rdf-syntax-ns#' and local-name()='RDF']/*[namespace-uri()='http://www.w3.org/1999/02/22-rdf-syntax-ns#' and local-name()='Description']/*[namespace-uri()='http://islandora.ca/ontology/relsext#' and local-name()='width']")[0];
        $field_height = $xmlObject->xpath("/foxml:digitalObject/foxml:datastream/foxml:datastreamVersion[last()]/foxml:xmlContent/*[namespace-uri()='http://www.w3.org/1999/02/22-rdf-syntax-ns#' and local-name()='RDF']/*[namespace-uri()='http://www.w3.org/1999/02/22-rdf-syntax-ns#' and local-name()='Description']/*[namespace-uri()='http://islandora.ca/ontology/relsext#' and local-name()='height']")[0];
      }
    }

    $ref = (string)$lastContentLocation['REF'];
    $uri = $DATA_URI . dereference($ref);

    // grab some file metadata from the datastream object
    $created = strtotime($lastDatastreamVersion["@attributes"]['CREATED']);
    $mimetype = $lastDatastreamVersion["@attributes"]['MIMETYPE'];
    $size = $lastDatastreamVersion["@attributes"]['SIZE'];
    switch ($mimetype) {
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
      $field_height,
      $field_width,
    ]);
    $field_height = "";
    $field_width = "";
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
