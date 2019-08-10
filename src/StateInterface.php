<?php
/**
 * Created by PhpStorm.
 * User: NIKITA
 * Date: 02.06.2018
 * Time: 17:06
 */

namespace NH;


interface StateInterface
{

    /**
     * @return array
     */
    public function getState();

    public function reset();

}
