# JSONQ - JSON Query
Simple PHP library that provides SQL-like style API for JSON data.

You can use this library with remote or local API or any local json files.
## Example
```php
<?php
$jsonq = new \SoilPHP\Tools\JSONQ('some_local_or_remote.json');
$data = $jsonq
            ->from('item')
            ->select('subitem.array')
            ->where('field', 'some text', '=')
            ->has('otherField', 'other text');
// if need you can export to any other file
$data->export('filename.json');
// get selected result
$result = $data->getAll();
```
## Requirements
PHP7+

## Installing
JSONQ can be installed via Composer

## License
This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details