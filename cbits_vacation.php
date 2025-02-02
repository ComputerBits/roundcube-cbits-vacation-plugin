<?php

require 'drivers/vacationdriver.php';

class cbits_vacation extends rcube_plugin {
    public $noframe = true;
    public $noajax = true;
    private $rc;
    public $data;

    function init() {
        $this->rc = rcmail::get_instance();
        $this->add_texts('localization/');
        $this->load_driver();
        $this->add_hook('settings_actions', array($this, 'settings_actions'));
        $this->register_action('plugin.cbits_vacation', array($this, 'vacation_init'));
        $this->register_action('plugin.cbits_vacation.save', array($this, 'vacation_save'));
    }

    function settings_actions($args) {
        $args['actions'][] = array(
            'action' => 'plugin.cbits_vacation',
            'class' => 'vacation',
            'label' => 'setvacation',
            'title' => 'setvacation',
            'domain' => 'cbits_vacation',
        );
        return $args;
    }

    function vacation_init() {
        $this->register_handler('plugin.body', array($this, 'vacation_form'));
        $this->rc->output->set_pagetitle($this->gettext('setvacation'));
        $this->rc->output->send('plugin');
    }

    function vacation_save() {
        $this->register_handler('plugin.body', array($this, 'create_form'));
        $this->rc->output->set_pagetitle($this->gettext('setvacation'));

        // data we will pass to the driver
        $data_for_backend = [];

        // data we have been sent, and will send back to the client on error
        $this->data = [
            'active' => rcube_utils::get_input_value('active', rcube_utils::INPUT_POST),
            'forwarding_address' => rcube_utils::get_input_value('forwarding_address', rcube_utils::INPUT_POST),
            'start_datetime' => rcube_utils::get_input_value('start_datetime', rcube_utils::INPUT_POST),
            'end_datetime' => rcube_utils::get_input_value('end_datetime', rcube_utils::INPUT_POST),
            'message' => rcube_utils::get_input_value('message', rcube_utils::INPUT_POST, true),
        ];

        if ($this->data['active'] == "off") {
            $data_for_backend['enabled'] = false;
            $data_for_backend['start_datetime'] = null;
            $data_for_backend['end_datetime'] = null;
        } elseif ($this->data['active'] == "on-indef") {
            $data_for_backend['enabled'] = true;
            $data_for_backend['start_datetime'] = null;
            $data_for_backend['end_datetime'] = null;
        } elseif ($this->data['active'] == "on-dates") {
            $data_for_backend['enabled'] = true;

            $start_datetime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $this->data['start_datetime']);
            if ($start_datetime === false) {
                $this->send_error("Start datetime is in an invalid format, needs to be in form of " . date('Y-m-d\TH:i'));
            }
            $data_for_backend['start_datetime'] = $start_datetime;

            $end_datetime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $this->data['end_datetime']);
            if ($end_datetime === false) {
                $this->send_error("End datetime is in an invalid format, needs to be in form of " . date('Y-m-d\TH:i'));
            }
            $data_for_backend['end_datetime'] = $end_datetime;

            if ($data_for_backend['start_datetime'] > $data_for_backend['end_datetime']) {
                $this->send_error("Start datetime should be before end datetime");
            }
        } else {
            $this->send_error('Invalid value for "enabled" field');
        }

        $data_for_backend['message'] = rcube_utils::get_input_value('message', rcube_utils::INPUT_POST, true);
        $data_for_backend['forward'] = rcube_utils::get_input_value('forwarding_address', rcube_utils::INPUT_POST);

        if (trim($data_for_backend['forward']) == '') {
            $data_for_backend['forward'] = null;
        }

        if ($this->save($data_for_backend)) {
            $this->rc->output->command('display_message', 'Out of Office settings saved successfully', 'confirmation');
        } else {
            $this->rc->output->command('display_message', 'Out of Office settings not saved', 'error');
        }

        $this->data = $this->get();
        $this->rc->overwrite_action('plugin.cbits_vacation');
        $this->rc->output->send('plugin');
    }

    function send_error($error) {
        $this->rc->output->command('display_message', $error, 'error');
        $this->rc->overwrite_action('plugin.cbits_vacation');
        $this->rc->output->send('plugin');
    }

    // creates form from data on backend
    function vacation_form() {
        $this->data = $this->get();
        return $this->create_form();
    }

    // creates form from data stored in object
    function create_form() {
        $data = $this->data;
        $this->rc->html_editor('identity');

        $fields = [
            'active' => [
                html::label('active', rcube::Q($this->gettext('vacenabled'))),
                html::label([], (new html_radiobutton(['name' => 'active']))->show($data['active'], ['value' => 'off']) . "Off"),
                html::label([], (new html_radiobutton(['name' => 'active']))->show($data['active'], ['value' => 'on-indef']) . "Enabled indefinitely"),
                html::label([], (new html_radiobutton(['name' => 'active']))->show($data['active'], ['value' => 'on-dates']) . "Enabled between date range"),
            ],
            'start_datetime' => [
                html::label('start_datetime', rcube::Q($this->gettext('vacstartdate'))),
                (new html_inputfield(['name' => 'start_datetime', 'type' => 'datetime-local']))->show($data['start_datetime']),
            ],
            'end_datetime' => [
                html::label('end_datetime', rcube::Q($this->gettext('vacenddate'))),
                (new html_inputfield(['name' => 'end_datetime', 'type' => 'datetime-local']))->show($data['end_datetime']),
            ],
            'forwarding_address' => [
                html::label('forwarding_address', rcube::Q($this->gettext('vacforward'))),
                (new html_inputfield(['name' => 'forwarding_address', 'type' => 'email']))->show($data['forward']),
            ],
            'message' => [
                html::label('forwarding_address', rcube::Q($this->gettext('vacmessage'))),
                (new html_textarea(['name' => 'message', 'id' => 'vacation-message']))->show($data['message'], ['class' => 'mce_editor']),
            ],
            'save' => [
                $this->rc->output->button(array(
                    'command' => 'plugin.cbits_vacation.save',
                    'class' => 'button mainaction submit',
                    'label' => 'save',
                ))
            ]
        ];

        $output = '';

        foreach ($fields as $id => $field) {
            $output .= html::div(['id' => "vacation-$id-row"], join('', $field));
        }

        $this->rc->output->add_gui_object('vacform', 'cbits_vacation-form');

        $this->include_script('cbits_vacation.js');

        return $this->rc->output->form_tag(array(
            'id' => 'cbits_vacation-form',
            'name' => 'cbits_vacation-form',
            'method' => 'post',
            'action' => './?_task=settings&_action=plugin.cbits_vacation.save',
        ), $output);
    }

    function get() {
        $data = $this->driver->get();

        if ($data['enabled'] === true) {
            if ($data['start_datetime'] !== null && $data['end_datetime'] !== null) {
                $data['active'] = "on-dates";
            } else {
                $data['active'] = "on-indef";
            }
        } else {
            $data['active'] = "off";
        }

        // we use 'active'
        unset($this->data['enabled']);

        $data['start_datetime'] = is_null($data['start_datetime']) ? null : $data['start_datetime']->format('Y-m-d\TH:i');
        $data['end_datetime'] = is_null($data['end_datetime']) ? null : $data['end_datetime']->format('Y-m-d\TH:i');

        return $data;
    }

    function save($data) {
        return $this->driver->save($data);
    }

    private function load_driver() {
        $driver = $this->rc->config->get('vacation_driver', null);
        $class = "rcube_{$driver}_vacation";
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
