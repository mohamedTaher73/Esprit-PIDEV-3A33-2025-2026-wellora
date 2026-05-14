# Comment Tester le Système de Vérification des Diplômes

## Étape 1: Créer un compte professionnel (Médecin/Coach/Nutritionist)

1. Allez à `/register/medecin` (ou coach, nutritionist)
2. Remplissez le formulaire:
   - Email: `testdoctor@test.com`
   - Mot de passe: `TestDoctor@123`
   - Prénom: Ahmed
   - Nom: Ben Ali
   - Spécialité: Cardiologie
   - Numéro de licence: `LIC-2024-001`
3. **Téléchargez un fichier de diplôme** (PDF ou image)
4. Cliquez "S'inscrire"
5. ✓ Un message de succès devrait apparaître

---

## Étape 2: Vérifier que la demande est créée

1. Connectez-vous en admin: `/login`
   - Email: `admin@wellcare.tn`
   - Mot de passe: `Admin@123`
2. Allez à `/admin/verification`
3. ✓ Vous devriez voir la demande en attente

---

## Étape 3: Tester le traitement automatique

### Scénario 1: Score ≥80 (Approbation automatique)
Le système approve automatiquement si le score de confiance est ≥80.

### Scénario 2: Score 60-79 (Review manuel)
Le système marque pour review manuel.

### Scénario 3: Score <60 (Rejet automatique)
Le système rejette automatiquement.

---

## Étape 4: Approuver manuellement

1. Dans `/admin/verification`, cliquez sur une demande
2. Vérifiez les détails:
   - Nom du professionnel
   - Spécialité
   - Numéro de licence
   - Fichier de diplôme
   - Score de confiance
3. Cliquez "Approuver"
4. Entrez un commentaire (optionnel)
5. Cliquez "Confirmer"
6. ✓ Le statut change à "verified"

---

## Étape 5: Rejeter manuellement

1. Dans `/admin/verification`, cliquez sur une demande
2. Cliquez "Rejeter"
3. Entrez une raison: "Diplôme non authentique"
4. Cliquez "Confirmer"
5. ✓ Le statut change à "rejected"

---

## Étape 6: Tester le retraitement

1. Allez à `/admin/verification/view/{id}`
2. Cliquez "Retraiter"
3. ✓ Le système re-analyse le diplôme

---

## Comment le Score est Calculé

Le système analyse:
1. **OCR Extraction** - Texte du PDF
2. **Nom Matching** - Est-ce que le nom correspond?
3. **Spécialité** - La spéciale est-elle reconnue?
4. **Numéro de licence** - Format valide?
5. **Indicateurs de falsification** - Signes suspects

### Exemple de Score:
- Nom trouvé + Spécialité trouvée + Licence valide = Score élevé (≥80)
- Nom trouvé + Spécialité trouvée = Score moyen (60-79)
- Pas de nom ni spéciale = Score bas (<60)

---

## Résumé des Routes

| Action | Route |
|--------|-------|
| Liste vérifications | `/admin/verification` |
| Détails vérification | `/admin/verification/view/{id}` |
| Approuver | `/admin/verification/approve/{id}` |
| Rejeter | `/admin/verification/reject/{id}` |
| Retraiter | `/admin/verification/reprocess/{id}` |
| Statistiques | `/admin/verification/statistics` |
