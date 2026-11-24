# Fiche 3 — Appeler une API REST en PHP avec Guzzle

**Objectif :** Savoir installer, configurer et utiliser la bibliothèque **Guzzle** en PHP pour envoyer des requêtes HTTP (avec en-têtes et JSON), lire la réponse et gérer proprement les erreurs.

---

## 1. Pourquoi utiliser Guzzle ?

Comme vu dans la Fiche 2, PHP peut jouer le rôle de **client HTTP** pour interroger une API REST. Plutôt que d'utiliser les fonctions bas niveau de `cURL`, on utilise **Guzzle** (`guzzlehttp/guzzle`), la bibliothèque client HTTP de référence en PHP.

Ses principaux avantages :
- Une syntaxe objet claire et lisible (`$client->get(...)`, `$client->post(...)`).
- La conversion automatique d'un tableau PHP en JSON (avec le bon en-tête `Content-Type: application/json`).
- La gestion des erreurs réseau et des codes HTTP `4xx` / `5xx` par exceptions (`try / catch`).

### Installation et chargement avec Composer

Dans un projet PHP géré par Composer, on installe Guzzle avec :

```bash
composer require guzzlehttp/guzzle
```

Ensuite, au début du script PHP, on charge l'autoloader de Composer puis on importe les classes nécessaires :

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
```

---

## 2. Créer et configurer un client HTTP

La première étape consiste à instancier un objet `GuzzleHttp\Client`. On lui passe généralement un tableau d'options par défaut :

```php
$client = new Client([
    'base_uri' => 'https://api.exemple.fr',
    'timeout'  => 30,
]);
```

### Options de configuration courantes

| Option | Rôle | Exemple |
| :--- | :--- | :--- |
| **`base_uri`** | Adresse de base de l'API (évite de la répéter à chaque appel). | `'https://generativelanguage.googleapis.com'` |
| **`timeout`** | Durée maximale d'attente d'une réponse (en secondes) avant d'abandonner. | `30` ou `60` |
| **`verify`** | Vérification du certificat SSL/TLS (HTTPS). Vaut `true` par défaut. | `true` ou chemin vers `cacert.pem` |

> **Note pratique (HTTPS sous Windows) :** Si PHP affiche une erreur de certificat SSL (`cURL error 60: SSL certificate problem`), cela signifie que PHP ne trouve pas la liste des autorités de certification (`cacert.pem`). Il faut soit la configurer dans `php.ini`, soit passer le chemin du fichier à l'option `'verify'`. Il est fortement déconseillé de mettre `'verify' => false` en production.

---

## 3. Envoyer des requêtes HTTP

### Exemple A — Requête `GET` simple

Pour récupérer une ressource sans envoyer de corps JSON :

```php
$reponse = $client->get('/utilisateurs/42');
```

Grâce à `base_uri`, Guzzle interroge en réalité `https://api.exemple.fr/utilisateurs/42`.

### Exemple B — Requête `POST` avec en-têtes et JSON

C'est le cas typique d'une API d'IA ou d'un service authentifié. On passe un second argument (un tableau d'options) à `$client->post()` :

```php
$donnees = [
    'message' => 'Bonjour',
    'langue'  => 'fr',
];

$reponse = $client->post('/v1/analyse', [
    'headers' => [
        'x-api-key' => 'MA_CLE_SECRETE',
    ],
    'json' => $donnees,
]);
```

**Ce que fait l'option `'json'` automatiquement pour vous :**
1. Elle convertit le tableau PHP `$donnees` en texte JSON (équivalent de `json_encode()`).
2. Elle ajoute automatiquement l'en-tête HTTP `Content-Type: application/json`.

Il n'est donc pas nécessaire d'appeler `json_encode()` manuellement ni d'écrire l'en-tête `Content-Type` quand on utilise `'json'`.

---

## 4. Lire la réponse du serveur

Les méthodes `get()`, `post()`, etc. retournent un objet réponse implémentant l'interface PSR-7 `ResponseInterface`.

Les deux méthodes les plus utiles sont :

- **`$reponse->getStatusCode()`** : retourne le code HTTP sous forme d'entier (`200`, `201`, etc.).
- **`$reponse->getBody()`** : retourne un flux contenant le corps de la réponse. On le convertit en chaîne de caractères avec `(string)` avant de le décoder avec `json_decode()`.

```php
$codeHttp = $reponse->getStatusCode(); // ex. 200
$corpsBrut = (string) $reponse->getBody();

$resultat = json_decode($corpsBrut, true, 512, JSON_THROW_ON_ERROR);
```

---

## 5. Gérer les erreurs avec `try / catch`

Par défaut, si le serveur est injoignable **ou** s'il répond avec un code d'erreur HTTP (`4xx` comme `401`/`404`, ou `5xx` comme `500`), Guzzle lève une exception.

Toutes les exceptions de Guzzle implémentent `GuzzleHttp\Exception\GuzzleException`. On entoure donc l'appel d'un bloc `try / catch` :

```php
try {
    $reponse = $client->post('/v1/analyse', [
        'headers' => ['x-api-key' => $cleApi],
        'json'    => $donnees,
    ]);

    $resultat = json_decode(
        (string) $reponse->getBody(),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

} catch (GuzzleException $exception) {
    // Erreur réseau (DNS, timeout...) ou HTTP (400, 401, 403, 429, 500...)
    echo 'Erreur lors de l’appel API : ' . $exception->getMessage();
}
```

> **Pour aller plus loin :** Si vous souhaitez réagir différemment selon le type d'erreur (par exemple afficher le détail renvoyé par l'API en cas de code `400` ou `403`), vous pouvez intercepter `GuzzleHttp\Exception\RequestException` qui possède une méthode `$e->getResponse()` permettant d'accéder au code HTTP et au corps d'erreur.

---

## 6. Synthèse des 3 fiches

Voici comment les trois concepts s'assemblent lors d'un appel d'API en PHP :

| Étape | Concept (Fiche) | Traduction en PHP / Guzzle |
| :--- | :--- | :--- |
| Préparer les données | Format JSON *(Fiche 1)* | Tableau associatif PHP structuré selon la doc de l'API |
| Définir la cible et les droits | API REST *(Fiche 2)* | `'base_uri'`, méthode `$client->post(...)` et `'headers'` |
| Envoyer et encoder | Guzzle *(Fiche 3)* | Option `'json' => $donnees` |
| Capturer les pannes réseau/HTTP | API REST & Guzzle *(Fiches 2 & 3)* | Bloc `try / catch (GuzzleException $e)` |
| Exploiter la réponse | Format JSON *(Fiche 1)* | `json_decode((string) $reponse->getBody(), true, 512, JSON_THROW_ON_ERROR)` |

---

## 7. Exercices pratiques

Les corrections sont disponibles dans le document annexe : [`annexe-corrections.md`](annexe-corrections.md).

### Exercice 1 — Effectuer une requête `GET` avec Guzzle

On souhaite interroger l'API de test publique `https://jsonplaceholder.typicode.com` sur le chemin `/users/1` (qui renvoie un objet JSON contenant notamment les clés `"name"` et `"email"`).

Écris un script PHP complet (avec `use`, `Client`, `try / catch` et `json_decode`) qui :
1. Crée un client Guzzle configuré avec `base_uri` et un `timeout` de `10` secondes.
2. Envoie une requête `GET` sur `/users/1`.
3. Affiche le code HTTP reçu, le nom (`name`) et l'adresse e-mail (`email`) de l'utilisateur.
4. Affiche un message d'erreur clair en cas d'exception `GuzzleException`.

### Exercice 2 — Envoyer une requête `POST` authentifiée en JSON

Écris un script PHP utilisant Guzzle pour envoyer une requête `POST` vers l'API de test publique `https://httpbin.org/post` (qui renvoie en JSON les en-têtes et les données reçus) avec :
- Un `timeout` de `15` secondes.
- L'en-tête `X-Api-Key` ayant pour valeur `'CLE_TEST_123'`.
- Un corps JSON représentant l'objet suivant :
  ```json
  {
    "auteur": "BTS SIO",
    "contenu": "Premier test avec Guzzle"
  }
  ```
- Le décodage JSON de la réponse dans une variable `$resultat`, l'affichage de la clé (`$resultat['headers']['X-Api-Key']`) et du contenu (`$resultat['json']['contenu']`) renvoyés par `httpbin.org`, ainsi que la gestion des exceptions `GuzzleException` et `JsonException`.

### Exercice 3 — Mettre en pratique sur l'API Gemini

En combinant les connaissances des Fiches 1, 2 et 3, écris un petit bloc de code PHP autonome (en supposant qu'une variable `$cleApi` et une variable `$prompt` existent déjà) qui :
1. Instancie un client Guzzle avec `base_uri` égal à `https://generativelanguage.googleapis.com` et un `timeout` de `60` secondes.
2. Envoie une requête `POST` vers `/v1beta/models/gemini-3.5-flash-lite:generateContent` avec :
   - l'en-tête `x-goog-api-key` contenant `$cleApi`,
   - le corps JSON minimal contenant `$prompt` pour le rôle `"user"`.
3. Décode la réponse JSON et affiche le texte généré par Gemini, tout en gérant les éventuelles erreurs avec `try / catch`.

