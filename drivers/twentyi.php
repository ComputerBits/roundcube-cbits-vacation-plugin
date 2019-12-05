<?php
require('twentyi_rest_api.class.php');

class rcube_twentyi_vacation extends vacationdriver {
  public function init() {
    $token = $this->rc->config->get('vacation_20i_token');
    $this->rest = new twentyi_rest_api($token);
  }
  public function get() {
    $local  = $this->user->username;
    $domain_name = $this->user->domain;
    $domain = $this->rest->getWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name);
    foreach ($domain->responder as $responder) {
      if ($responder->local == $local && (substr($responder->id, 0, 1) === 'r')) {
        if ($responder->forwardTo == null) {
          $responder->forwardTo = '';
        }
        return array(
          'subject'=>$responder->subject,
          'message'=>$responder->content,
          'forward'=>$responder->forwardTo,
          'enabled'=>$responder->enabled,
        );
      }
      continue;
    }
    return array('subject'=>'','body'=>'','forward'=>'','enabled'=>false);
  }
  public function save() {
    $local  = $this->user->username;
    $domain_name = $this->user->domain;
    $domain = $this->rest->getWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name);
    if ($this->forward == '') {
      $this->forward = null;
    }
    foreach ($domain->responder as $responder) {
      if ($responder->local == $local && (substr($responder->id, 0, 1) === 'r')) {
        $data = [
          'update' => [
            $responder->id => [
              'subject' => $this->settings['subject'],
              'content' => $this->settings['message'],
              'forwardTo' => $this->settings['forward'],
              'enabled' => $this->settings['enabled'],
              'type' => 'text/html',
              'endTime' => null,
              'startTime' => null,
            ]
          ]
        ];
        rcube::console('Posting data: '. json_encode($data, JSON_PRETTY_PRINT));
        return $this->rest->postWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name, $data);
      }
      continue;
    }
    $data = [
      'new' => [
        'responder' => [
          'local' => $local,
          'subject' => $this->settings['subject'],
          'content' => $this->settings['message'],
          'forwardTo' => $this->settings['forward'],
          'enabled' => $this->settings['enabled'],
          'type' => 'text/html',
          'endTime' => null,
          'startTime' => null,
        ]
      ]
    ];
    return $this->rest->postWithFields('https://api.20i.com/package/' . $domain_name . '/email/' . $domain_name, $data);
  }
}
