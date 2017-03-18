<?php
include "src/JSONQ.php";
$jsonq = new \SoilPHP\Tools\JSONQ('https://api.vk.com/method/wall.get?owner_id=-6446578&extended=1&count=15&v=5.62');

$groups = $jsonq->from('response')->select('groups')->query();
$groups->from('groups')->select('id,name,photo_50');
//$resultGroups = $groups->getAll();
echo '<pre>'.print_r($groups->getAll(), true).'</pre>';

$profile = $jsonq->from('response')->select('profiles')->query();
$profile->from('profiles')->select('id,first_name,photo_50');


echo '<pre>'.print_r($profile->getAll(), true).'</pre>';