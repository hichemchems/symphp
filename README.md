# EasyGestionBarber

Application de gestion complète pour salon de coiffure développée avec Symfony 7.0.

## 🚀 Fonctionnalités Implémentées

### 🏗️ Architecture & Sécurité
- **Symfony 7.0** avec toutes les dépendances nécessaires
- **Séparation stricte des rôles** : Admin vs Employé
- **Authentification sécurisée** avec hashage des mots de passe
- **Protection des routes** selon les rôles

### 👤 Gestion des Utilisateurs
- **Inscription** : Crée uniquement des comptes administrateurs
- **Création d'employés** : Uniquement par les admins via formulaire dédié
- **Connexion intelligente** : Redirection automatique vers le bon dashboard selon le rôle

### 📊 Dashboard Administrateur
- **Statistiques globales** : Revenus du jour/semaine/mois (HT & TTC)
- **Gestion des employés** : Liste avec commissions, création/modification
- **Rendez-vous récents** : Vue d'ensemble des activités
- **Formulaire de création d'employés** avec validation

### 👨‍💼 Dashboard Employé
- **Statistiques personnelles** : CA HT, nombre de clients, commission calculée
- **Sélection de prestations** : Boutons pour choisir les services
- **Modales statistiques** : Hebdomadaires et mensuelles avec données archivées
- **Commission automatique** : Calculée sur CA HT - 20% TVA

### 🎨 Interface Utilisateur
- **Bootstrap 5** pour un design moderne et responsive
- **Page d'accueil** avec boutons de connexion claire
- **Navigation intuitive** selon les rôles
- **Messages de feedback** pour les actions utilisateur

### 🗄️ Base de Données
- **Entités configurées** : Employee, Appointment, Package, Revenue, Charge, Statistics
- **Migrations Doctrine** pour la gestion des schémas
- **Relations entre entités** correctement définies

### 🔧 Fonctionnalités Techniques
- **CRUD complet** pour toutes les entités via EasyAdmin
- **Archivage automatique** des statistiques (quotidien/hebdomadaire/mensuel)
- **Calculs financiers** précis avec gestion TVA
- **Gestion des charges** personnalisables par l'admin

## 🛠️ Installation & Configuration

### Prérequis
- PHP 8.4+
- Composer
- Symfony CLI
- MySQL/PostgreSQL

### Installation
```bash
# Cloner le projet
git clone <repository-url>
cd EasyGestionBarber

# Installer les dépendances
composer install

# Configurer la base de données dans .env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/easygestionbarber"

# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures (optionnel)
php bin/console doctrine:fixtures:load

# Démarrer le serveur
symfony serve -d
```

### Accès à l'application
- **URL** : http://127.0.0.1:8000
- **Interface d'administration** : http://127.0.0.1:8000/admin (nécessite ROLE_ADMIN)

## 📋 Utilisation

### Pour les Administrateurs
1. **S'inscrire** via le formulaire d'inscription (crée automatiquement un compte admin)
2. **Se connecter** et accéder au dashboard admin
3. **Créer des employés** via le formulaire dédié
4. **Gérer les entités** via l'interface EasyAdmin (/admin)
5. **Consulter les statistiques** globales

### Pour les Employés
1. **Se connecter** avec les identifiants fournis par l'admin
2. **Accéder au dashboard** personnel
3. **Consulter ses statistiques** et commissions
4. **Voir les données archivées** via les modales

## 🏗️ Structure du Projet

```
EasyGestionBarber/
├── config/                 # Configuration Symfony
├── migrations/            # Migrations Doctrine
├── public/                # Assets publics
├── src/
│   ├── Controller/        # Contrôleurs
│   ├── Entity/           # Entités Doctrine
│   ├── Form/             # Formulaires
│   ├── Repository/       # Repositories Doctrine
│   └── Security/         # Classes de sécurité
├── templates/            # Templates Twig
├── tests/                # Tests
└── var/                  # Cache, logs, etc.
```

## 🔐 Rôles & Permissions

- **ROLE_ADMIN** : Accès complet à l'administration et gestion des employés
- **ROLE_EMPLOYEE** : Accès au dashboard personnel et statistiques

## 📊 Entités Principales

- **Employee** : Utilisateurs (admin/employé)
- **Appointment** : Rendez-vous
- **Package** : Prestations/forfaits
- **Revenue** : Revenus
- **Charge** : Charges (loyer, électricité, etc.)
- **Statistics** : Statistiques archivées

## 🎯 Fonctionnalités Clés

- ✅ Séparation admin/employé
- ✅ Calcul automatique des commissions
- ✅ Archivage des statistiques
- ✅ Interface responsive
- ✅ Gestion complète des entités
- ✅ Authentification sécurisée

## 📝 Notes Techniques

- Utilise Symfony 7.0 avec les dernières bonnes pratiques
- Interface Bootstrap 5 pour la responsivité
- EasyAdmin pour l'administration
- Doctrine pour l'ORM
- Architecture MVC propre et maintenable

---

**Développé avec ❤️ pour la gestion efficace des salons de coiffure**
# symphp
