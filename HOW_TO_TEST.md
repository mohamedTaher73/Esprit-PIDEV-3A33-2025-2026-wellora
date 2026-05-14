# Guide pour Tester Manuellement

## Étape 1: Préparer l'Environnement

### Démarrer le serveur
```bash
cd wellcare-connect3

# Option 1: Serveur PHP内置
php bin/console server:run

# Option 2: Avec Docker
docker-compose up -d
```

### Accéder à l'application
- Ouvrez votre navigateur à: **http://127.0.0.1:8000**

---

## Étape 2: Tests d'Authentification

### Test 1: Login Patient
1. Allez à `/login`
2. Entrez: `patient@test.com` / `Patient@123`
3. Cliquez "Se connecter"
4. ✓ Devrait être connecté au dashboard patient

### Test 2: Login Médecin
1. Allez à `/login`
2. Entrez: `doctor@test.com` / `Doctor@123`
3. Cliquez "Se connecter"
4. ✓ Devrait être connecté au dashboard médecin

### Test 3: Login Admin
1. Allez à `/login`
2. Entrez: `admin@wellcare.tn` / `Admin@123`
3. Cliquez "Se connecter"
4. ✓ Devrait être connecté au dashboard admin

### Test 4: Inscription Patient
1. Allez à `/register/patient`
2. Remplissez le formulaire:
   - Email: `newpatient@test.com`
   - Mot de passe: `NewPatient@123`
   - Prénom: Test
   - Nom: Patient
   - Date de naissance: 01/01/1990
   - Téléphone: +21612345678
3. Acceptez les CGU
4. Cliquez "S'inscrire"
5. ✓ Devrait rediriger vers vérification email

### Test 5: Inscription Médecin (avec diplôme)
1. Allez à `/register/medecin`
2. Remplissez:
   - Email: `newdoctor@test.com`
   - Mot de passe: `NewDoctor@123`
   - Prénom: Test
   - Nom: Doctor
   - Spécialité: Cardiologie
   - Numéro de licence: `LIC-2024-001`
3. **Téléchargez un fichier PDF ou image de diplôme**
4. Cliquez "S'inscrire"
5. ✓ Devrait créer demande de vérification

---

## Étape 3: Tests Admin - Vérification Diplômes

### Test 6: Dashboard Vérification (Admin)
1. Connectez-vous en admin
2. Allez à `/admin/verification`
3. ✓ Devrait afficher:
   - Nombre de vérifications en attente
   - Liste des professionnels en attente

### Test 7: Approuver un Médecin
1. Dans `/admin/verification`, cliquez sur une demande
2. Vérifiez les détails (diplôme, numéro de licence)
3. Cliquez "Approuver"
4. Confirmez
5. ✓ Le statut devrait changer à "verified"

### Test 8: Rejeter un Médecin
1. Dans `/admin/verification`, cliquez sur une demande
2. Cliquez "Rejeter"
3. Entrez une raison: "Diplôme illisible"
4. Confirmez
5. ✓ Le statut devrait changer à "rejected"

---

## Étape 4: Tests des Routes Protégées

### Test 9: Accès Refusé
1. Déconnectez-vous
2. Essayez d'aller à `/admin/dashboard`
3. ✓ Devrait rediriger vers login

### Test 10: Accès par Rôle
1. Connectez-vous comme patient
2. Essayez d'aller à `/doctor/dashboard`
3. ✓ Devrait dire "Accès refusé"

---

## Checklist Rapide

| Test | URL | Résultat Attendu |
|------|-----|------------------|
| Login patient | /login | Dashboard patient |
| Login admin | /login | Dashboard admin |
| Register patient | /register/patient | Email vérification |
| Register doctor | /register/medecin | Demande vérification |
| Vérifications admin | /admin/verification | Liste demandes |
| Approuver | /admin/verification/approve/{id} | Statut = verified |
| Rejeter | /admin/verification/reject/{id} | Statut = rejected |

---

## Résolution des Problèmes

### Si erreur 500:
```bash
# Vider le cache
php bin/console cache:clear

# Vérifier les logs
tail -f var/log/dev.log
```

### Si erreur base de données:
```bash
# Mettre à jour le schéma
php bin/console doctrine:migrations:migrate
```

### Si erreur de permissions:
```bash
# Fixer les permissions
chmod -R 777 var/
chmod -R 777 public/uploads/
```
