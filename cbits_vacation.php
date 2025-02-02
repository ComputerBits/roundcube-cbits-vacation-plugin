<?php

require 'drivers/vacationdriver.php';

class cbits_vacation extends rcube_plugin {
    public $noframe = true;
    public $noajax = true;
    private $rc;

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
        $this->register_handler('plugin.body', array($this, 'vacation_form'));
        $this->rc->output->set_pagetitle($this->gettext('setvacation'));

        $data = [
            'message' => rcube_utils::get_input_value('message', rcube_utils::INPUT_POST, true),
            'forward' => rcube_utils::get_input_value('forwarding_address', rcube_utils::INPUT_POST),
        ];

        if (trim($data['forward']) == '') {
            $data['forward'] = null;
        }

        $active = rcube_utils::get_input_value('active', rcube_utils::INPUT_POST);

        if ($active == "off") {
            $data['enabled'] = false;
            $data['start_datetime'] = null;
            $data['end_datetime'] = null;
        } elseif ($active == "on-indef") {
            $data['enabled'] = true;
            $data['start_datetime'] = null;
            $data['end_datetime'] = null;
        } elseif ($active == "on-dates") {
            $data['enabled'] = true;
            $data['start_datetime'] = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $data['start_datetime']);
            if ($data['start_datetime'] === false) {
                $data['start_datetime'] = null;
            }
            $data['end_datetime'] = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $data['end_datetime']);
            if ($data['end_datetime'] === false) {
                $data['end_datetime'] = null;
            }
        } else {
            $this->rc->output->command('display_message', 'Out of Office settings not saved', 'error');
            $this->rc->overwrite_action('plugin.cbits_vacation');
            $this->rc->output->send('plugin');
            return;
        }

        if ($this->save($data)) {
            $this->rc->output->command('display_message', 'Out of Office settings saved successfully', 'confirmation');
        } else {
            $this->rc->output->command('display_message', 'Out of Office settings not saved', 'error');
        }

        $this->rc->overwrite_action('plugin.cbits_vacation');
        $this->rc->output->send('plugin');
    }

    function vacation_form() {
        $data = $this->get();

        $this->rc->html_editor('identity');

        $start_datetime = $data['start_datetime']->format('Y-m-d\TH:i');
        $end_datetime = $data['end_datetime']->format('Y-m-d\TH:i');

        $fields = [
            'active' => [
                html::label('active', rcube::Q($this->gettext('vacenabled'))),
                html::label([], (new html_radiobutton(['name' => 'active']))->show('off', ['value' => 'off']) . "Off"),
                html::label([], (new html_radiobutton(['name' => 'active']))->show('on-indef', ['value' => 'on-indef']) . "Enabled indefinitely"),
                html::label([], (new html_radiobutton(['name' => 'active']))->show('on-dates', ['value' => 'on-dates']) . "Enabled between date range"),
            ],
            'start_datetime' => [
                html::label('start_datetime', rcube::Q($this->gettext('vacstartdate'))),
                (new html_inputfield(['name' => 'start_datetime', 'type' => 'datetime-local']))->show($start_datetime),
            ],
            'end_datetime' => [
                html::label('end_datetime', rcube::Q($this->gettext('vacenddate'))),
                (new html_inputfield(['name' => 'end_datetime', 'type' => 'datetime-local']))->show($end_datetime),
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
        return $this->driver->get();
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
