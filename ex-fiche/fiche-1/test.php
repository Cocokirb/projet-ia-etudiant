<?php
$configBot = [
    'nom'         => 'Assistant SIO',
    'temperature' => 0.5,
    'langues'     => ['fr', 'en'],
];
$json = json_encode($configBot, JSON_THROW_ON_ERROR);
echo $json;

$configBot2 = '{"nom" : "Assistant SIO" , "temperature" : 0.5 , "langues" : ["fr" , "en"]}';

$donnees = json_decode(
    $configBot2,
    true,              // true = retourne un tableau associatif (et non un objet stdClass)
    512,               // profondeur maximale d'imbrication (valeur par défaut)
    JSON_THROW_ON_ERROR// lève une exception JsonException si le JSON est invalide
);

echo PHP_EOL . $donnees["nom"];

$json2 = '{
  "candidates": [
    {
      "content": {
        "parts": [
          { "text": "Première partie." },
          { "text": "Deuxième partie." }
        ],
        "role": "model"
      },
      "finishReason": "STOP"
    }
  ],
  "usageMetadata": {
    "promptTokenCount": 18,
    "candidatesTokenCount": 12,
    "totalTokenCount": 30
  }
}';

$resultat = json_decode(
    $json2,
    true,              // true = retourne un tableau associatif (et non un objet stdClass)
    512,               // profondeur maximale d'imbrication (valeur par défaut)
    JSON_THROW_ON_ERROR// lève une exception JsonException si le JSON est invalide
);

echo PHP_EOL . $resultat["candidates"][0]["content"]["parts"][1]["text"];
