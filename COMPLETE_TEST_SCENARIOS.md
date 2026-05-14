# WellCare Connect - Tests Complets avec Tous les Scénarios

## TABLE DES MATIÈRES
1. [Tests d'Authentification - Tous Scénarios](#1-tests-dauthentification---tous-scénarios)
2. [Tests d'Inscription - Tous Scénarios](#2-tests-dinscription---tous-scénarios)
3. [Tests du Profil Utilisateur](#3-tests-du-profil-utilisateur)
4. [Tests Admin - Gestion Utilisateurs](#4-tests-admin---gestion-utilisateurs)
5. [Tests Vérification Diplômes](#5-tests-vérification-diplômes)
6. [Tests Modules Front Office](#6-tests-modules-front-office)
7. [Tests de Sécurité](#7-tests-de-sécurité)
8. [Tests API](#8-tests-api)

---

## 1. TESTS D'AUTHENTIFICATION - TOUS SCÉNARIOS

### 1.1 Login - Scénarios Positifs
- [ ] **SP1** - Login avec email/password corrects (patient)
- [ ] **SP2** - Login avec email/password corrects (médecin)
- [ ] **SP3** - Login avec email/password corrects (coach)
- [ ] **SP4** - Login avec email/password corrects (nutritionist)
- [ ] **SP5** - Login avec email/password corrects (admin)
- [ ] **SP6** - Login avec "Se souvenir de moi" coché
- [ ] **SP7** - Login puis logout, puis re-login
- [ ] **SP8** - Login avec compte email vérifié
- [ ] **SP9** - Login avec compte email non vérifié (devrait être bloqué)

### 1.2 Login - Scénarios Négatifs
- [ ] **SN1** - Login avec email inexistant
- [ ] **SN2** - Login avec mauvais mot de passe
- [ ] **SN3** - Login avec email invalide (format incorrect)
- [ ] **SN4** - Login avec mot de passe vide
- [ ] **SN5** - Login avec email vide
- [ ] **SN6** - Login avec compte désactivé
- [ ] **SN7** - Login avec compte verrouillé (après trop de tentatives)
- [ ] **SN8** - Login avec captcha incorrect
- [ ] **SN9** - Login avec SQL injection dans email
- [ ] **SN10** - Login avec XSS dans email
- [ ] **SN11** - Login multiple rapide (rate limiting)

### 1.3 Logout
- [ ] **LO1** - Logout depuis le header
- [ ] **LO2** - Logout depuis le menu profil
- [ ] **LO3** - Redirection après logout vers page d'accueil
- [ ] **LO4** - Accès aux pages protégées après logout (doit être refusé)

### 1.4 Mot de Passe Oublié
- [ ] **PO1** - Demande reset avec email existant
- [ ] **PO2** - Demande reset avec email inexistant (message générique)
- [ ] **PO3** - Demande reset avec email vide
- [ ] **PO4** - Demande reset avec email invalide
- [ ] **PO5** - Lien reset expire après délai
- [ ] **PO6** - Reset avec nouveau mot de passe valide
- [ ] **PO7** - Reset avec confirmation mot de passe différent
- [ ] **PO8** - Reset avec mot de passe faible
- [ ] **PO9** - Reset avec mot de passe déjà utilisé
- [ ] **PO10** - Utilisation du lien reset deux fois (devrait échouer)

### 1.5 Two-Factor Authentication (2FA)
- [ ] **2FA1** - Activation 2FA par utilisateur
- [ ] **2FA2** - Désactivation 2FA par utilisateur
- [ ] **2FA3** - Login avec code 2FA correct
- [ ] **2FA4** - Login avec code 2FA incorrect
- [ ] **2FA5** - Login avec code 2FA expiré
- [ ] **2FA6** - Utilisation codes backup
- [ ] **2FA7** - Régénération codes backup
- [ ] **2FA8** - Gestion appareils de confiance
- [ ] **2FA9** - Suppression appareil de confiance

---

## 2. TESTS D'INSCRIPTION - TOUS SCÉNARIOS

### 2.1 Inscription Patient
- [ ] **IP1** - Inscription patient avec toutes données valides
- [ ] **IP2** - Inscription patient avec email déjà utilisé
- [ ] **IP3** - Inscription patient avec email invalide
- [ ] **IP4** - Inscription patient avec mot de passe faible
- [ ] **IP5** - Inscription patient avec confirmation mot de passe différent
- [ ] **IP6** - Inscription patient avec téléphone invalide
- [ ] **IP7** - Inscription patient avec date de naissance future
- [ ] **IP8** - Inscription patient avec age < 18 ans
- [ ] **IP9** - Inscription patient sans accepter CGU
- [ ] **IP10** - Inscription patient avec tous champs vides
- [ ] **IP11** - Inscription patient avec caractères spéciaux dans nom/prénom
- [ ] **IP12** - Inscription patient avec adresse très longue
- [ ] **IP13** - Inscription patient avec captcha invalide

### 2.2 Inscription Médecin
- [ ] **IM1** - Inscription médecin avec diplôme PDF valide
- [ ] **IM2** - Inscription médecin avec diplôme image valide
- [ ] **IM3** - Inscription médecin sans diplôme
- [ ] **IM4** - Inscription médecin avec numéro de licence vide
- [ ] **IM5** - Inscription médecin avec numéro de licence déjà utilisé
- [ ] **IM6** - Inscription médecin avec spécialité invalide
- [ ] **IM7** - Inscription médecin avec toutes spécialités (Cardiologie, Dermatologie, etc.)
- [ ] **IM8** - Inscription médecin avec fichier trop volumineux
- [ ] **IM9** - Inscription médecin avec type de fichier non autorisé
- [ ] **IM10** - Inscription médecin avec données professionnelles valides

### 2.3 Inscription Coach
- [ ] **IC1** - Inscription coach avec toutes données valides
- [ ] **IC2** - Inscription coach sans spécialisation
- [ ] **IC3** - Inscription coach avec expérience invalide
- [ ] **IC4** - Inscription coach avec diplôme
- [ ] **IC5** - Inscription coach sans diplôme

### 2.4 Inscription Nutritionnist
- [ ] **IN1** - Inscription nutritionist avec toutes données valides
- [ ] **IN2** - Inscription nutritionist avec diplôme
- [ ] **IN3** - Inscription nutritionist sans numéro de licence
- [ ] **IN4** - Inscription nutritionist avec specialité différente

### 2.5 Vérification Email Post-Inscription
- [ ] **VE1** - Réception email de vérification
- [ ] **VE2** - Clic sur lien vérification email valide
- [ ] **VE3** - Clic sur lien vérification email expiré
- [ ] **VE4** - Clic sur lien vérification email déjà utilisé
- [ ] **VE5** - Renvoyer email de vérification
- [ ] **VE6** - Vérification avec token invalide

---

## 3. TESTS DU PROFIL UTILISATEUR

### 3.1 Consultation Profil
- [ ] **PP1** - Consultation profil patient
- [ ] **PP2** - Consultation profil médecin
- [ ] **PP3** - Consultation profil coach
- [ ] **PP4** - Consultation profil nutritionist
- [ ] **PP5** - Consultation profil admin

### 3.2 Modification Profil
- [ ] **MP1** - Modification du prénom
- [ ] **MP2** - Modification du nom
- [ ] **MP3** - Modification de l'email
- [ ] **MP4** - Modification du téléphone
- [ ] **MP5** - Modification de l'adresse
- [ ] **MP6** - Modification de la date de naissance
- [ ] **MP7** - Modification de l'avatar (URL valide)
- [ ] **MP8** - Modification avec email déjà utilisé
- [ ] **MP9** - Modification avec téléphone invalide

### 3.3 Changement Mot de Passe
- [ ] **CP1** - Changement mot de passe avec ancien mot de passe correct
- [ ] **CP2** - Changement mot de passe avec ancien mot de passe incorrect
- [ ] **CP3** - Changement mot de passe avec nouveau mot de passe faible
- [ ] **CP4** - Changement mot de passe avec confirmation différente

---

## 4. TESTS ADMIN - GESTION UTILISATEURS

### 4.1 Dashboard Admin
- [ ] **DA1** - Accès dashboard admin (admin uniquement)
- [ ] **DA2** - Accès dashboard admin refusé (utilisateur normal)
- [ ] **DA3** - Affichage statistiques utilisateurs
- [ ] **DA4** - Affichage graphique utilisateurs par rôle
- [ ] **DA5** - Affichage utilisateurs récents

### 4.2 Liste Utilisateurs
- [ ] **LU1** - Liste de tous les utilisateurs
- [ ] **LU2** - Recherche utilisateur par email
- [ ] **LU3** - Recherche utilisateur par nom
- [ ] **LU4** - Filtrage par rôle (patient, médecin, etc.)
- [ ] **LU5** - Filtrage par statut (actif, inactif)
- [ ] **LU6** - Filtrage par vérification email
- [ ] **LU7** - Pagination liste utilisateurs
- [ ] **LU8** - Tri par date de création
- [ ] **LU9** - Tri par dernier login

### 4.3 Détails Utilisateur
- [ ] **DU1** - Affichage détails utilisateur
- [ ] **DU2** - Affichage historique connexions
- [ ] **DU3** - Affichage historique modifications
- [ ] **DU4** - Vérification du statut de vérification professionnel

### 4.4 Modification Utilisateur (Admin)
- [ ] **MU1** - Modification du rôle utilisateur
- [ ] **MU2** - Modification du statut actif/inactif
- [ ] **MU3** - Modification des informations professionnelles
- [ ] **MU4** - Forçage vérification email
- [ ] **MU5** - Modification avec données invalides

### 4.5 Opérations en Masse
- [ ] **OM1** - Activation de plusieurs utilisateurs
- [ ] **OM2** - Désactivation de plusieurs utilisateurs
- [ ] **OM3** - Suppression de plusieurs utilisateurs
- [ ] **OM4** - Vérification de plusieurs professionnels
- [ ] **OM5** - Opération en masse sans sélection (devrait être désactivé)

### 4.6 Suppression Utilisateur
- [ ] **SU1** - Suppression utilisateur avec confirmation
- [ ] **SU2** - Suppression utilisateur sans confirmation (annulé)
- [ ] **SU3** - Suppression admin lui-même (devrait être empêché)
- [ ] **SU4** - Suppression utilisateur avec données associées

---

## 5. TESTS VÉRIFICATION DIPLÔMES

### 5.1 Dashboard Vérification
- [ ] **VD1** - Accès dashboard vérification (admin uniquement)
- [ ] **VD2** - Affichage statistiques vérification
- [ ] **VD3** - Affichage liste vérifications en attente
- [ ] **VD4** - Affichage liste vérifications approuvées
- [ ] **VD5** - Affichage liste vérifications rejetées
- [ ] **VD6** - Filtrage par statut
- [ ] **VD7** - Filtrage par type de professionnel
- [ ] **VD8** - Filtrage par date

### 5.2 Traitement Automatique
- [ ] **TA1** - Traitement automatique avec score ≥80 (approuvé auto)
- [ ] **TA2** - Traitement automatique avec score 60-79 (review manuel)
- [ ] **TA3** - Traitement automatique avec score <60 (rejeté auto)
- [ ] **TA4** - Extraction OCR depuis PDF
- [ ] **TA5** - Extraction OCR depuis image
- [ ] **TA6** - OCR avec fichier corrompu

### 5.3 Approbation Manuelle
- [ ] **AM1** - Approbation manuelle avec理由
- [ ] **AM2** - Approbation sans commentaire
- [ ] **AM3** - Notification utilisateur après approbation
- [ ] **AM4** - Accès professionnel après approbation

### 5.4 Rejet Manuel
- [ ] **RM1** - Rejet avec reason
- [ ] **RM2** - Rejet sans reason
- [ ] **RM3** - Notification utilisateur après rejet
- [ ] **RM4** - Professional reste bloqué après rejet

### 5.5 Retraitement
- [ ] **RT1** - Retraitement d'une vérification
- [ ] **RT2** - Retraitement de toutes les vérifications en attente
- [ ] **RT3** - Mise à jour du score après retraitement

---

## 6. TESTS MODULES FRONT OFFICE

### 6.1 Module Rendez-vous (Patient)
- [ ] **RP1** - Affichage dashboard rendez-vous
- [ ] **RP2** - Recherche médecin par nom
- [ ] **RP3** - Recherche médecin par spécialité
- [ ] **RP4** - Filtrage par date
- [ ] **RP5** - Réservation rendez-vous
- [ ] **RP6** - Annulation rendez-vous
- [ ] **RP7** - Modification rendez-vous
- [ ] **RP8** - Historique rendez-vous
- [ ] **RP9** - Rendez-vous avec médecin vérifié
- [ ] **RP10** - Rendez-vous avec médecin non vérifié (bloqué)

### 6.2 Module Santé
- [ ] **SH1** - Affichage dashboard santé
- [ ] **SH2** - Ajout entrée journal
- [ ] **SH3** - Ajout symptôme
- [ ] **SH4** - Affichage signes vitaux
- [ ] **SH5** - Ajout medication
- [ ] **SH6** - Consultation historique médical
- [ ] **SH7** - Consultation résultats laboratoire
- [ ] **SH8** - Consultation ordonnances
- [ ] **SH9** - Export données santé
- [ ] **SH10** - Journal accessible (accessibilité)

### 6.3 Module Nutrition
- [ ] **SN1** - Affichage dashboard nutrition
- [ ] **SN2** - Ajout aliment rapide
- [ ] **SN3** - Consultation journal alimentaire
- [ ] **SN4** - Planification repas
- [ ] **SN5** - Objectifs nutritionnels
- [ ] **SN6** - Progression objectifs
- [ ] **SN7** - Consultation recettes
- [ ] **SN8** - Messages avec nutritionist
- [ ] **SN9** - Analyse nutritionnelle

### 6.4 Module Téléconsultation
- [ ] **TC1** - Accès salle d'attente
- [ ] **TC2** - Vérification système
- [ ] **TC3** - Test connexion audio/video
- [ ] **TC4** - Notes SOAP
- [ ] **TC5** - Outils médicaux
- [ ] **TC6** - Rédaction ordonnance

### 6.5 Module Fitness
- [ ] **FT1** - Affichage dashboard fitness
- [ ] **FT2** - Planificateur workouts
- [ ] **FT3** - Bibliothèque exercices
- [ ] **FT4** - Journal workouts
- [ ] **FT5** - Analytics fitness
- [ ] **FT6** - Objectifs fitness
- [ ] **FT7** - Milestones
- [ ] **FT8** - Workouts adaptatifs

### 6.6 Module Trail (Randonnée)
- [ ] **TR1** - Affichage dashboard trail
- [ ] **TR2** - Découverte trails
- [ ] **TR3** - Création trail
- [ ] **TR4** - Publications/feed
- [ ] **TR5** - Communauté/discussions
- [ ] **TR6** - Cartes interactives
- [ ] **TR7** - Téléchargement offline

### 6.7 Module Médecin
- [ ] **DM1** - Affichage dashboard médecin
- [ ] **DM2** - Liste patients
- [ ] **DM3** - File d'attente patients
- [ ] **DM4** - Notes cliniques
- [ ] **DM5** - Communication patient
- [ ] **DM6** - Paramètres disponibilité
- [ ] **DM7** - Planning (jour/semaine/mois)

### 6.8 Module Coach
- [ ] **DC1** - Affichage dashboard coach
- [ ] **DC2** - Gestion clients
- [ ] **DC3** - Détails client
- [ ] **DC4** - Création programmes
- [ ] **DC5** - Suivi progression
- [ ] **DC6** - Hub communication
- [ ] **DC7** - Rapports

### 6.9 Module Nutritionnist
- [ ] **DN1** - Affichage dashboard nutritionist
- [ ] **DN2** - Liste patients
- [ ] **DN3** - Création plan meals
- [ ] **DN4** - Analyse nutritionnelle patient
- [ ] **DN5** - Messages patients
- [ ] **DN6** - Rapports

### 6.10 Module Analytics
- [ ] **AN1** - Performance clinique
- [ ] **AN2** - Rendez-vous patients
- [ ] **AN3** - Métriques qualité
- [ ] **AN4** - Rapports financiers

---

## 7. TESTS DE SÉCURITÉ

### 7.1 Protection CSRF
- [ ] **CS1** - Soumission formulaire sans token CSRF
- [ ] **CS2** - Soumission formulaire avec token CSRF expiré

### 7.2 Protection XSS
- [ ] **XX1** - Injection script dans formulaire (devrait être échappé)
- [ ] **XX2** - Injection HTML dans formulaire (devrait être échappé)

### 7.3 Protection SQL Injection
- [ ] **SQ1** - Recherche avec ' OR '1'='1
- [ ] **SQ2** - Recherche avec ; DROP TABLE

### 7.4 Rate Limiting
- [ ] **RL1** - 5 tentatives login échouées → verrouillage
- [ ] **RL2** - Tentative login pendant verrouillage
- [ ] **RL3** - Déverrouillage après délai

### 7.5 Gestion Sessions
- [ ] **GS1** - Session expire après inactivité
- [ ] **GS2** - Session détruite après logout
- [ ] **GS3** - Accès concurrent (même compte sur deux appareils)

### 7.6 Autorisations
- [ ] **AU1** - Patient accède à /doctor/dashboard → refusé
- [ ] **AU2** - Médecin accède à /admin/dashboard → refusé
- [ ] **AU3** - Coach accède à /nutrition/nutritionniste → refusé
- [ ] **AU4** - Utilisateur non connecté accède à /profile → refusé

---

## 8. TESTS API

### 8.1 API Authentification
- [ ] **API1** - POST /api/login/validate
- [ ] **API2** - POST /api/forgot-password
- [ ] **API3** - POST /api/check-email
- [ ] **API4** - POST /api/check-phone
- [ ] **API5** - POST /api/validate-password
- [ ] **API6** - POST /api/check-license-number
- [ ] **API7** - POST /api/resend-verification-email
- [ ] **API8** - GET /api/login/scenarios

### 8.2 API Captcha
- [ ] **CAP1** - GET /api/captcha/refresh
- [ ] **CAP2** - POST /api/captcha/validate (valide)
- [ ] **CAP3** - POST /api/captcha/validate (invalide)

### 8.3 API Santé
- [ ] **HS1** - POST /health/quick-entry
- [ ] **HS2** - GET /health/metrics
- [ ] **HS3** - GET /health/charts
- [ ] **HS4** - GET /health/insights
- [ ] **HS5** - POST /health/symptom

### 8.4 API Trail
- [ ] **TR1** - GET /trail/api/search
- [ ] **TR2** - GET /trail/api/nearby
- [ ] **TR3** - GET /trail/api/export/{id}

---

## CHECKLIST FINALE

Cochez quand terminé:

### Authentification
- [ ] Tous les scénarios positifs login (9 tests)
- [ ] Tous les scénarios négatifs login (11 tests)
- [ ] Tous les tests logout (4 tests)
- [ ] Tous les tests mot de passe oublié (10 tests)
- [ ] Tous les tests 2FA (9 tests)

### Inscription
- [ ] Tous les tests inscription patient (13 tests)
- [ ] Tous les tests inscription médecin (10 tests)
- [ ] Tous les tests inscription coach (5 tests)
- [ ] Tous les tests inscription nutritionist (4 tests)
- [ ] Tous les tests vérification email (6 tests)

### Profil
- [ ] Tous les tests consultation profil (5 tests)
- [ ] Tous les tests modification profil (9 tests)
- [ ] Tous les tests changement mot de passe (4 tests)

### Admin
- [ ] Tous les tests dashboard admin (5 tests)
- [ ] Tous les tests liste utilisateurs (9 tests)
- [ ] Tous les tests détails utilisateur (4 tests)
- [ ] Tous les tests modification utilisateur (5 tests)
- [ ] Tous les tests opérations en masse (5 tests)
- [ ] Tous les tests suppression utilisateur (4 tests)

### Vérification Diplômes
- [ ] Tous les tests dashboard vérification (8 tests)
- [ ] Tous les tests traitement automatique (6 tests)
- [ ] Tous les tests approbation manuelle (4 tests)
- [ ] Tous les tests rejet manuel (4 tests)
- [ ] Tous les tests retraitement (3 tests)

### Modules Front Office
- [ ] Module Rendez-vous (10 tests)
- [ ] Module Santé (10 tests)
- [ ] Module Nutrition (9 tests)
- [ ] Module Téléconsultation (6 tests)
- [ ] Module Fitness (8 tests)
- [ ] Module Trail (7 tests)
- [ ] Module Médecin (7 tests)
- [ ] Module Coach (7 tests)
- [ ] Module Nutritionnist (6 tests)
- [ ] Module Analytics (4 tests)

### Sécurité
- [ ] Tests CSRF (2 tests)
- [ ] Tests XSS (2 tests)
- [ ] Tests SQL Injection (2 tests)
- [ ] Tests Rate Limiting (3 tests)
- [ ] Tests Sessions (3 tests)
- [ ] Tests Autorisations (4 tests)

### API
- [ ] Tests API Authentification (8 tests)
- [ ] Tests API Captcha (3 tests)
- [ ] Tests API Santé (5 tests)
- [ ] Tests API Trail (3 tests)

---

**TOTAL: ~250 tests**

---

*Document de tests complet - WellCare Connect*
*Dernière mise à jour: 2026-02-20*
