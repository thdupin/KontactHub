# 🌐 KontactHub 👥

## 📝 Description
**KontactHub** est une application de gestion de contacts, développée en PHP avec Symfony. Elle permet de gérer des contacts, de les organiser en groupes et d'effectuer des opérations en masse telles que la suppression de contacts. Le projet utilise également JavaScript et la bibliothèque Bootstrap pour l'interface utilisateur, et Bootstrap Table pour afficher les données des contacts sous forme de table.

## 🛠️ Technologies utilisées
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white)
![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=for-the-badge&logo=javascript&logoColor=%23F7DF1E)
![jQuery](https://img.shields.io/badge/jquery-%230769AD.svg?style=for-the-badge&logo=jquery&logoColor=white)
![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/css3-%231572B6.svg?style=for-the-badge&logo=css3&logoColor=white)
![Bootstrap](https://img.shields.io/badge/bootstrap-%238511FA.svg?style=for-the-badge&logo=bootstrap&logoColor=white)

## 🚀 Fonctionnalités principales

- **Gestion des contacts** : Ajouter, modifier, supprimer et consulter des contacts.
- **Organisation par groupes** : Classer les contacts en groupes pour une gestion simplifiée.
- **Recherche avancée** : Recherche de contacts et de groupes avec des filtres.
- **Interface conviviale** : Interface dynamique grâce à Bootstrap et Bootstrap Table.
- **Suppression en masse** : Possibilité de supprimer plusieurs contacts à la fois.

## ⚙️ Prérequis

Avant de démarrer, assurez-vous d'avoir les prérequis suivants installés sur votre machine :

- PHP 8.0 ou supérieur
- Composer
- DB Browser for SQLite (ou [SQLite Online](https://www.sqliteonline.com/) si vous ne voulez pas installer DB Browser for SQLite)

## 🧰 Documentations utiles

* [Symfony](https://symfony.com/doc/current/index.html)
* [PHP](https://www.php.net/docs.php)
* [jQuery](https://api.jquery.com/)
* [Bootstrap](https://getbootstrap.com/)

## 📜 Installation

### 1. Clonez le repository

Tout d'abord, clonez le repository depuis GitHub :

```bash
git clone https://github.com/thdupin/KontactHub.git
cd KontactHub
```

### 2. Créez votre fichier `.env`

Copiez le fichier `.env.example` pour créer votre propre fichier `.env` :

```bash
cp .env.example .env
```

### 3. Installez les dépendances

Une fois le projet cloné, vous devez installer les dépendances PHP via Composer :

```bash
composer install
```

### 4. Créez et configurez la base de données SQLite
> [!TIP]
> Pour cette partie, vous pouvez passez de **l'étape 1** à **l'étape 4** directement car le dossier `/migrations` existe et contient déjà une migration normalement (si jamais il n'y en a pas, suivez toutes les étapes pour créer et configurer la base de données proprement).

#### 1. Définissez l'URL de la base de données dans .env
Si vous utilisez SQLite, spécifiez l'emplacement du fichier de base de données dans le fichier `.env` :
```bash
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```
Cela indique que le fichier `data.db` sera créé automatiquement dans le répertoire `var` du projet.

#### 2. Créez la base de données
Exécutez la commande suivante pour créer le fichier SQLite (même si le fichier sera aussi généré lors de l'exécution des migrations) :
```bash
php bin/console doctrine:database:create
```

#### 3. Générer les fichiers de migration
Créez un fichier de migration basé sur vos entités Doctrine :
```bash
php bin/console make:migration
```
Cela génère un fichier de migration dans le dossier `migrations/`.

#### 4. Exécutez les migrations
Appliquez les migrations pour créer les tables dans votre base de données SQLite :
```bash
php bin/console doctrine:migrations:migrate
```
Cette commande exécute les fichiers de migration et initialise les structures nécessaires dans la base de données.

### 5. Lancez le serveur Symfony
Une fois la base de données créée et configurée, vous devez lancer le serveur Symfony :
```bash
symfony serve
```

### 6. Accédez à l'application
Vous pouvez maintenant ouvrir votre navigateur et accéder à l'application via l'URL suivante :
```bash
http://127.0.0.1:8000
```

## 🏗️ Structure du projet
```
KontactHub/                 
├── bin/                   
├── config/
│   ├── packages/
│   ├── routes/
│   ├── routes.yaml
│   └── services.yaml
├── migrations/
├── public/
│   ├── css/
│   ├── images/
│   ├── uploads/                # Ignoré par Git (spécifié dans .gitignore)
│   │   └── photos/             
│   └── index.php
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Repository/
│   └── Service/
├── templates/
│   ├── contact/
│   │   ├── add.html.twig
│   │   ├── edit.html.twig
│   │   └── index.html.twig
│   ├── group/
│   │   ├── edit.html.twig
│   │   └── index.html.twig
│   └── base.html.twig
├── var/                        # Ignoré par Git(spécifié dans .gitignore)
│   ├── cache/
│   ├── log/
│   └── data.db
├── vendor/                     # Ignoré par Git (spécifié dans .gitignore)
├── .env                        # Ignoré par Git (spécifié dans .gitignore)
├── .env.example
├── .gitignore
├── LICENSE.txt
├── README.md
├── composer.json
├── composer.lock               # Ignoré par Git (spécifié dans .gitignore)
└── symfony.lock
```

## 🧑‍💻 Auteurs
* **Théo DUPIN** - *Développeur principal* - https://github.com/thdupin

## 🤝 Contribuer
Si vous souhaitez contribuer à ce projet, vous pouvez soumettre une pull request. Assurez-vous que toutes les modifications respectent les directives de codage avant de soumettre.

### 1. Forkez le projet depuis GitHub
* Allez sur la page du repository sur GitHub (https://github.com/thdupin/KontactHub.git)
* Cliquez sur le bouton **Fork** en haut à droite pour créer une copie du projet dans votre compte GitHub
  
### 2. Clonez votre fork localement
```bash
git clone https://github.com/votre-utilisateur/KontactHub.git
cd KontactHub
```

### 3. Créez une nouvelle branche pour votre fonctionnalité
```bash
git checkout -b feature-nouvelle-fonctionnalité
```

### 4. Apportez vos modifications dans le code

### 5. Committez vos modifications
```bash
git commit -am "Ajout d'une nouvelle fonctionnalité"
```

### 6. Poussez votre branche sur votre fork
```bash
git push origin feature-nouvelle-fonctionnalité
```

### 7. Ouvrez une pull request
* Allez sur la page de votre fork sur GitHub.
* Cliquez sur le bouton **Compare & pull** request.
* Ajoutez une description claire et détaillée de vos modifications, puis soumettez la pull request.

Une fois la pull request créée, elle pourra être examinée et fusionnée par les mainteneurs du projet. 🎉

# Merci d'utiliser KontactHub ! 🚀
