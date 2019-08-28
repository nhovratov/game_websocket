<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 12.05.2018
 * Time: 22:10
 */

namespace NH;


interface GameInterface
{
    function start($players);

    function handleAction($params);
}
