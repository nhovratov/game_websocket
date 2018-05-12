<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 23:44
 */

use MyApp\LoveLetter\LoveLetter;

require dirname(__DIR__) . '/../vendor/autoload.php';

$loveletter = new LoveLetter();
echo count($loveletter->getStack()) === 16;
