
````markdown
# Project Management System

Un système web de gestion de projets collaboratifs permettant aux utilisateurs de créer des projets, de leur associer des tâches, et de suivre leur avancement via une interface conviviale.

## 🧱 Structure de la base de données

Le schéma relationnel comprend les entités suivantes :

- **users** : Gère les utilisateurs (admins, managers, membres…)
- **projects** : Projets créés et gérés par les utilisateurs
- **tasks** : Tâches associées à un projet, affectées à des utilisateurs

### Modèle physique (Mermaid ER Diagram)

```mermaid
erDiagram
    users {
        INT id PK
        VARCHAR name
        VARCHAR email UNIQUE
        VARCHAR password
        ENUM role
        TINYINT active
        DATETIME created_at
        DATETIME updated_at
    }

    projects {
        INT id PK
        VARCHAR title
        TEXT description
        ENUM status
        DATE start_date
        DATE end_date
        INT created_by FK
        INT manager_id FK
        DATETIME created_at
        DATETIME updated_at
    }

    tasks {
        INT id PK
        VARCHAR title
        TEXT description
        ENUM status
        ENUM priority
        DATE due_date
        INT project_id FK
        INT assigned_to FK
        INT created_by FK
        DATETIME created_at
        DATETIME updated_at
    }

    users ||--o{ projects : creates
    users ||--o{ projects : manages
    users ||--o{ tasks : creates
    users ||--o{ tasks : assigned
    projects ||--o{ tasks : contains
````

## 🛠 Technologies utilisées

* **Frontend** : HTML / CSS / JS / Bootstrap 3
* **Backend** : PHP (structure MVC simplifiée)
* **Base de données** : MySQL
* **Librairies** :

  * [DataTables](https://datatables.net/) pour les tableaux dynamiques
  * [Chosen](https://harvesthq.github.io/chosen/) pour les listes déroulantes améliorées
  * jQuery / AJAX pour les interactions asynchrones

## 📁 Arborescence du projet

```
project-management/
├── frontend/
│   ├── pages/
│   │   ├── dashboard.php
│   │   ├── tasks/
│   │   │   └── create.php
│   ├── includes/
│   │   ├── header.php
│   │   ├── functions.php
│   └── assets/
│       ├── css/
│       └── js/
├── backend/
│   ├── controllers/
│   ├── models/
│   └── api/
│       └── dashboard.php
├── database/
│   └── schema.sql
└── README.md
```

## 🚀 Fonctionnalités principales

* Gestion des utilisateurs avec rôles (`admin`, `manager`, `member`)
* Création et modification de projets
* Affectation de tâches à des utilisateurs
* Filtres et tableaux dynamiques pour les statistiques
* Journal d’activité (log)
* Interface responsive avec Bootstrap

## 📌 À faire / améliorations possibles

* Authentification avec tokens
* Gestion fine des permissions
* Statistiques graphiques (Chart.js)
* Notifications en temps réel (WebSocket)
* Support multilingue

## 👩‍💻 Auteur

* \ – Développeur Full Stack
* Projet académique / personnel

---

> 💡 Pour exécuter ce projet, il suffit d'importer la base de données, configurer `config.php`, et lancer un serveur local PHP.

```

```
