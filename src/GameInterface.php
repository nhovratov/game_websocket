<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 22:10
 */

namespace MyApp;


interface GameInterface
{
    public function start($players);

    public function updateState();

    public function handleAction($action, $params);
}
