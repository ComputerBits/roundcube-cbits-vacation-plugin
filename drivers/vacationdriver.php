<?php

abstract class vacationdriver {
    public $user;

    abstract function init();

    abstract function get();

    abstract function save($data);
}
