# export/import media from Islandora 7 to Islandora 2

If you don't want to download/upload binary files from i7 into i2, you can migrate in place using these scripts.

Requires the pids/nodes/metadata to be migrated from i7 into i2. Then these scripts can be used to find the i7 binaries associated with each pid/nid and attach the media to the node in i2.

## [export-i7.php](./export-i7.php)

Requires a CSV file with nid/pid mappings. e.g.

```
nid,pid
1,foo:object1
2,foo:object2
```

And needs to be ran from a server with access to the i7 foxml and datastream directories.

Creates a CSV `export.csv`

### Usage

```
php export-i7.php
```

## [import-i2.php](./import-i2.php)

Requires a CSV `export.csv`, created by the export script.

### Usage

```
drush scr import-i2.php --uri https://your-real-domain.com
```
