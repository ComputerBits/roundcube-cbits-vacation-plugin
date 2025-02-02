<?php
require('twentyi_rest_api.class.php');

class rcube_twentyi_vacation extends vacationdriver {
    public twentyi_rest_api $rest;

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
                    'start_datetime' => $start_datetime,
                    'end_datetime' => $end_datetime,
                ];
            }
        }
        return [
            'message' => null,
            'forward' => null,
            'enabled' => false,
            'start_datetime' => null,
            'end_datetime' => null,
        ];
    }

    public function save($data) {
        $local = $this->user->username;
        $domain_name = $this->user->domain;
        $domain = $this->rest->getWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name);

        foreach ($domain->responder as $responder) {
            if ($responder->local == $local && (str_starts_with($responder->id, 'r'))) {
                $data = [
                    'update' => [
                        $responder->id => [
                            'subject' => 'Autoresponse - Re: $h_subject',
                            'content' => $data['message'],
                            'forwardTo' => $data['forward'],
                            'enabled' => $data['enabled'],
                            'type' => 'text/html',
                            'endTime' => $data['end_datetime']->format(DateTimeInterface::RFC3339_EXTENDED),
                            'startTime' => $data['start_datetime']->format(DateTimeInterface::RFC3339_EXTENDED),
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
                    'content' => $data['message'],
                    'forwardTo' => $data['forward'],
                    'enabled' => $data['enabled'],
                    'type' => 'text/html',
                    'endTime' => $data['end_datetime']->format(DateTimeInterface::RFC3339_EXTENDED),
                    'startTime' => $data['start_datetime']->format(DateTimeInterface::RFC3339_EXTENDED),
                ]
            ]
        ];
        return $this->rest->postWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name, $data);
    }
}
