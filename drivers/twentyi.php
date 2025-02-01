<?php
require('twentyi_rest_api.class.php');

class rcube_twentyi_vacation extends vacationdriver {
    public function init() {
        $token = $this->rc->config->get('vacation_20i_token');
        $this->rest = new twentyi_rest_api($token);
    }

    public function get() {
        $local = $this->user->username;
        $domain_name = $this->user->domain;
        $domain = $this->rest->getWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name);
        foreach ($domain->responder as $responder) {
            if ($responder->local == $local && (str_starts_with($responder->id, 'r'))) {
                if ($responder->forwardTo == null) {
                    $responder->forwardTo = '';
                }
                $start_datetime = (!is_null($responder->startTime) ? new DateTime($responder->startTime) : null);
                $end_datetime = (!is_null($responder->endTime) ? new DateTime($responder->endTime) : null);
                return [
                    'message' => $responder->content,
                    'forward' => $responder->forwardTo,
                    'enabled' => $responder->enabled,
                    'starttime' => (!is_null($start_datetime) ? $start_datetime->format('H:i') : ''),
                    'endtime' => (!is_null($end_datetime) ? $end_datetime->format('H:i') : ''),
                    'startdate' => (!is_null($start_datetime) ? $start_datetime->format('Y-m-d') : ''),
                    'enddate' => (!is_null($end_datetime) ? $end_datetime->format('Y-m-d') : ''),
                ];
            }
        }
        return [
            'subject' => '',
            'body' => '',
            'forward' => '',
            'enabled' => false,
        ];
    }

    public function save() {
        $local = $this->user->username;
        $domain_name = $this->user->domain;
        $domain = $this->rest->getWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name);
        if ($this->forward == '') {
            $this->forward = null;
        }

        $startdatetime = ($this->settings['startdate'] == '') ? null : new DateTime($this->settings['startdate']);
        $enddatetime = ($this->settings['enddate'] == '') ? null : new DateTime($this->settings['enddate']);

        if (!is_null($startdatetime)) {
            $startdatetime->setTime(...explode(':', $this->settings['starttime']));
        }
        if (!is_null($enddatetime)) {
            $enddatetime->setTime(...explode(':', $this->settings['endtime']));
        }

        foreach ($domain->responder as $responder) {
            if ($responder->local == $local && (str_starts_with($responder->id, 'r'))) {
                $data = [
                    'update' => [
                        $responder->id => [
                            'subject' => 'Autoresponse - Re: $h_subject',
                            'content' => $this->settings['message'],
                            'forwardTo' => $this->settings['forward'],
                            'enabled' => $this->settings['enabled'],
                            'type' => 'text/html',
                            'endTime' => (is_null($enddatetime)) ? null : $enddatetime->format('c'),
                            'startTime' => (is_null($startdatetime)) ? null : $startdatetime->format('c'),
                        ]
                    ]
                ];
                return $this->rest->postWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name, $data);
            }
        }
        $data = [
            'new' => [
                'responder' => [
                    'local' => $local,
                    'subject' => 'Autoresponse - Re: $h_subject',
                    'content' => $this->settings['message'],
                    'forwardTo' => $this->settings['forward'],
                    'enabled' => $this->settings['enabled'],
                    'type' => 'text/html',
                    'endTime' => $enddatetime->format('c'),
                    'startTime' => $startdatetime->format('c'),
                ]
            ]
        ];
        return $this->rest->postWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name, $data);
    }
}
