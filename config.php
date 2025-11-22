<?php
// config.php — keep this outside webroot if you can
return [
  'db' => [
    'host' => '127.0.0.1',
    'name' => 'educonnect_db',
    'user' => 'root',
    'pass' => '',            // XAMPP default is empty for root
    'charset' => 'utf8mb4'
  ],
  'app' => [
    'base_url' => 'http://localhost/edu_connect'
  ]
];
