<?php

require 'drivers/vacationdriver.php';

class vacation extends rcube_plugin
{
  public $noframe = true;
  public $noajax  = true;
  private $rc;

  function init()
  {
    $this->rc = rcmail::get_instance();
    $this->add_texts('localization/');
    $this->load_driver();
    $this->add_hook('settings_actions', array($this, 'settings_actions'));
    $this->register_action('plugin.vacation', array($this, 'vacation_init'));
    $this->register_action('plugin.vacation-save', array($this, 'vacation_save'));
  }

  function settings_actions($args)
  {
    $args['actions'][] = array(
      'action' => 'plugin.vacation',
      'class'  => 'vacation',
      'label'  => 'setvacation',
      'title'  => 'setvacation',
      'domain' => 'vacation',
    );
    return $args;
  }

  function vacation_init()
  {
    $this->register_handler('plugin.body', array($this, 'vacation_form'));
    $this->rc->output->set_pagetitle($this->gettext('setvacation'));
    $this->rc->output->send('plugin');
  }

  function vacation_save()
  {
    $this->register_handler('plugin.body', array($this, 'vacation_form'));
    $this->rc->output->set_pagetitle($this->gettext('setvacation'));
    $vacsettings = [
      'enabled' => rcube_utils::get_input_value('vacenabled', rcube_utils::INPUT_POST) == "on" ? true : false,
      'subject' => rcube_utils::get_input_value('vacsubject', rcube_utils::INPUT_POST),
      'message' => rcube_utils::get_input_value('vacmessage', rcube_utils::INPUT_POST, true),
      'forward' => rcube_utils::get_input_value('vacforward', rcube_utils::INPUT_POST),
    ];
    if ($this->save($vacsettings)) {
      $this->rc->output->command('display_message', 'Out of Office settings saved successfully', 'confirmation');
    } else {
      $this->rc->output->command('display_message', 'Out of Office settings not saved', 'error');
    }
    $this->rc->overwrite_action('plugin.vacation');
    $this->rc->output->send('plugin');
  }

  function vacation_form()
  {
    $data = $this->get();

    $this->rc->html_editor('identity');
    $table = new html_table(array('cols' => 2, 'class' => 'propform'));

    $field_id = 'vacenabled';
    $input_vacenabled = new html_checkbox(array(
      'name' => $field_id,
      'id' => $field_id,
      'style' => 'width:30px;',
    ));
    $table->add('title', html::label($field_id, rcube::Q($this->gettext('vacenabled'))));
    $table->add(null, $input_vacenabled->show(!$data['enabled']));

    $field_id = 'vacsubject';
    $input_vacsubject = new html_inputfield(array(
      'name' => $field_id,
      'id' => $field_id,
    ));
    $table->add('title', html::label($field_id, rcube::Q($this->gettext('vacsubject'))));
    $table->add(null, $input_vacsubject->show($data['subject']));

    $field_id = 'vacmessage';
    $input_vacmessage = new html_textarea(array(
      'name' => $field_id,
      'id' => $field_id,
      'rows' => 20,
    ));
    $table->add('title', html::label($field_id, rcube::Q($this->gettext('vacmessage'))));
    $table->add(null, $input_vacmessage->show($data['message']));

    $field_id = 'vacforward';
    $input_vacforward = new html_inputfield(array(
      'name' => $field_id,
      'id' => $field_id,
    ));
    $table->add('title', html::label($field_id, rcube::Q($this->gettext('vacforward'))));
    $table->add(null, $input_vacforward->show($data['forward']));

    $submit_button = $this->rc->output->button(array(
                    'command' => 'plugin.vacation-save',
                    'class'   => 'button mainaction submit',
                    'label'   => 'save',
            ));
    $form_buttons = html::p(array('class' => 'formbuttons footerleft'), $submit_button);

    $this->rc->output->add_gui_object('vacform', 'vacation-form');

    $this->include_script('vacation.js');

    $form = $this->rc->output->form_tag(array(
            'id'     => 'vacation-form',
            'name'   => 'vacation-form',
            'method' => 'post',
            'action' => './?_task=settings&_action=plugin.vacation-save',
        ), $table->show());

    return ""
      .html::div(
        array('id' => 'prefs-title', 'class' => 'boxtitle'),
        $this->gettext('setvacation')
      )
      .html::div(
        array('class' => 'box formcontainer scroller'),
        html::div(
          array('class' => 'boxcontent formcontent'),
          $form
        ).$form_buttons
      );
  }

  function get()
  {
    return $this->driver->get();
  }

  function save($vacsettings)
  {
    $this->driver->settings = $vacsettings;
    return $this->driver->save();
  }
  private function load_driver()
  {
    $driver = $this->rc->config->get('vacation_driver', null);
    $class  = "rcube_{$driver}_vacation";
    $file = $this->home . "/drivers/$driver.php";
    if (!file_exists($file)) {
        rcube::raise_error(array(
            'code' => 600,
            'type' => 'php',
            'file' => __FILE__, 'line' => __LINE__,
            'message' => "Vacation plugin: Driver file does not exist ($file)"
        ), true, false);
        return false;
    }
    include_once $file;
    if (!class_exists($class, false) || (!method_exists($class, 'save') && !method_exists($class, 'get'))) {
        rcube::raise_error(array(
            'code' => 600,
            'type' => 'php',
            'file' => __FILE__, 'line' => __LINE__,
            'message' => "Vacation plugin: Broken driver $driver"
        ), true, false);
        return false;
    }
    $this->driver = new $class;
    $this->driver->rc = rcmail::get_instance();
    $this->driver->user = new stdClass;
    $this->driver->user->username = $this->rc->user->get_username('local');
    $this->driver->user->domain = $this->rc->user->get_username('domain');
    $this->driver->init();
    return true;
}

}
