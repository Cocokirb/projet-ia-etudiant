# Fiche 1 — Le langage JSON et le format des échanges avec Gemini

**Objectif :** Comprendre la syntaxe JSON, savoir la manipuler en PHP et maîtriser la structure exacte des messages envoyés et reçus par l'API Gemini dans ce projet.

---

## 1. Pourquoi utilise-t-on JSON ?

Notre programme PHP doit dialoguer avec les serveurs de Google qui hébergent le modèle Gemini. Ces deux systèmes ne tournent pas sur la même machine et n'utilisent pas forcément le même langage de programmation en interne.

Pour se comprendre, ils utilisent un format texte universel et léger : **JSON** (*JavaScript Object Notation*).

- **Côté envoi :** PHP transforme ses tableaux en texte JSON pour les envoyer à Gemini.
- **Côté réception :** Gemini renvoie du texte JSON que PHP retransforme en tableaux exploitables.

---

## 2. Les règles de syntaxe JSON

Un document JSON est du simple texte structuré autour de **deux structures** et de **quelques types de valeurs**.

### Les deux structures de base

1. **L'objet `{ ... }`** : une collection de paires `"clé": valeur`, séparées par des virgules.
   - Les clés sont **toujours** des chaînes de caractères entre guillemets doubles `"`.
2. **Le tableau `[ ... ]`** : une liste ordonnée de valeurs, séparées par des virgules.

### Les types de valeurs autorisés

| Type | Exemple | Remarque |
| :--- | :--- | :--- |
| Chaîne de caractères | `"Bonjour"` | Guillemets doubles obligatoires |
| Nombre | `42`, `3.14` | Entier ou décimal, sans guillemets |
| Booléen | `true`, `false` | En minuscules, sans guillemets |
| Valeur nulle | `null` | En minuscules |
| Objet | `{"nom": "Alice", "age": 20}` | Clés entre guillemets doubles |
| Tableau | `["PHP", "SQL", "JSON"]` | Peut contenir tout type, y compris des objets |

### Exemple complet

```json
{
  "modele": "gemini-3.5-flash-lite",
  "temperature": 0.7,
  "stream": false,
  "outils": ["code", "texte"],
  "auteur": {
    "nom": "Lamy",
    "bts": "SIO"
  }
}
```

### Les 4 erreurs de syntaxe les plus fréquentes

JSON est strict : la moindre erreur empêche son décodage.

1. **Guillemets simples :** `{'nom': 'Alice'}` est **invalide** → il faut `{"nom": "Alice"}`.
2. **Clé sans guillemets :** `{nom: "Alice"}` est **invalide** → il faut `{"nom": "Alice"}`.
3. **Virgule finale (*trailing comma*) :** `["PHP", "JSON",]` est **invalide** → pas de virgule après le dernier élément.
4. **Commentaires :** `// mon commentaire` ou `/* ... */` sont **interdits** en JSON standard.

---

## 3. Manipuler du JSON en PHP

En PHP, la correspondance avec les tableaux est directe :
- Un **tableau associatif** PHP (`['cle' => 'valeur']`) devient un **objet** JSON (`{"cle": "valeur"}`).
- Un **tableau indexé** PHP (`['a', 'b']`) devient un **tableau** JSON (`["a", "b"]`).

PHP fournit deux fonctions natives : `json_encode()` et `json_decode()`.

### Passer de PHP vers JSON (`json_encode`)

```php
<?php

$utilisateur = [
    'prenom' => 'Camille',
    'actif'  => true,
    'notes'  => [14, 16, 15],
];

$json = json_encode($utilisateur, JSON_THROW_ON_ERROR);
echo $json;
// Affiche : {"prenom":"Camille","actif":true,"notes":[14,16,15]}
```

> **Astuce de lecture :** Pour afficher un JSON indenté et plus lisible lors du débogage, on ajoute l'option `JSON_PRETTY_PRINT` :
> `json_encode($utilisateur, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);`

### Passer de JSON vers PHP (`json_decode`)

```php
<?php

$jsonRecu = '{"prenom":"Camille","actif":true,"notes":[14,16,15]}';

$donnees = json_decode(
    $jsonRecu,
    true,                 // true = retourne un tableau associatif (et non un objet stdClass)
    512,                  // profondeur maximale d'imbrication (valeur par défaut)
    JSON_THROW_ON_ERROR   // lève une exception JsonException si le JSON est invalide
);

echo $donnees['prenom'];   // Camille
echo $donnees['notes'][1]; // 16
```

> **Bonne pratique :** Toujours passer `true` (pour manipuler de simples tableaux associatifs) et `JSON_THROW_ON_ERROR` afin de détecter immédiatement un JSON mal formé ou tronqué grâce à un bloc `try / catch`.

---

## 4. Le format des échanges avec Gemini

L'API Gemini attend une structure JSON précise pour la question envoyée, et renvoie une autre structure JSON pour la réponse.

### A. Le JSON envoyé à Gemini (la requête)

Voici un exemple de corps JSON à envoyer à l'API Gemini pour poser une question :

```json
{
  "systemInstruction": {
    "parts": [
      {
        "text": "Réponds toujours en texte brut, sans syntaxe Markdown."
      }
    ]
  },
  "contents": [
    {
      "role": "user",
      "parts": [
        {
          "text": "Explique le protocole HTTP en deux phrases."
        }
      ]
    }
  ]
}
```

#### Décryptage des champs

- **`systemInstruction`** *(optionnel)* : définit le rôle ou les règles générales que Gemini doit suivre sur l'ensemble de la conversation (ici, ne pas répondre en Markdown).
- **`contents`** : **tableau** contenant la liste des messages échangés.
  - Chaque élément de `contents` représente un tour de parole avec :
    - **`role`** : `"user"` (le message vient de l'utilisateur) ou `"model"` (le message vient de Gemini, utile si on envoie l'historique).
    - **`parts`** : **tableau** des morceaux qui composent ce message.
      - Pourquoi un tableau `parts` plutôt qu'un simple champ `"text"` ? Parce qu'un même message peut contenir plusieurs éléments (par exemple du texte **et** une image). Pour du texte seul, on met un objet `{"text": "..."}` dans `parts`.

### B. Le JSON renvoyé par Gemini (la réponse)

Quand tout se passe bien, Gemini répond avec un JSON ressemblant à ceci :

```json
{
  "candidates": [
    {
      "content": {
        "parts": [
          {
            "text": "HTTP est un protocole de communication qui permet à un client d'envoyer des requêtes à un serveur web et d'en recevoir des réponses."
          }
        ],
        "role": "model"
      },
      "finishReason": "STOP"
    }
  ],
  "usageMetadata": {
    "promptTokenCount": 24,
    "candidatesTokenCount": 28,
    "totalTokenCount": 52
  }
}
```

#### Décryptage des champs

- **`candidates`** : tableau des propositions de réponses générées par le modèle. Par défaut, il y en a une seule à l'index `0`.
- **`content.parts`** : tableau des fragments de réponse produits par Gemini (chaque fragment textuel possède une clé `"text"`).
- **`finishReason`** : indique pourquoi la génération s'est arrêtée (`"STOP"` signifie une fin normale et complète).
- **`usageMetadata`** : statistiques sur le nombre de *tokens* (unités de texte) consommés pour la question et la réponse.

#### Exploiter la réponse en PHP

Une fois ce JSON décodé avec `json_decode($json, true, 512, JSON_THROW_ON_ERROR)` dans une variable `$resultat`, on accède au texte généré en suivant l'arborescence :

```php
$texte = $resultat['candidates'][0]['content']['parts'][0]['text'];
```

Le chemin suit exactement l'imbrication du JSON reçu : objet racine → tableau `candidates` (1er élément `[0]`) → objet `content` → tableau `parts` (1er morceau `[0]`) → clé `'text'`. Notez qu'une réponse peut parfois contenir plusieurs éléments dans `parts` : il est donc courant de parcourir ce tableau avec une boucle `foreach`.

---

## 5. Exercices pratiques

Les corrections sont disponibles dans le document annexe : [`annexe-corrections.md`](annexe-corrections.md).

### Exercice 1 — Corriger un JSON invalide

Le document JSON ci-dessous contient **5 erreurs de syntaxe**. Identifie-les et réécris un JSON valide :

```text
{
  'modele': 'gemini-3.5-flash-lite',
  maxTokens: 500,
  "actif": True,
  "tags": ["chatbot", "php",] // liste de mots-clés
}
```

### Exercice 2 — Encoder et décoder en PHP

1. Écris le code PHP permettant de transformer le tableau suivant en chaîne JSON (avec gestion d'erreur) :
   ```php
   $configBot = [
       'nom'         => 'Assistant SIO',
       'temperature' => 0.5,
       'langues'     => ['fr', 'en'],
   ];
   ```
2. Écris le code PHP permettant de décoder la chaîne JSON suivante dans un tableau associatif et d'afficher la valeur `"fr"` :
   ```php
   $reponseJson = '{"statut":"ok","parametres":{"langue":"fr","version":2}}';
   ```

### Exercice 3 — Construire une requête Gemini multi-tours

À l'image du format vu en section 4.A, rédige le JSON d'une requête envoyée à Gemini qui contient :
- Une consigne système (`systemInstruction`) demandant de répondre en une seule phrase.
- Un historique de conversation (`contents`) de **3 messages** dans l'ordre :
  1. L'utilisateur demande : `"Qu'est-ce que PHP ?"`
  2. Le modèle répond : `"PHP est un langage de script côté serveur utilisé pour le développement web."`
  3. L'utilisateur enchaîne : `"Donne un exemple d'utilisation."`

### Exercice 4 — Lire une réponse Gemini

On reçoit la réponse JSON suivante et on la décode en PHP dans une variable `$resultat` (tableau associatif) :

```json
{
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
}
```

1. Quelle expression PHP permet d'accéder directement à `"Deuxième partie."` ?
2. Quelle expression PHP permet d'afficher la raison d'arrêt (`"STOP"`) ?
3. Quelle expression PHP permet de récupérer le nombre total de tokens (`30`) ?
