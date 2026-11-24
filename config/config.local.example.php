<?php

declare (strict_types = 1);

// Copier ce fichier vers config.local.php et renseigner votre clé API Gemini.
return [
    'gemini_api_key' => 'VOTRE_CLE_API_GEMINI',
    'gemini_model'   => 'gemini-3.5-flash-lite',

    // Optionnel : chemin vers cacert.pem si PHP/cURL n'a pas de certificat CA configuré
    // (ex. sous Windows : 'C:/php-8.4.8/extras/ssl/cacert.pem'). Laisser null sous macOS/Linux.
    'ssl_ca_bundle'  => null,
];
