# WellCare Connect - Manuel de Tests Complets

Ce document contient tous les tests manuels nécessaires pour vérifier que le système fonctionne correctement sans erreurs.

---

## TABLE DES MATIÈRES

1. [Configuration Préalable](#configuration-préalable)
2. [Tests d'Authentification](#tests-dauthentification)
3. [Tests d'Inscription Patient](#tests-dinscription-patient)
4. [Tests d'Inscription Professionnels (Médecin, Coach, Nutritionniste)](#tests-dinscription-professionnels)
5. [Tests du Tableau de Bord Admin](#tests-du-tableau-de-bord-admin)
6. [Tests de Vérification des Diplômes](#tests-de-vérification-des-diplômes)
7. [Tests des Routes Protégées](#tests-des-routes-protégées)
8. [Tests de Sécurité](#tests-de-sécurité)
9. [Tests des Services](#tests-des-services)
10. [Tests des Pages Front Office](#tests-des-pages-front-office)

---

## 1. CONFIGURATION PRÉALABLE

### 1.1 Prérequis
```bash
# Démarrer le serveur de développement
cd wellcare-connect3
php bin/console server:run

# OU utiliser Symfony Docker
docker-compose up -d
```

### 1.2 Accès à l'application
- URL de base: `http://127.0.0.1:8000`
- Tableau de bord admin: `http://127.0.0.1:8000/admin/dashboard`

### 1.3 Comptes de test
```
ADMIN:
- Email: admin@wellcare.tn
- Mot de passe: Admin@123

PATIENT:
- Email: patient@test.com
- Mot de passe: Patient@123

MÉDECIN (vérifié):
- Email: doctor@test.com
- Mot de passe: Doctor@123

MÉDECIN (non vérifié):
- Email: doctor2@test.com
- Mot de passe: Doctor@123
```

---

## 2. TESTS D'AUTHENTIFICATION

### 2.1 Test: Page de Login
- [ ] **Visiter** `/login`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Vérifier** que le formulaire de login est affiché
- [ ] **Vérifier** que le captcha est affiché
- [ ] **Vérifier** que les liens "Mot de passe oublié" et "Créer un compte" existent
- [ ] **Vérifier** qu'il n'y a pas d'erreurs JavaScript dans la console

### 2.2 Test: Login avec identifiants invalides
- [ ] **Entrer** un email invalide
- [ ] **Entrer** un mauvais mot de passe
- [ ] **Cliquer** sur "Se connecter"
- [ ] **Vérifier** qu'un message d'erreur s'affiche
- [ ] **Vérifier** qu'il n'y a pas d'erreur Symfony

### 2.3 Test: Login avec compte inexistant
- [ ] **Entrer** un email qui n'existe pas dans la base de données
- [ ] **Entrer** n'importe quel mot de passe
- [ ] **Cliquer** sur "Se connecter"
- [ ] **Vérifier** que le message d'erreur est approprié (pas d'exposition d'information)

### 2.4 Test: Login avec compte inactif
- [ ] **Créer** un nouveau compte ou utiliser un compte existant inactif
- [ ] **Tenter** de se connecter
- [ ] **Vérifier** que le message indique que le compte est inactif

### 2.5 Test: Login avec succès
- [ ] **Entrer** des identifiants valides (admin@wellcare.tn / Admin@123)
- [ ] **Cliquer** sur "Se connecter"
- [ ] **Vérifier** redirection vers le tableau de bord
- [ ] **Vérifier** qu'aucune erreur n'apparaît

### 2.6 Test: Logout
- [ ] **Cliquer** sur le bouton de déconnexion
- [ ] **Vérifier** redirection vers la page d'accueil ou login
- [ ] **Vérifier** que l'utilisateur est bien déconnecté

### 2.7 Test: Mot de passe oublié
- [ ] **Visiter** `/forgot-password`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Entrer** un email valide
- [ ] **Cliquer** sur "Envoyer"
- [ ] **Vérifier** le message de succès

### 2.8 Test: Réinitialisation du mot de passe
- [ ] **Cliquer** sur le lien dans l'email de réinitialisation
- [ ] **Vérifier** que la page de reset se charge
- [ ] **Entrer** un nouveau mot de passe
- [ ] **Confirmer** le mot de passe
- [ ] **Vérifier** la redirection vers login
- [ ] **Tester** la connexion avec le nouveau mot de passe

---

## 3. TESTS D'INSCRIPTION PATIENT

### 3.1 Test: Page d'inscription patient
- [ ] **Visiter** `/register/patient`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Vérifier** que le formulaire multi-étapes s'affiche
- [ ] **Vérifier** que le captcha est présent

### 3.2 Test: Inscription patient - Étape 1 (Informations de base)
- [ ] **Entrer** un email unique (testpatient + timestamp @test.com)
- [ ] **Entrer** un mot de passe valide
- [ ] **Confirmer** le mot de passe
- [ ] **Cliquer** sur "Suivant"
- [ ] **Vérifier** passage à l'étape 2

### 3.3 Test: Inscription patient - Étape 2 (Informations personnelles)
- [ ] **Entrer** le prénom
- [ ] **Entrer** le nom
- [ ] **Sélectionner** la date de naissance
- [ ] **Entrer** le téléphone
- [ ] **Cliquer** sur "Suivant"
- [ ] **Vérifier** passage à l'étape 3

### 3.4 Test: Inscription patient - Étape 3 (Adresse)
- [ ] **Entrer** l'adresse
- [ ] **Entrer** la ville
- [ ] **Cocher** accepter les conditions
- [ ] **Cliquer** sur "S'inscrire"
- [ ] **Vérifier** redirection vers page de vérification d'email

### 3.5 Test: Validation des champs
- [ ] **Tester** avec email invalide → message d'erreur
- [ ] **Tester** avec mot de passe faible → message d'erreur
- [ ] **Tester** avec email déjà existant → message d'erreur

---

## 4. TESTS D'INSCRIPTION PROFESSIONNELS

### 4.1 Test: Page
- [ ] d'inscription médecin **Visiter** `/register/medecin`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Vérifier** que le formulaire inclut les champs professionnels

### 4.2 Test: Inscription médecin - Informations professionnelles
- [ ] **Remplir** les informations de base (email, mot de passe)
- [ ] **Remplir** les informations personnelles (prénom, nom)
- [ ] **Sélectionner** la spécialité (Cardiologie, Dermatologie, etc.)
- [ ] **Entrer** le numéro de licence
- [ ] **Télécharger** un fichier de diplôme (PDF ou image)
- [ ] **Soumettre** le formulaire
- [ ] **Vérifier** redirection vers page de vérification d'email

### 4.3 Test: Inscription coach
- [ ] **Visiter** `/register/coach`
- [ ] **Vérifier** que le formulaire contient les champs spécifiques coach
- [ ] **Télécharger** un fichier de diplôme
- [ ] **Soumettre** et vérifier le succès

### 4.4 Test: Inscription nutritionist
- [ ] **Visiter** `/register/nutritionist`
- [ ] **Vérifier** que le formulaire contient les champs spécifiques nutritionist
- [ ] **Télécharger** un fichier de diplôme
- [ ] **Soumettre** et vérifier le succès

### 4.5 Test: Vérification du statut après inscription professionnelle
- [ ] **Se connecter** avec le nouveau compte professionnel
- [ ] **Vérifier** que l'utilisateur ne peut pas accéder à son dashboard
- [ ] **Vérifier** le message indiquant que le compte est en attente de vérification

---

## 5. TESTS DU TABLEAU DE BORD ADMIN

### 5.1 Test: Accès au tableau de bord admin
- [ ] **Se connecter** en tant qu'admin
- [ ] **Visiter** `/admin/dashboard`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Vérifier** que les statistiques sont affichées

### 5.2 Test: Gestion des utilisateurs
- [ ] **Visiter** `/admin/users`
- [ ] **Vérifier** que la liste des utilisateurs s'affiche
- [ ] **Tester** la recherche d'utilisateur
- [ ] **Tester** les filtres par rôle

### 5.3 Test: Modification d'utilisateur
- [ ] **Cliquer** sur un utilisateur dans la liste
- [ ] **Vérifier** que la page de détail s'affiche
- [ ] **Modifier** un champ (nom, email, rôle)
- [ ] **Enregistrer** les modifications
- [ ] **Vérifier** le message de succès

### 5.4 Test: Activation/Désactivation utilisateur
- [ ] **Aller** sur la page d'un utilisateur
- [ ] **Cliquer** sur "Désactiver le compte"
- [ ] **Confirmer** l'action
- [ ] **Vérifier** que le compte est désactivé
- [ ] **Réactiver** le compte

### 5.5 Test: Suppression utilisateur
- [ ] **Aller** sur la page d'un utilisateur
- [ ] **Cliquer** sur "Supprimer le compte"
- [ ] **Confirmer** la suppression
- [ ] **Vérifier** que l'utilisateur est supprimé

### 5.6 Test: Opérations en masse
- [ ] **Sélectionner** plusieurs utilisateurs
- [ ] **Tester** l'activation en masse
- [ ] **Tester** la désactivation en masse

---

## 6. TESTS DE VÉRIFICATION DES DIPLÔMES

### 6.1 Test: Tableau de bord de vérification
- [ ] **Se connecter** en tant qu'admin
- [ ] **Visiter** `/admin/verification`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Vérifier** que les statistiques sont affichées (total, en attente, vérifiés, rejetés)
- [ ] **Vérifier** que la liste des vérifications est affichée

### 6.2 Test: Détails d'une vérification
- [ ] **Cliquer** sur une vérification dans la liste
- [ ] **Vérifier** que la page de détails se charge
- [ ] **Vérifier** les informations du professionnel
- [ ] **Vérifier** le fichier de diplôme (si visible)
- [ ] **Vérifier** les données extraites (OCR)
- [ ] **Vérifier** le score de confiance
- [ ] **Vérifier** les indicateurs de falsification

### 6.3 Test: Approbation manuelle
- [ ] **Aller** sur une vérification en attente
- [ ] **Cliquer** sur "Approuver"
- [ ] **Confirmer** l'approbation
- [ ] **Vérifier** que le statut change à "verified"
- [ ] **Vérifier** que l'utilisateur professionnel est notifié

### 6.4 Test: Rejet avec raison
- [ ] **Aller** sur une vérification en attente
- [ ] **Cliquer** sur "Rejeter"
- [ ] **Entrer** une raison de rejet
- [ ] **Confirmer** le rejet
- [ ] **Vérifier** que le statut change à "rejected"

### 6.5 Test: Reprocess (retraitement)
- [ ] **Aller** sur une vérification
- [ ] **Cliquer** sur "Retraiter"
- [ ] **Attendre** le traitement
- [ ] **Vérifier** que les résultats sont mis à jour

### 6.6 Test: Statistiques de vérification
- [ ] **Visiter** `/admin/verification/statistics`
- [ ] **Vérifier** que les graphiques/tableaux s'affichent

---

## 7. TESTS DES ROUTES PROTÉGÉES

### 7.1 Test: Accès non autorisé
- [ ] **Tenter** d'accéder à `/admin/dashboard` sans être connecté
- [ ] **Vérifier** redirection vers login
- [ ] **Tenter** d'accéder à `/admin/dashboard` avec un compte patient
- [ ] **Vérifier** accès refusé (403 ou redirection)

### 7.2 Test: Accès autorisé par rôle
- [ ] **Se connecter** en tant que médecin
- [ ] **Accéder** à `/doctor/dashboard`
- [ ] **Vérifier** accès autorisé
- [ ] **Tenter** d'accéder à `/admin/dashboard`
- [ ] **Vérifier** accès refusé

### 7.3 Test: Coach routes
- [ ] **Se connecter** en tant que coach
- [ ] **Accéder** à `/coach/dashboard`
- [ ] **Vérifier** accès autorisé

### 7.4 Test: Nutritionist routes
- [ ] **Se connecter** en tant que nutritionist
- [ ] **Accéder** à `/nutrition/nutritionniste/dashboard`
- [ ] **Vérifier** accès autorisé

---

## 8. TESTS DE SÉCURITÉ

### 8.1 Test: Protection CSRF
- [ ] **Ouvrir** la console de développement (F12)
- [ ] **Aller** sur la page de login
- [ ] **Soumettre** le formulaire sans token CSRF
- [ ] **Vérifier** que la requête est rejetée

### 8.2 Test: Protection XSS
- [ ] **Tenter** d'injecter du JavaScript dans un champ de formulaire
- [ ] **Soumettre** le formulaire
- [ ] **Vérifier** que le code n'est pas exécuté (affiché en texte brut)

### 8.3 Test: Protection SQL Injection
- [ ] **Entrer** des caractères spéciaux dans les champs de recherche
- [ ] **Soumettre**
- [ ] **Vérifier** pas d'erreur SQL

### 8.4 Test: Rate Limiting (Brute Force)
- [ ] **Tenter** de se connecter plusieurs fois avec un mauvais mot de passphrase
- [ ] **Vérifier** que le compte est temporairement verrouillé
- [ ] **Vérifier** le message d'erreur approprié

### 8.5 Test: Two-Factor Authentication
- [ ] **Se connecter** avec un compte 2FA activé
- [ ] **Vérifier** redirection vers la page de vérification 2FA
- [ ] **Entrer** le code à 6 chiffres
- [ ] **Vérifier** accès au tableau de bord

---

## 9. TESTS DES SERVICES

### 9.1 Test: Service de vérification de diplôme
- [ ] **Vérifier** que le service `DiplomaVerificationService` est injecté correctement
- [ ] **Tester** l'OCR avec un PDF de diplôme
- [ ] **Tester** l'OCR avec une image de diplôme
- [ ] **Vérifier** le scoring de confiance

### 9.2 Test: Service d'email de vérification
- [ ] **Vérifier** que les emails sont envoyés
- [ ] **Vérifier** le contenu des emails

### 9.3 Test: Service de reset de mot de passe
- [ ] **Demander** un reset de mot de passe
- [ ] **Vérifier** l'email de reset
- [ ] **Utiliser** le lien pour reset
- [ ] **Vérifier** que le mot de passe est changé

### 9.4 Test: Service de Captcha
- [ ] **Vérifier** que le captcha est généré
- [ ] **Vérifier** la validation du captcha

---

## 10. TESTS DES PAGES FRONT OFFICE

### 10.1 Test: Page d'accueil
- [ ] **Visiter** `/`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Vérifier** les liens de navigation
- [ ] **Vérifier** le design responsive

### 10.2 Test: Tableau de bord patient
- [ ] **Se connecter** en tant que patient
- [ ] **Visiter** `/appointment/patient-dashboard`
- [ ] **Vérifier** que la page se charge sans erreur

### 10.2.1 Test: Recherche de médecins
- [ ] **Aller** sur `/appointment/search-doctors`
- [ ] **Rechercher** par spécialité
- [ ] **Vérifier** les résultats

### 10.3 Test: Santé (Health)
- [ ] **Visiter** `/health/dashboard`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Naviguer** vers les sous-pages:
  - [ ] `/health/journal`
  - [ ] `/health/symptoms`
  - [ ] `/health/records`
  - [ ] `/health/prescriptions`
  - [ ] `/health/lab-results`
  - [ ] `/health/billing`
  - [ ] `/health/body-map`
  - [ ] `/health/accessible/journal-entry`
  - [ ] `/health/accessible/body-map`

### 10.4 Test: Nutrition
- [ ] **Visiter** `/nutrition/`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Naviguer** vers les sous-pages:
  - [ ] `/nutrition/diary`
  - [ ] `/nutrition/quick-log`
  - [ ] `/nutrition/planner`
  - [ ] `/nutrition/recipes`
  - [ ] `/nutrition/goals`
  - [ ] `/nutrition/messages/1`
  - [ ] `/nutrition/consultation`
  - [ ] `/nutrition/nutritionniste/dashboard`

### 10.5 Test: Téléconsultation
- [ ] **Visiter** `/teleconsultation/waiting-room`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Naviguer** vers:
  - [ ] `/teleconsultation/consultation-room`
  - [ ] `/teleconsultation/medical-tools`
  - [ ] `/teleconsultation/prescription-writer`
  - [ ] `/teleconsultation/soap-notes`

### 10.6 Test: Fitness
- [ ] **Visiter** `/fitness/dashboard`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Naviguer** vers:
  - [ ] `/fitness/planner`
  - [ ] `/fitness/library`
  - [ ] `/fitness/log`
  - [ ] `/fitness/analytics`
  - [ ] `/fitness/coach`
  - [ ] `/fitness/goals`

### 10.7 Test: Trail (Randonnée)
- [ ] **Visiter** `/trail/dashboard`
- [ ] **Vérifier** que la page se charge sans erreur

### 10.8 Test: Médecin
- [ ] **Se connecter** en tant que médecin
- [ ] **Visiter** `/doctor/dashboard`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Naviguer** vers:
  - [ ] `/doctor/patient-list`
  - [ ] `/doctor/patient-queue`
  - [ ] `/doctor/clinical-notes`
  - [ ] `/doctor/communication`
  - [ ] `/doctor/availability-settings`
  - [ ] `/doctor/schedule/day-view`
  - [ ] `/doctor/schedule/week-view`
  - [ ] `/doctor/schedule/month-view`

### 10.9 Test: Coach
- [ ] **Se connecter** en tant que coach
- [ ] **Visiter** `/coach/dashboard`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Naviguer** vers:
  - [ ] `/coach/clients`
  - [ ] `/coach/programs`
  - [ ] `/coach/progress`
  - [ ] `/coach/messages`
  - [ ] `/coach/reports`

### 10.10 Test: Nutritionniste
- [ ] **Se connecter** en tant que nutritionist
- [ ] **Visiter** `/nutrition/nutritionniste/dashboard`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Naviguer** vers:
  - [ ] `/nutrition/nutritionniste/patients`
  - [ ] `/nutrition/nutritionniste/messages`
  - [ ] `/nutrition/nutritionniste/reports`

### 10.11 Test: Analytics
- [ ] **Visiter** `/analytics/clinic-performance`
- [ ] **Vérifier** que la page se charge sans erreur
- [ ] **Naviguer** vers:
  - [ ] `/analytics/patient-appointments`
  - [ ] `/analytics/quality-metrics`
  - [ ] `/analytics/financial-reports`

---

## 11. VÉRIFICATIONS FINALES

### 11.1 Vérification des logs
```bash
# Vérifier les logs d'erreur
tail -f var/log/dev.log

# Rechercher les erreurs récentes
grep -i "error" var/log/dev.log | tail -20
```

### 11.2 Vérification de la base de données
- [ ] **Vérifier** que les tables sont créées
- [ ] **Vérifier** que les données de test existent

### 11.3 Vérification des assets
```bash
# Compiler les assets
npm run build

# Vérifier que les fichiers sont générés
ls -la public/build/
```

---

## 12. CHECKLIST DE SIGN-OFF

Cochez cette case lorsque tous les tests sont passés:

- [ ] Tous les tests d'authentification passent
- [ ] Tous les tests d'inscription patient passent
- [ ] Tous les tests d'inscription professionnels passent
- [ ] Tous les tests du tableau de bord admin passent
- [ ] Tous les tests de vérification des diplômes passent
- [ ] Tous les tests de sécurité passent
- [ ] Toutes les pages front office se chargent sans erreur
- [ ] Aucune erreur dans la console JavaScript
- [ ] Aucune erreur dans les logs Symfony

---

## 13. RAPPORT DE BUGS

Si vous trouvez des erreurs, notez-les ici:

| Date | Page/Route | Description de l'erreur | Screenshot |
|------|------------|------------------------|------------|
|      |            |                        |            |
|      |            |                        |            |
|      |            |                        |            |

---

*Document généré pour WellCare Connect - Tests Manuels*
*Dernière mise à jour: 2026-02-20*
