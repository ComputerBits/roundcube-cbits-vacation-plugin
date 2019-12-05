<?php

abstract class vacationdriver {
  public $user;
  public $rcmail;
  public $vacation_settings;
  abstract function init();
  abstract function get();
  abstract function save();
}
