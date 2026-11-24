# Fiche 2 — Communiquer avec un LLM via une API REST

**Objectif :** Comprendre ce qu'est une API REST, à quoi elle sert et comment une application PHP dialogue avec un service distant comme Gemini (en préparation de la Fiche 3 sur Guzzle).

---

## 1. Qu'est-ce qu'une API et à quoi ça sert ?

Une **API** (*Application Programming Interface*) est une **interface de communication** qui permet à deux programmes informatiques d'échanger des données et des services, sans avoir besoin de connaître leur fonctionnement interne.

### L'analogie du restaurant

- **Vous (le client)** : votre programme PHP.
- **La cuisine (le serveur)** : les serveurs de Google qui font tourner le modèle Gemini.
- **Le serveur de salle (l'API)** : l'intermédiaire qui prend votre commande dans un format précis, l'apporte en cuisine et vous rapporte le plat préparé.

Sans API, vous devriez installer et exécuter vous-même un modèle d'IA gigantesque sur votre machine. Grâce à l'API, votre code PHP envoie simplement une demande sur Internet et récupère la réponse générée.

---

## 2. Qu'est-ce qu'une API REST ?

Une **API REST** est une API qui utilise le protocole **HTTP** — le même protocole que votre navigateur web — pour faire communiquer des applications sur le réseau.

La différence avec un site web classique :
- Un **navigateur** envoie une requête HTTP et reçoit une page **HTML** destinée à être affichée à un humain.
- Un **programme** qui appelle une API REST envoie une requête HTTP et reçoit généralement du **JSON** (vu dans la Fiche 1) destiné à être traité par du code.

---

## 3. Anatomie d'un échange avec l'API REST de Gemini

Communiquer avec une API REST consiste toujours à envoyer une **requête HTTP** et à recevoir une **réponse HTTP**.

### A. La requête HTTP (ce que votre application envoie)

Une requête REST repose sur 4 éléments essentiels :

1. **La méthode HTTP** *(l'action voulue)* :
   - `GET` : récupérer une information (ex. consulter la météo, lister des ressources).
   - `POST` : envoyer des données pour créer quelque chose ou demander un calcul/traitement.
   - *(Il existe aussi `PUT`/`PATCH` pour modifier et `DELETE` pour supprimer.)*
   - Pour Gemini, on utilise **`POST`**, car on envoie un prompt (des données) pour que le serveur génère une réponse.

2. **L'URL, appelée *endpoint*** *(l'adresse du service)* :
   ```text
   https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent
   ```
   - `https://generativelanguage.googleapis.com` : le serveur (adresse de base de l'API).
   - `/v1beta/models/gemini-3.5-flash-lite:generateContent` : le chemin précis indiquant la version de l'API, le modèle choisi et l'action demandée.

3. **Les en-têtes (*headers*)** *(les métadonnées du message)* :
   Ils donnent des informations techniques au serveur, notamment le format des données et l'authentification :
   ```http
   Content-Type: application/json
   x-goog-api-key: VOTRE_CLE_API_GEMINI
   ```
   - `Content-Type: application/json` indique que le corps de la requête est au format JSON.
   - `x-goog-api-key` transmet votre clé API personnelle pour prouver que vous avez le droit d'utiliser le service.

4. **Le corps (*body*)** *(les données utiles)* :
   Pour une requête `POST` vers Gemini, c'est tout simplement le document JSON vu dans la Fiche 1 :
   ```json
   {
     "contents": [
       {
         "role": "user",
         "parts": [
           { "text": "Bonjour, explique REST en une phrase." }
         ]
       }
     ]
   }
   ```

### B. La réponse HTTP (ce que Gemini renvoie)

Le serveur renvoie toujours deux choses principales :

1. **Un code de statut HTTP** qui indique si l'opération a réussi ou pourquoi elle a échoué :

| Code | Signification | Cause fréquente avec une API |
| :--- | :--- | :--- |
| **`200` OK** | Succès | La requête est valide, la réponse JSON est dans le corps. |
| **`400` Bad Request** | Requête invalide | JSON mal formé, champ manquant ou nom de champ incorrect. |
| **`401` / `403`** | Non autorisé / Interdit | Clé API absente, expirée ou invalide. |
| **`404` Not Found** | Ressource introuvable | Erreur dans l'URL ou nom de modèle inexistant. |
| **`429` Too Many Requests** | Trop de requêtes | Quota ou limite de requêtes par minute dépassée. |
| **`500` / `503`** | Erreur serveur | Problème temporaire du côté du fournisseur de l'API. |

2. **Le corps de la réponse**, au format JSON (dans le cas d'un code `200`, la structure contenant `candidates` vue dans la Fiche 1 ; en cas d'erreur `4xx`/`5xx`, un JSON détaillant le message d'erreur).

> **En résumé :**
> `Client PHP` ──(Requête `POST` + `Headers` + `JSON`)──▶ `Endpoint API Gemini`
> `Client PHP` ◀──(Code statut `200` + `JSON` réponse)── `Endpoint API Gemini`

---

## 4. Comment appeler une API REST depuis PHP ?

On a souvent l'habitude d'utiliser PHP **côté serveur** pour répondre à un navigateur web. Mais PHP peut aussi agir comme un **client HTTP** : il ouvre une connexion vers un autre serveur (ici Google), lui envoie une requête HTTP et lit sa réponse.

En PHP, il existe deux grandes façons de procéder :

1. **Avec l'extension native `cURL` (`curl_init`, `curl_setopt`, `curl_exec`...)** :
   C'est la solution intégrée de base à PHP, mais elle demande beaucoup de code technique et peu lisible dès qu'il faut gérer des en-têtes, du JSON, des délais d'attente (*timeout*) ou des erreurs HTTP.

2. **Avec une bibliothèque dédiée comme Guzzle** :
   **Guzzle** est le client HTTP de référence en PHP (installé via Composer). Il encapsule toute la complexité réseau pour permettre d'écrire en quelques lignes claires :
   - l'adresse de base de l'API,
   - la méthode (`POST`),
   - les en-têtes (`headers`),
   - les données encodées automatiquement en JSON,
   - la gestion propre des erreurs.

Nous verrons dans la **Fiche 3** comment utiliser Guzzle concrètement pour réaliser cet échange en PHP.

---

## 5. Exercices pratiques

Les corrections sont disponibles dans le document annexe : [`annexe-corrections.md`](annexe-corrections.md).

### Exercice 1 — Identifier les éléments d'une requête REST

Observe la requête HTTP brute ci-dessous envoyée à l'API Gemini :

```http
POST /v1beta/models/gemini-3.5-flash-lite:generateContent HTTP/1.1
Host: generativelanguage.googleapis.com
Content-Type: application/json
x-goog-api-key: AIzaSyExempleCle123

{
  "contents": [
    {
      "role": "user",
      "parts": [{ "text": "Donne 3 commandes Linux utiles." }]
    }
  ]
}
```

Identifie :
1. La méthode HTTP utilisée et pourquoi celle-ci plutôt que `GET`.
2. L'URL complète (*endpoint*) ciblée.
3. Les deux en-têtes applicatifs envoyés et le rôle de chacun.
4. Le format du corps de la requête.

### Exercice 2 — Diagnostiquer un code de statut HTTP

Lors de tes tests vers l'API Gemini, ton programme reçoit successivement les codes de statut suivants. Indique pour chacun la cause la plus probable et ce qu'il faut vérifier :

1. `403 Forbidden`
2. `400 Bad Request`
3. `404 Not Found`
4. `429 Too Many Requests`
5. `200 OK`

### Exercice 3 — Concevoir une requête REST vers un autre modèle

Tu dois rédiger la requête HTTP brute (méthode, URL complète, en-têtes et corps JSON) permettant de :
- Contacter le modèle `gemini-2.5-flash` sur le serveur `https://generativelanguage.googleapis.com` (version d'API `v1beta`).
- T'authentifier avec la clé fictive `MA_CLE_SECRETE`.
- Envoyer le prompt utilisateur `"Qu'est-ce qu'une adresse IP ?"`.

