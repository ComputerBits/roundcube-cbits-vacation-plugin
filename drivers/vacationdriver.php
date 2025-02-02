<?php

abstract class vacationdriver {
    public $user;
    public $rc;

    abstract function init();

    abstract function get();

    abstract function save($data);
}
