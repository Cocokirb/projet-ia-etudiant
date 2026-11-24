# TP — Chatbot en ligne de commande avec PHP et l'API Gemini

**Public :** BTS SIO  
**Mode d'exécution :** Application PHP en **mode console (CLI)** — aucune interface web (HTML/CSS) n'est demandée.

---

## 1. Contexte et objectif

L'objectif de ce TP est de concevoir et de développer en PHP un **chatbot interactif fonctionnant dans le terminal**, capable de dialoguer avec le modèle d'intelligence artificielle **Google Gemini** via son API REST.

Pour mener à bien ce projet, appuyez-vous sur les 3 fiches de référence :
1. [Fiche 1 — Le langage JSON et le format des échanges avec Gemini](01-json-et-gemini.md)
2. [Fiche 2 — Communiquer avec un LLM via une API REST](02-api-rest.md)
3. [Fiche 3 — Appeler une API REST en PHP avec Guzzle](03-guzzle-php.md)

---

## 2. Mise en place de l'environnement (à faire avant de coder)

### A. Découvrir et utiliser Composer

**Composer** est le gestionnaire de dépendances de PHP (l'équivalent de `npm` en JavaScript ou `pip` en Python). Il permet d'installer automatiquement des bibliothèques externes comme **Guzzle** (avec toutes leurs sous-dépendances) et de charger leurs classes sans avoir à écrire des dizaines de `require`.

1. **Vérifier l'installation** dans votre terminal :
   ```bash
   composer --version
   ```
2. **Créer le dossier du projet**, initialiser Git et installer Guzzle :
   ```bash
   git init
   composer require guzzlehttp/guzzle
   ```
   *(Si Composer demande de confirmer la création de `composer.json`, validez.)*

Cette commande crée automatiquement trois éléments à la racine du projet :

| Élément généré | Rôle | Versionné dans Git ? |
| :--- | :--- | :--- |
| **`composer.json`** | Liste les bibliothèques dont votre projet dépend (ex. `guzzlehttp/guzzle`). | **Oui** |
| **`composer.lock`** | Enregistre les versions exactes installées pour que le projet fonctionne à l'identique sur un autre poste. | **Oui** |
| **`vendor/`** | Contient tout le code téléchargé des bibliothèques tierces. | **Non** (à exclure dans `.gitignore`) |

**Comment l'utiliser dans votre code PHP :**  
Ajoutez simplement cette ligne au début du script qui utilise Guzzle pour activer le chargement automatique des classes (*autoloading*) :

```php
require_once __DIR__ . '/vendor/autoload.php';
```

> **Bon à savoir :** Si vous récupérez votre dépôt Git sur un autre ordinateur (`git clone`), le dossier `vendor/` sera absent. Exécutez simplement `composer install` dans le dossier du projet pour tout réinstaller automatiquement.

---

### B. Configuration Git et sécurité de la clé API

1. Créez un fichier `.gitignore` à la racine du projet pour **exclure impérativement** :
   - le dossier `/vendor/` (réinstallable à tout moment avec `composer install`),
   - le fichier contenant votre clé API personnelle (ex. `/config/config.php` ou `.env`).

> **Règle de sécurité (cybersécurité) :** Une clé API est un secret personnel (souvent associé à un quota ou à une facturation). **Ne la mettez jamais en dur dans votre code source** et ne la poussez jamais sur un dépôt Git (GitHub, GitLab...).
> Utilisez un fichier de configuration local ignoré par Git, accompagné d'un fichier modèle d'exemple (ex. `config.example.php` contenant `'VOTRE_CLE_API_GEMINI'`) qui, lui, est versionné.

### C. Obtenir une clé API Gemini

1. Connectez-vous sur **Google AI Studio** avec un compte Google et générez une clé API.
2. Renseignez cette clé et le nom du modèle à utiliser (par exemple `gemini-3.5-flash-lite`, ou celui indiqué par votre enseignant) dans votre fichier de configuration local.

### D. Dépannage SSL/HTTPS sous Windows (`cURL error 60`)

Lors de vos premiers appels avec Guzzle sous Windows (Laragon, WAMP, PHP autonome), vous pouvez rencontrer l'erreur suivante :  
`cURL error 60: SSL certificate problem: unable to get local issuer certificate`

Cela signifie que PHP ne sait pas où trouver la liste des autorités de certification pour vérifier le certificat HTTPS de Google. Pour corriger ce problème proprement :
1. Téléchargez le fichier `cacert.pem` depuis le site officiel de cURL (`https://curl.se/ca/cacert.pem`).
2. Placez-le dans votre dossier PHP (par exemple `C:\php\extras\ssl\cacert.pem`).
3. Deux options au choix :
   - **Option recommandée (globale) :** dans votre fichier `php.ini`, renseignez les deux directives :
     ```ini
     curl.cainfo = "C:/php/extras/ssl/cacert.pem"
     openssl.cafile = "C:/php/extras/ssl/cacert.pem"
     ```
   - **Option par projet :** passez le chemin de `cacert.pem` à l'option `'verify'` lors de la création du `Client` Guzzle (avec une valeur `null` par défaut sous macOS/Linux).

> ⚠️ Ne contournez pas ce problème avec `'verify' => false` : désactiver la vérification SSL expose les échanges aux attaques *Man-in-the-Middle*.

---

## 3. Cahier des charges fonctionnel

### Comportement général (mode console)

Le programme se lance dans un terminal via la commande :

```bash
php <votre_script_principal>.php
```

1. **Accueil :** Au démarrage, l'application affiche un titre et indique à l'utilisateur comment quitter (par exemple en saisissant `quitter`).
2. **Boucle de conversation :**
   - Le programme invite l'utilisateur à saisir une question à l'aide de la fonction PHP **`readline()`** (ex. `readline('Vous > ')`).
   - Si l'utilisateur saisit `quitter` (quelle que soit la casse : `quitter`, `QUITTER`, `Quitter`), le programme affiche un message d'au revoir et se termine proprement.
   - Si l'utilisateur appuie simplement sur Entrée ou ne saisit que des espaces, aucune requête n'est envoyée à l'API et une nouvelle saisie est demandée.
   - Sinon, le prompt est envoyé à l'API Gemini et la réponse textuelle est affichée dans le terminal (ex. précédée de `Gemini > `).
3. **Formatage adapté au terminal :**
   - Par défaut, Gemini répond souvent en syntaxe Markdown (`**gras**`, `# titres`, listes avec `*`), ce qui est peu lisible dans une console brute.
   - Utilisez le champ `systemInstruction` dans la requête JSON envoyée à Gemini pour lui demander de **répondre uniquement en texte brut, sans mise en forme Markdown**.
4. **Gestion des erreurs :**
   - En cas d'anomalie (fichier de configuration absent, clé API non renseignée, coupure réseau, erreur HTTP `400`/`403`/`429`, réponse JSON inattendue ou vide), l'application **ne doit pas planter avec une erreur fatale PHP**.
   - Un message d'erreur clair et compréhensible doit être affiché à l'utilisateur dans la console.

### Exemple complet d'exécution

Voici un exemple de session interactive attendue dans le terminal (en supposant que votre script principal se nomme `main.php`) :

```text
$ php main.php
====================================
      CHATBOT GEMINI EN PHP
====================================
Saisissez 'quitter' pour terminer.

Vous > Bonjour ! Peux-tu expliquer en 2 phrases ce qu'est une API REST ?

Gemini > Une API REST est une interface qui permet à deux applications de communiquer via le protocole HTTP en utilisant des méthodes standards comme GET ou POST. Elle s'appuie généralement sur le format JSON pour échanger des données de manière simple et structurée.

Vous > 
Vous > Quelle est la différence entre les codes HTTP 401 et 403 ?

Gemini > Le code 401 (Unauthorized) indique que l'utilisateur n'est pas authentifié ou que sa clé ou ses identifiants sont invalides. Le code 403 (Forbidden) signifie que l'utilisateur est bien identifié, mais qu'il n'a pas les droits nécessaires pour accéder à la ressource demandée.

Vous > Donne-moi une requête SQL pour lister les étudiants de la table etudiants triés par nom.

Gemini > Voici un exemple de requête SQL :
SELECT id, nom, prenom FROM etudiants ORDER BY nom ASC;

Vous > Quitter

Au revoir !
```

> **À noter dans cet exemple :**
> - La saisie vide (simple appui sur `Entrée` après le deuxième `Vous > `) est ignorée sans appeler l'API.
> - Les réponses de Gemini sont affichées en **texte brut** (sans `**gras**`, `# titres` ni puces Markdown `*`).
> - La commande `Quitter` (insensible à la casse) met fin à la boucle proprement.

---

## 4. Spécifications techniques attendues

### Architecture du code

Pour garder un code propre, maintenable et testable, **séparez les responsabilités** en plusieurs fichiers :

1. **Un dossier de configuration** :
   - Un fichier modèle versionné (sans secret) documentant les paramètres attendus (`gemini_api_key`, `gemini_model`, et éventuellement le chemin SSL).
   - Le fichier de configuration local réel (ignoré par Git).
2. **Un module dédié à la communication avec Gemini** :
   - Expose une fonction claire (ex. prenant un prompt `string` et retournant la réponse `string`).
   - Charge et valide la configuration (lève une `RuntimeException` si un paramètre est manquant ou non configuré).
   - Construit le client Guzzle (`base_uri`, `timeout`, `verify`).
   - Prépare le tableau de données (`systemInstruction` + `contents`) et envoie la requête `POST` avec l'en-tête `x-goog-api-key` et l'option `'json'`.
   - Intercepte les `GuzzleException` et relève une exception applicative explicite.
   - Décode la réponse avec `json_decode(..., true, 512, JSON_THROW_ON_ERROR)`.
   - Extrait de manière défensive le texte de `candidates[0].content.parts` (en parcourant les éléments de `parts` contenant `'text'`) et lève une exception si aucun texte n'est présent.
3. **Le script principal (point d'entrée CLI)** :
   - Gère uniquement l'interface console (bannière, boucle `readline()`, commandes de sortie, appel à la fonction Gemini dans un bloc `try / catch (Throwable $e)` et affichage).

### Qualité de code PHP

- Activez le typage strict en tête de chaque fichier PHP : `declare(strict_types=1);`.
- Typez les paramètres et les valeurs de retour de vos fonctions.
- Utilisez `PHP_EOL` (ou `\n`) pour les retours à la ligne en console.

---

## 5. Évolutions possibles (bonus)

Si vous terminez le socle de base en avance, améliorez votre chatbot avec une ou plusieurs des fonctionnalités suivantes :

1. **Mémoire de la conversation (multi-tours) :**
   - Conservez un tableau d'historique alternant les rôles `"user"` et `"model"` (cf. Fiche 1) afin que Gemini se souvienne des messages précédents dans la même session.
2. **Commandes spéciales en console :**
   - `/reset` : réinitialise l'historique de la conversation.
   - `/tokens` : affiche le nombre de tokens consommés lors du dernier échange (via `usageMetadata` dans la réponse JSON).
3. **Personnalisation du rôle :**
   - Adaptez `systemInstruction` pour spécialiser le chatbot (ex. assistant de révision BTS SIO, correcteur de requêtes SQL, traducteur technique).

---

## 6. Critères de validation du TP

- [ ] L'application s'exécute sans erreur en ligne de commande avec `php` et utilise `readline()`.
- [ ] Les saisies vides sont ignorées et le mot-clé `quitter` arrête proprement le programme.
- [ ] Les réponses de Gemini s'affichent en texte brut (sans balisage Markdown).
- [ ] Une clé API invalide ou une erreur réseau affiche un message propre sans arrêter brutalement le script.
- [ ] Le dépôt Git ne contient **ni** le dossier `vendor/`, **ni** votre clé API secrète.
- [ ] Le code est découpé proprement (configuration / service Gemini / interface console) et utilise le typage strict.
