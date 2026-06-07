# Documentation des APIs Principales — Wellora

Ce document fournit la spécification technique et la documentation des principales routes d'API REST exposées par le backend Symfony de Wellora. Ces endpoints permettent de gérer l'authentification, la prise de rendez-vous, le suivi des patients, la messagerie et les recommandations intelligentes basées sur la météo.

---

## Table des Matières
1. [Authentification & Validation](#1-authentification--validation)
2. [Gestion des Rendez-vous (Patient & Médecin)](#2-gestion-des-rendez-vous-patient--médecin)
3. [Dossier Médical & Notes Cliniques](#3-dossier-médical--notes-cliniques)
4. [Messagerie & Chatbot IA](#4-messagerie--chatbot-ia)
5. [Plans d'Entraînement Journaliers](#5-plans-dentraînement-journaliers)
6. [Parcours de Santé & Recommandations](#6-parcours-de-santé--recommandations)

---

## 1. Authentification & Validation

### Validation des Identifiants (Login)
* **Route :** `POST /api/login/validate`
* **Description :** Valide les identifiants d'un utilisateur et vérifie son statut (si actif, vérifié, etc.).
* **Request Body (JSON) :**
  ```json
  {
    "email": "user@example.com",
    "password": "Password123"
  }
  ```
* **Success Response (200 OK) :**
  ```json
  {
    "success": true,
    "user": {
      "uuid": "4f87a8b9-8e4d-4c3a-9f5e-1a2b3c4d5e6f",
      "email": "user@example.com",
      "roles": ["ROLE_PATIENT", "ROLE_USER"],
      "firstName": "John",
      "lastName": "Doe"
    }
  }
  ```
* **Error Response (400/401 Unauthorized) :**
  ```json
  {
    "success": false,
    "message": "Identifiants ou mot de passe incorrects."
  }
  ```

### Demande de Réinitialisation de Mot de Passe
* **Route :** `POST /api/forgot-password`
* **Description :** Envoie un e-mail de réinitialisation de mot de passe à l'utilisateur.
* **Request Body (JSON) :**
  ```json
  {
    "email": "patient@example.com"
  }
  ```
* **Success Response (200 OK) :**
  ```json
  {
    "success": true,
    "message": "Un email de réinitialisation a été envoyé si l'adresse existe."
  }
  ```

### Validation des Champs d'Inscription
* **Routes :**
  * `POST /api/check-email` (Vérification de l'unicité de l'email)
  * `POST /api/check-phone` (Vérification de l'unicité du téléphone)
  * `POST /api/check-license-number` (Vérification du numéro de licence pour les médecins)
* **Request Body (JSON) :**
  ```json
  {
    "email": "newuser@example.com"
  }
  ```
* **Success Response (200 OK) :**
  ```json
  {
    "available": true
  }
  ```

---

## 2. Gestion des Rendez-vous (Patient & Médecin)

### Recherche de Médecins (Filtres Avancés)
* **Route :** `GET /appointment/api/doctors/search`
* **Description :** Filtre et trie les médecins actifs et vérifiés par l'administration.
* **Query Parameters :**
  * `specialty` (string, optionnel) — Spécialité médicale
  * `location` (string, optionnel) — Ville ou adresse
  * `q` (string, optionnel) — Recherche par nom/prénom
  * `sort` (string, optionnel) — Tri (`rating`, `experience`, `price-low`, `price-high`)
* **Success Response (200 OK) :**
  ```json
  {
    "success": true,
    "count": 1,
    "doctors": [
      {
        "id": 12,
        "uuid": "7a8d9f0e-1b2c-3d4e-5f6a-7b8c9d0e1f2a",
        "name": "Dr. Sarah Martin",
        "email": "dr.martin@wellora.com",
        "phone": "+216 55 555 555",
        "specialty": "Cardiology",
        "experience": 12,
        "price": 120,
        "rating": 4.8
      }
    ]
  }
  ```

### Création d'un Rendez-vous (Patient)
* **Route :** `POST /appointment/create`
* **Description :** Crée une nouvelle demande de consultation en attente (`pending`).
* **Request Body (JSON) :**
  ```json
  {
    "doctorId": "7a8d9f0e-1b2c-3d4e-5f6a-7b8c9d0e1f2a",
    "consultationType": "Suivi annuel",
    "reason": "Douleurs thoraciques légères",
    "symptoms": ["fatigue", "palpitations"],
    "selectedDate": "2026-06-15",
    "selectedTime": "10:30 AM",
    "appointmentMode": "in-person",
    "notes": "Si possible avant midi"
  }
  ```
* **Success Response (200 OK) :**
  ```json
  {
    "success": true,
    "message": "Rendez-vous créé avec succès et en attente de validation",
    "appointmentId": 45
  }
  ```

### Acceptation / Refus de Rendez-vous (Médecin)
* **Routes :**
  * `POST /appointment/api/doctor/accept/{id}` (Accepter)
  * `POST /appointment/api/doctor/reject/{id}` (Refuser)
* **Description :** Modifie le statut de la consultation (`accepted` ou `rejected`).
* **Success Response (200 OK) :**
  ```json
  {
    "success": true,
    "message": "Rendez-vous accepté avec succès",
    "appointmentId": 45,
    "newStatus": "accepted"
  }
  ```

### Liste des Rendez-vous Associés (Patient)
* **Route :** `GET /appointment/api/appointments`
* **Description :** Récupère les rendez-vous à venir, passés ou annulés du patient connecté.
* **Success Response (200 OK) :**
  ```json
  {
    "upcoming": [
      {
        "id": 45,
        "doctorName": "Dr. Sarah Martin",
        "specialty": "Cardiology",
        "month": "Jun",
        "day": "15",
        "weekday": "Mon",
        "time": "10:30",
        "type": "in-person",
        "status": "accepted"
      }
    ],
    "past": [],
    "cancelled": []
  }
  ```

---

## 3. Dossier Médical & Notes Cliniques

### Récupération du Dossier Patient Complet
* **Route :** `GET /health/doctor/api/patient-chart/{id}`
* **Description :** Fournit toutes les données cliniques liées à un patient via l'ID de sa consultation (Signes vitaux, antécédents, traitements, examens de laboratoire).
* **Success Response (200 OK) :**
  ```json
  {
    "success": true,
    "data": {
      "patient": {
        "id": 45,
        "name": "Jean Dupont",
        "age": 42,
        "bmi": 24.2,
        "status": "active",
        "healthScore": 85
      },
      "timeline": [
        {
          "id": "MED-3",
          "type": "medication",
          "title": "Prescription: Doliprane 1000mg",
          "date": "10/06/2026"
        }
      ],
      "vitalSigns": [
        {
          "date": "10/06/2026",
          "bloodPressure": "120/80",
          "heartRate": 72,
          "temperature": 36.8
        }
      ]
    }
  }
  ```

### Enregistrement des Notes Cliniques (Consultation SOAP)
* **Route :** `POST /health/doctor/api/clinical-notes/save`
* **Description :** Enregistre ou met à jour la consultation et ses notes cliniques structurées.
* **Request Body (JSON) :**
  ```json
  {
    "consultation": {
      "id": 45,
      "diagnoses": ["Hypertension"],
      "assessment": "Patient stable mais surveillance requise",
      "plan": "Continuer le traitement en cours et repos",
      "notes": "Contrôle dans 3 mois",
      "soapNotes": {
        "subjective": "Se sent fatigué le matin",
        "objective": "Tension artérielle 138/85",
        "assessment": "Légère hausse de tension",
        "plan": "Suivi tensionnel sur 1 semaine"
      }
    }
  }
  ```
* **Success Response (200 OK) :**
  ```json
  {
    "success": true,
    "message": "Note clinique enregistrée avec succès"
  }
  ```

---

## 4. Messagerie & Chatbot IA

### Envoi de Message au Chatbot IA (Gemini)
* **Route :** `POST /chatbot-ia/message`
* **Description :** Envoie un message médical ou général à l'IA avec gestion de l'historique et détection d'urgence critique.
* **Request Body (JSON) :**
  ```json
  {
    "message": "J'ai une forte douleur à la poitrine depuis 10 minutes"
  }
  ```
* **Success Response (200 OK - Alerte Urgence) :**
  ```json
  {
    "message": "⚠️ **URGENCE DÉTECTÉE** ⚠️\n🔴 **ACTION IMMÉDIATE :** Appelez le 15 (SAMU) sans attendre.",
    "level": "rouge"
  }
  ```
* **Success Response (200 OK - Question standard) :**
  ```json
  {
    "message": "Pour atténuer un mal de tête léger, reposez-vous dans une pièce sombre...",
    "level": "info"
  }
  ```

### Réinitialisation du Chatbot
* **Route :** `POST /chatbot-ia/reset`
* **Description :** Vide l'historique de la conversation chatbot de la session courante.
* **Success Response (200 OK) :**
  ```json
  {
    "message": "Conversation réinitialisée. Comment puis-je vous aider ?",
    "level": "info"
  }
  ```

### Messagerie Directe Coach / Patient
* **Routes :**
  * `GET /chat/conversation/{id}` (Récupérer l'historique des messages d'un fil)
  * `POST /chat/send/{id}` (Envoyer un message dans une conversation)
  * `GET /chat/unread/count` (Nombre total de messages non lus)
* **Request Parameters (pour `POST /chat/send/{id}`) :**
  * Form Data parameter: `content` (string) — Le texte du message à envoyer.
* **Success Response (`GET /chat/conversation/{id}`) :**
  ```json
  {
    "conversationId": 8,
    "participant": {
      "name": "Coach Marc",
      "avatar": "M"
    },
    "messages": [
      {
        "id": 142,
        "sender": "coach",
        "content": "Bonjour, comment s'est passé votre entraînement ?",
        "time": "09:30",
        "date": "2026-06-07"
      },
      {
        "id": 143,
        "sender": "me",
        "content": "Très bien, j'ai fini toutes les séries !",
        "time": "09:35",
        "date": "2026-06-07"
      }
    ]
  }
  ```

---

## 5. Plans d'Entraînement Journaliers

### Récupération des Plans par Client (Coach View)
* **Route :** `GET /coach/client/{clientId}/plans`
* **Description :** Récupère la liste des plans journaliers assignés à un patient par son coach.
* **Success Response (200 OK) :**
  ```json
  {
    "plans": [
      {
        "id": 14,
        "title": "Cardio intensif et renforcement",
        "date": "2026-06-07",
        "duration": 45,
        "calories": 350,
        "status": "planned",
        "notes": "Hydratez-vous bien pendant la séance",
        "exercises": [
          {
            "id": 5,
            "name": "Burpees",
            "duration": 15,
            "calories": 120
          }
        ]
      }
    ]
  }
  ```

---

## 6. Parcours de Santé & Recommandations

### Recommandation IA d'un Parcours par la Météo et Géolocalisation
* **Route :** `POST /parcours/de/sante/ai/recommend`
* **Description :** Recommande le parcours le plus proche correspondant aux préférences météo.
* **Request Body (JSON) :**
  ```json
  {
    "location": "Tunis",
    "weather": "clear",
    "latitude": 36.8065,
    "longitude": 10.1815
  }
  ```
* **Success Response (200 OK) :**
  ```json
  {
    "ok": true,
    "message": "I recommend Parcours El Menzah in Tunis. It is 2.4 km from you and the current weather there is Clear sky. This matches your preferred weather.",
    "recommendation": {
      "id": 4,
      "name": "Parcours El Menzah",
      "location": "Tunis",
      "distance_km": 2.4,
      "weather_condition": "Clear sky",
      "weather_match": true,
      "details_url": "/parcours/de/sante/4"
    }
  }
  ```
