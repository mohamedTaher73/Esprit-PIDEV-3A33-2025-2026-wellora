# WellCare Connect - Résumé Global du Projet

## 📋 Table des Matières

1. [Aperçu du Projet](#aperçu-du-projet)
2. [Stack Technologique](#stack-technologique)
3. [Les 6 Modules Principaux](#les-6-modules-principaux)
   - [Module 1: Authentification & Gestion des Utilisateurs](#module-1-authentification--gestion-des-utilisateurs)
   - [Module 2: Consultation](#module-2-consultation)
   - [Module 3: Journal de Santé](#module-3-journal-de-santé)
   - [Module 4: Nutrition](#module-4-nutrition)
   - [Module 5: Fitness](#module-5-fitness)
   - [Module 6: Parcours Santé](#module-6-parcours-santé)
4. [Tableaux de Bord Professionnels](#tableaux-de-bord-professionnels)
5. [Entités de Base de Données](#entités-de-base-de-données)
6. [Statistiques du Projet](#statistiques-du-projet)

---

## 1. Aperçu du Projet

**Nom du Projet:** WellCare Connect  
**Type:** Application Web de Santé (Symfony 6.4)  
**Description:** Une plateforme de santé complète reliant les patients avec les professionnels médicaux, nutritionnistes, coaches, et offrant des outils de suivi de santé, téléconsultations, fitness, et communauté de trails/randonnée.

### Types d'Utilisateurs (5 Acteurs)

| Acteur | Rôle | Description |
|--------|------|-------------|
| **Patient** | `ROLE_PATIENT` | Utilisateur standard cherchant des services de santé |
| **Médecin** | `ROLE_MEDECIN` | Professionnel médical offrant des consultations |
| **Coach** | `ROLE_COACH` | Coach sportif et bien-être |
| **Nutritionniste** | `ROLE_NUTRITIONIST` | Spécialiste en nutrition |
| **Administrateur** | `ROLE_ADMIN` | Administrateur de la plateforme |

---

## 2. Stack Technologique

| Catégorie | Technologie |
|-----------|-------------|
| **Framework** | Symfony 6.4 |
| **Base de données** | Doctrine ORM |
| **Frontend** | Twig, JavaScript (Alpine.js), CSS |
| **Authentification** | Symfony Native Security (Sans FOSUserBundle) |
| **2FA** | Scheb 2FA |
| **Upload de fichiers** | Vich Uploader |
| **Email** | Symfony Mailer |
| **QR Code** | Endroid QR Code |
| **OAuth** | KNP OAuth2 Client |
| **IA** | Services Python AI |
| **Images** | Liip Imagine |

---

## 3. Les 6 Modules Principaux

### Module 1: Authentification & Gestion des Utilisateurs

**Route:** `/auth/*`, `/register/*`, `/login/*`

#### Fonctionnalités
- **Inscription Utilisateurs**
  - Inscription patient avec vérification email
  - Inscription professionnel (Médecin, Coach, Nutritionniste) avec vérification de diplôme
  - Protection Captcha
  
- **Système de Connexion**
  - Authentification email/mot de passe
  - Fonctionnalité "Se souvenir de moi"
  - Protection contre les attaques (SQL injection, XSS)

- **Gestion des Mots de Passe**
  - Mot de passe oublié avec lien de réinitialisation
  - Réinitialisation avec expiration
  - Validation de force du mot de passe

- **Authentification à Deux Facteurs (2FA)**
  - 2FA basée sur TOTP
  - Codes de sauvegarde
  - Gestion des appareils de confiance

- **Vérification Professionnelle**
  - Upload de diplôme (PDF/Image)
  - Validation du numéro de licence
  - Workflow d'approbation admin
  - Notifications email

#### Contrôleurs
- [`AuthController.php`](wellcare-connect3/src/Controller/AuthController.php) - Authentification principale
- [`Admin/ProfessionalVerificationController.php`](wellcare-connect3/src/Controller/Admin/ProfessionalVerificationController.php) - Vérification professionnelle
- [`Auth/TwoFactor/TwoFactorController.php`](wellcare-connect3/src/Controller/Auth/TwoFactor/TwoFactorController.php) - Gestion 2FA

#### Services
- [`DiplomaVerificationService.php`](wellcare-connect3/src/Service/DiplomaVerificationService.php) - Vérification diplôme
- [`EmailVerificationService.php`](wellcare-connect3/src/Service/EmailVerificationService.php) - Vérification email
- [`PasswordResetService.php`](wellcare-connect3/src/Service/PasswordResetService.php) - Réinitialisation mot de passe
- [`LoginValidationService.php`](wellcare-connect3/src/Service/LoginValidationService.php) - Sécurité connexion
- [`CaptchaService.php`](wellcare-connect3/src/Service/CaptchaService.php) - Génération Captcha

---

### Module 2: Consultation

**Route:** `/appointment/*`, `/teleconsultation/*`

#### A. Rendez-vous & Planning

**Fonctionnalités**
- **Tableau de Bord Patient**
  - Voir les rendez-vous à venir
  - Accès rapide à la réservation
  
- **Recherche Médecin**
  - Recherche par nom
  - Filtrer par spécialité
  - Filtrer par date de disponibilité
  - Badge médecin vérifié

- **Processus de Réservation**
  - Sélection date/heure
  - Motif de la visite
  - Confirmation de réservation
  - Notifications email

- **Gestion des Rendez-vous**
  - Voir détails du rendez-vous
  - Annuler rendez-vous
  - Reprogrammer rendez-vous
  - Historique des rendez-vous

- **Gestion Planning Médecin**
  - Vue semaine/mois
  - Créneaux de disponibilité
  - Gestion des absences
  - Substitution

#### Contrôleurs
- [`AppointmentController.php`](wellcare-connect3/src/Controller/AppointmentController.php) - Logique rendez-vous
- [`DoctorScheduleController.php`](wellcare-connect3/src/Controller/DoctorScheduleController.php) - Planning médecin

#### Templates
- `appointment/patient-dashboard.html.twig`
- `appointment/search-doctors.html.twig`
- `appointment/booking-flow.html.twig`
- `appointment/consultation-room.html.twig`

---

#### B. Téléconsultation

**Fonctionnalités**
- **Salle d'Attente**
  - Vérification système (caméra, micro)
  - Test audio/vidéo
  - Questionnaire pré-consultation

- **Salle de Consultation**
  - Interface visioconférence
  - Chat en temps réel
  - Partage d'écran
  - Outils médicaux

- **Outils Médicaux**
  - Notes SOAP (Subjective, Objective, Assessment, Plan)
  - Rédaction d'ordonnances
  - Calculateurs médicaux

- **Gestion des Ordonnances**
  - Créer des ordonnances
  - Envoyer à la pharmacie
  - Historique des ordonnances

#### Contrôleurs
- [`TeleconsultationController.php`](wellcare-connect3/src/Controller/TeleconsultationController.php)

#### Templates
- `teleconsultation/waiting-room.html.twig`
- `teleconsultation/consultation-room.html.twig`
- `teleconsultation/soap-notes.html.twig`
- `teleconsultation/prescription-writer.html.twig`

---

### Module 3: Journal de Santé

**Route:** `/health/*`, `/healthentry/*`, `/healthjournal/*`

#### Fonctionnalités
- **Tableau de Bord Santé**
  - Affichage signes vitaux (fréquence cardiaque, tension artérielle, etc.)
  - Entrées de santé récentes
  - Rendez-vous à venir
  - Insights santé IA

- **Dossier Médical**
  - Historique médical
  - Ajout d'entrées de santé
  - Suivi des symptômes

- **Résultats de Laboratoire**
  - Upload résultats labo (PDF)
  - Voir résultats d'analyses
  - Filtrage par date

- **Ordonnances**
  - Voir ordonnances actives
  - Historique des ordonnances
  - Liste des médicaments

- **Facturation**
  - Voir factures
  - Historique des paiements
  - Information assurance

- **Analyses de Santé**
  - Analyse des tendances
  - Visualisations graphiques
  - Export données santé

- **Fonctionnalités d'Accessibilité**
  - Visualisation carte du corps
  - Entrée vocale pour journal
  - Mode contraste élevé
  - Support lecteur d'écran

#### Contrôleurs
- [`HealthController.php`](wellcare-connect3/src/Controller/HealthController.php) - Logique principale santé
- [`HealthentryController.php`](wellcare-connect3/src/Controller/HealthentryController.php) - Entrées santé
- [`HealthjournalController.php`](wellcare-connect3/src/Controller/HealthjournalController.php) - Journal santé
- [`HealthReportController.php`](wellcare-connect3/src/Controller/HealthReportController.php) - Rapports santé
- [`HealthCalendarController.php`](wellcare-connect3/src/Controller/HealthCalendarController.php) - Calendrier santé

#### Services
- [`Health/`](wellcare-connect3/src/Service/Health/) - Services de santé multiples

#### Templates
- `health/dashboard.html.twig`
- `health/records.html.twig`
- `health/lab-results.html.twig`
- `health/prescriptions.html.twig`
- `health/billing.html.twig`
- `health/analytics/`

---

### Module 4: Nutrition

**Route:** `/nutrition/*`, `/nutritioniste/*`

#### Fonctionnalités
- **Tableau de Bord Nutrition**
  - Aperçu calories & macros
  - Résumé quotidien
  - Accès rapide au journal

- **Journal Alimentaire**
  - Enregistrer les repas
  - Entrée alimentaire rapide
  - Scan code-barres
  - Entrée vocale
  - Recherche base alimentaire

- **Planificateur de Repas**
  - Planification hebdomadaire
  - Bibliothèque de recettes
  - Génération liste courses
  - Assistant cuisson

- **Objectifs & Progrès**
  - Définir objectifs nutritionnels
  - Suivre progression
  - Jalons & réalisations
  - Métriques de succès

- **Analyse Nutritionnelle**
  - Analyse macro/micronutriments
  - Score qualité des repas
  - Recommandations

- **Consultation Professionnelle**
  - Envoyer message au nutritionist
  - Réserver consultation
  - Historique des consultations

#### Contrôleurs
- [`NutritionController.php`](wellcare-connect3/src/Controller/NutritionController.php) - Logique principale
- [`NutritionGoalController.php`](wellcare-connect3/src/Controller/NutritionGoalController.php) - Gestion objectifs
- [`NutrisionisteDashController.php`](wellcare-connect3/src/Controller/NutrisionisteDashController.php) - Dashboard nutritionist

#### Services
- [`NutritionAIService.php`](wellcare-connect3/src/Service/NutritionAIService.php) - Recommandations IA
- [`TunisianPriceService.php`](wellcare-connect3/src/Service/TunisianPriceService.php) - Prix alimentaires tunisiens
- [`GroceryListPdfService.php`](wellcare-connect3/src/Service/GroceryListPdfService.php) - PDF liste courses

---

### Module 5: Fitness

**Route:** `/fitness/*`, `/coach/*`

#### Fonctionnalités
- **Tableau de Bord Fitness**
  - Aperçu des entraînements
  - Démarrage rapide entraînement
  - Résumé progression

- **Planificateur d'Entraînement**
  - Créer entraînements personnalisés
  - Planifier entraînements
  - Entraînements adaptatifs

- **Bibliothèque d'Exercices**
  - Parcourir exercices par catégorie
  - Détails exercices avec vidéos
  - Filtrer par difficulté

- **Journal d'Entraînement**
  - Enregistrer entraînements effectués
  - Suivre reps, séries, poids
  - Suivi durée

- **Objectifs & Plans**
  - Définir objectifs fitness
  - Créer plans d'entraînement
  - Éditeur d'objectifs intelligent

- **Suivi des Jalons**
  - Système d'achièvements
  - Progression des jalons
  - Badges & récompenses

- **Analytique**
  - Analytique performance
  - Graphiques progression
  - Records personnels

- **Communication Coach**
  - Discuter avec coach
  - Obtenir feedback entraînement
  - Ajustements programmes

#### Contrôleurs
- [`FitnessController.php`](wellcare-connect3/src/Controller/FitnessController.php)
- [`ExerciseController.php`](wellcare-connect3/src/Controller/ExerciseController.php)
- [`GoalController.php`](wellcare-connect3/src/Controller/GoalController.php)
- [`CoachController.php`](wellcare-connect3/src/Controller/CoachController.php)
- [`AiCoachDashboardController.php`](wellcare-connect3/src/Controller/AiCoachDashboardController.php)

#### Templates
- `fitness/patient-dashboard.html.twig`
- `fitness/workout-planner.html.twig`
- `fitness/exercise-library.html.twig`
- `fitness/workout-log.html.twig`
- `fitness/goals-plans.html.twig`
- `fitness/milestone-tracker.html.twig`
- `fitness/performance-analytics.html.twig`

---

### Module 6: Parcours Santé

**Route:** `/trail/*`, `/parcours_de_sante/*`

#### Fonctionnalités
- **Tableau de Bord Trail**
  - Activités récentes
  - Événements trail à venir
  - Highlights communauté

- **Découvrir les Trails**
  - Parcourir catalogue trails
  - Filtrer par difficulté/longueur
  - Rechercher par localisation

- **Détails du Trail**
  - Cartes de trails
  - Profils d'élévation
  - Conditions des trails
  - Avis utilisateurs

- **Mes Trails**
  - Historique personnel trails
  - Trails sauvegardés
  - Trails créés

- **Fonctionnalités Communauté**
  - Publications/feed trails
  - Système de commentaires
  - Like/partager trails

- **Cartes Interactives**
  - Visualisation cartographique
  - Planification d'itinéraire
  - Téléchargements offline

#### Contrôleurs
- [`TrailController.php`](wellcare-connect3/src/Controller/TrailController.php)
- [`ParcoursDeSanteController.php`](wellcare-connect3/src/Controller/ParcoursDeSanteController.php)
- [`PublicationParcoursController.php`](wellcare-connect3/src/Controller/PublicationParcoursController.php)
- [`AdminTrailAnalyticsController.php`](wellcare-connect3/src/Controller/AdminTrailAnalyticsController.php)

#### Services
- [`MapService.php`](wellcare-connect3/src/Service/MapService.php) - Services cartographiques
- [`ParcoursRecommendationService.php`](wellcare-connect3/src/Service/ParcoursRecommendationService.php) - Recommandations trails

#### Templates
- `trail/dashboard.html.twig`
- `trail/discover.html.twig`
- `trail/detail.html.twig`
- `trail/my-trails.html.twig`
- `trail/create.html.twig`

---

## 4. Tableaux de Bord Professionnels

### Tableau de Bord Médecin

**Route:** `/doctor/*`

- Liste des patients
- File d'attente patients
- Notes cliniques
- Planning rendez-vous (jour/semaine/mois)
- Communication patient
- Analytique

**Contrôleur:** [`DoctorController.php`](wellcare-connect3/src/Controller/DoctorController.php)

---

### Tableau de Bord Coach

**Route:** `/coach/*`

- Gestion des clients
- Détails client
- Création de programmes
- Suivi progression
- Hub communication
- Rapports

**Contrôleur:** [`CoachController.php`](wellcare-connect3/src/Controller/CoachController.php)

---

### Tableau de Bord Nutritionniste

**Route:** `/nutritioniste/*`

- Liste des patients
- Création plans repas
- Analyse nutritionnelle patient
- Messages patients
- Rapports

**Contrôleur:** [`NutrisionisteDashController.php`](wellcare-connect3/src/Controller/NutrisionisteDashController.php)

---

### Module Admin

**Route:** `/admin/*`

- Gestion des utilisateurs
- Approbation vérification professionnelle
- Analytique plateforme
- Configuration système

**Contrôleur:** [`AdminController.php`](wellcare-connect3/src/Controller/AdminController.php)

---

## 5. Entités de Base de Données

### Entités Principales

| Entité | Description |
|--------|-------------|
| `User` | Entité utilisateur principale |
| `Patient` | Profil patient |
| `Medecin` | Profil médecin |
| `Coach` | Profil coach |
| `Nutritionist` | Profil nutritionist |
| `Consultation` | Consultations/rendez-vous |
| `Healthentry` | Entrées santé |
| `Healthjournal` | Journal santé |
| `NutritionGoal` | Objectifs nutrition |
| `NutritionGoalProgress` | Progression objectifs |
| `Exercises` | Bibliothèque exercices |
| `ExercisePlan` | Plans d'exercice |
| `ParcoursDeSante` | Trails santé |
| `PublicationParcours` | Publications trails |
| `PublicationParcours` | Publications trails |
| `FoodItem` | Aliment |
| `FoodLog` | Journal alimentaire |
| `MealPlan` | Plan repas |
| `Goal` | Objectifs fitness |
| `DailyPlan` | Plan quotidien |
| `CommentairePublication` | Commentaires |
| `Notificationrdv` | Notifications rendez-vous |
| `Conversation` | Conversations |
| `Message` | Messages |

---

## 6. Statistiques du Projet

| Catégorie | Nombre |
|-----------|--------|
| Contrôleurs | 40+ |
| Entités | 40+ |
| Services | 25+ |
| Templates | 100+ |
| Fichiers JavaScript | 25+ |
| Fichiers CSS | 15+ |
| Documentation | 10+ |

---

## Résumé

WellCare Connect est une plateforme de santé complète construite avec Symfony 6.4 offrant:

✅ **6 Modules Principaux:**
1. Authentification & Gestion Utilisateurs
2. Consultation (Rendez-vous + Téléconsultation)
3. Journal de Santé
4. Nutrition
5. Fitness
6. Parcours Santé

✅ **4 Tableaux de Bord Professionnels:**
- Tableau de Bord Médecin
- Tableau de Bord Coach
- Tableau de Bord Nutritionniste
- Tableau de Bord Admin

✅ **Fonctionnalités Avancées:**
- Recommandations IA
- Visioconférence en temps réel
- Système de vérification professionnelle
- Authentification à deux facteurs
- Design accessible (WCAG)
- Design responsive mobile

✅ **Technologie:**
- Symfony 6.4
- Doctrine ORM
- JavaScript (Alpine.js)
- Intégration Python AI
- CSS moderne avec Tailwind

---

*Document généré pour le projet WellCare Connect*
*Version: 1.0*
