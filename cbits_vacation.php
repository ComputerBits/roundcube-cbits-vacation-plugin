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
        $vacsettings = [
            'enabled' => rcube_utils::get_input_value('vacenabled', rcube_utils::INPUT_POST) == "on" ? true : false,
            //      'subject' => rcube_utils::get_input_value('vacsubject', rcube_utils::INPUT_POST),
            'message' => rcube_utils::get_input_value('vacmessage', rcube_utils::INPUT_POST, true),
            'forward' => rcube_utils::get_input_value('vacforward', rcube_utils::INPUT_POST),
            'enddate' => rcube_utils::get_input_value('vacenddate', rcube_utils::INPUT_POST),
            'startdate' => rcube_utils::get_input_value('vacstartdate', rcube_utils::INPUT_POST),
            'endtime' => rcube_utils::get_input_value('vacendtime', rcube_utils::INPUT_POST),
            'starttime' => rcube_utils::get_input_value('vacstarttime', rcube_utils::INPUT_POST),
        ];
        if ($this->save($vacsettings)) {
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
        $table = new html_table(array('cols' => 3, 'class' => 'propform'));

        $field_id = 'vacenabled';
        $input_vacenabled = new html_checkbox(array(
            'name' => $field_id,
            'id' => $field_id,
            'style' => 'width:30px;',
        ));
        $table->add('title', html::label($field_id, rcube::Q($this->gettext('vacenabled'))));
        $table->add(['class' => 'col-sm-1'], $input_vacenabled->show(!$data['enabled']));
        $table->add_row();

        $table->add(null, '<p id="vacdatetimemessage">' . $this->gettext('vacdateinstructions') . '</p>');
        $table->add_row();

        $input_vacstartdate = new html_inputfield([
            'name' => 'vacstartdate',
            'id' => 'vacstartdate',
            'placeholder' => (new DateTime())->format('Y-m-d'),
            'width' => '50%',
        ]);
        $input_vacstarttime = new html_inputfield([
            'name' => 'vacstarttime',
            'id' => 'vacstarttime',
            'placeholder' => '00:00',
            'width' => '50%',
        ]);

        $table->add('title col-sm-4', html::label('vacstartdate', rcube::Q($this->gettext('vacstartdate'))));
        $table->add(['class' => 'col-sm-4'], $input_vacstartdate->show($data['startdate']));
        $table->add(['class' => 'col-sm-4'], $input_vacstarttime->show($data['starttime']));
        $table->add_row();

        $input_vacendtime = new html_inputfield([
            'name' => 'vacendtime',
            'id' => 'vacendtime',
            'placeholder' => '00:00',
            'width' => '50%',
        ]);
        $input_enddate = new html_inputfield([
            'name' => 'vacenddate',
            'id' => 'vacenddate',
            'placeholder' => (new DateTime())->add(DateInterval::createFromDateString('3 months'))->format('Y-m-d'),
            'width' => '50%',
        ]);
        $table->add('title col-sm-4', html::label('vacenddate', rcube::Q($this->gettext('vacenddate'))));
        $table->add(['class' => 'col-sm-4'], $input_enddate->show($data['enddate']));
        $table->add(['class' => 'col-sm-4'], $input_vacendtime->show($data['endtime']));

//        $field_id = 'vacsubject';
//        $input_vacsubject = new html_inputfield(array(
//            'name' => $field_id,
//            'id' => $field_id,
//        ));
//        $table->add('title', html::label($field_id, rcube::Q($this->gettext('vacsubject'))));
//        $table->add(["colspan" => 2], $input_vacsubject->show($data['subject']));
//        $table->add_row();

        $field_id = 'vacmessage';
        $input_vacmessage = new html_textarea(array(
            'name' => $field_id,
            'id' => $field_id,
            'class' => 'mce_editor',
            'rows' => 20,
        ));
        $table->add('title', html::label($field_id, rcube::Q($this->gettext('vacmessage'))));
        $table->add(["colspan" => 2], $input_vacmessage->show($data['message']));
        $table->add_row();

        $field_id = 'vacforward';
        $input_vacforward = new html_inputfield(array(
            'name' => $field_id,
            'id' => $field_id,
        ));
        $table->add('title', html::label($field_id, rcube::Q($this->gettext('vacforward'))));
        $table->add(["colspan" => 2], $input_vacforward->show($data['forward']));
        $table->add_row();

        $submit_button = $this->rc->output->button(array(
            'command' => 'plugin.cbits_vacation.save',
            'class' => 'button mainaction submit',
            'label' => 'save',
        ));
        $form_buttons = html::p(array('class' => 'formbuttons footerleft'), $submit_button);

        $this->rc->output->add_gui_object('vacform', 'vacation-form');

        $this->include_script('cbits_vacation.js');

        $form = $this->rc->output->form_tag(array(
            'id' => 'vacation-form',
            'name' => 'vacation-form',
            'method' => 'post',
            'action' => './?_task=settings&_action=plugin.cbits_vacation.save',
        ), $table->show());

        return ""
            . html::div(
                array('id' => 'prefs-title', 'class' => 'boxtitle'),
                $this->gettext('setvacation')
            )
            . html::div(
                array('class' => 'box formcontainer scroller'),
                html::div(
                    array('class' => 'boxcontent formcontent'),
                    $form
                ) . $form_buttons
            );
    }

    function get() {
        return $this->driver->get();
    }

    function save($vacsettings) {
        $this->driver->settings = $vacsettings;
        return $this->driver->save();
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
