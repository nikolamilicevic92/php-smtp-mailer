<?php

require __DIR__ . '/Mail.php';

$options = [
  // Credentials (if you are using gmail account no need to specify host and port)
  // 'host'     => 'smtp.gmail.com',
  // 'port'     => 465,
  'username' => 'johndoe@gmail.com',
  'password' => '123456789',
  //
  'text'     => 'Hello World!',
  'html'     => '<h1>Hello World!<h1>'
];

( new Mail( $options ) )->send();