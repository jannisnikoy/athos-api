<?php

namespace Athos\API;

/**
* Model
* Interfaces for data models.
*
* @package  athos-api
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-api
*/

interface ModelFactory {
    public static function getAll();
    public static function get($id);
}

interface Model {
    public function insert();
    public function update();
    public function delete();
}
?>
