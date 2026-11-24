<?php

declare (strict_types = 1);

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Exception\GuzzleException;

/**
 * Envoie un prompt à l'API Gemini et retourne la réponse textuelle.
 *
 * Charge et valide la configuration locale (`config/config.local.php`),
 * envoie une requête POST vers l'endpoint `generateContent` avec une
 * instruction système demandant une réponse en texte brut, puis extrait
 * les parties textuelles de la réponse JSON.
 *
 * @param string $prompt Le message (prompt) à envoyer au modèle Gemini.
 *
 * @return string La réponse textuelle générée par Gemini, sans mise en forme Markdown.
 */
function interrogerGemini(string $prompt): string
{
    // A vous d'écrire la suite !
    return "";
}
