// /config.php
<?php
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'projectflow',
        'user' => 'projectflow',
        'pass' => 'votre_mot_de_passe'
    ],
    'app' => [
        'name' => 'ProjectFlow',
        'url' => 'http://localhost:8000',
        'debug' => true
    ],
    'security' => [
        'jwt_secret' => 'votre_secret_jwt',
        'session_lifetime' => 3600
    ]
];
