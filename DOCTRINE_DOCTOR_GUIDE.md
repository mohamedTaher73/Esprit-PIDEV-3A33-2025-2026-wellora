# Guide Doctrine Doctor - WellCare Connect

## 1. Installation du Bundle

```bash
# Installation via Composer
composer require --dev ahmed-bhs/doctrine-doctor

# Le bundle s'auto-configure via Symfony Flex
```

**Note** : Le bundle nécessite PHP 8.2+ et fonctionne avec Doctrine ORM 2.10+, 3.x et 4.x.

---

## 2. Configuration

### Fichier créé : `config/packages/dev/doctrine_doctor.yaml`

```yaml
doctrine_doctor:
    enabled: true
    
    analyzers:
        n_plus_one:
            enabled: true
            threshold: 5
            
        slow_query:
            enabled: true
            threshold: 100
            
        find_all_without_limit:
            enabled: true
            threshold: 100
            
        too_many_joins:
            enabled: true
            threshold: 4
            
    security:
        sql_injection:
            enabled: true
        sensitive_data_exposure:
            enabled: true
            
    code_quality:
        cascade_configuration:
            enabled: true
        bidirectional_inconsistency:
            enabled: true
        float_for_money:
            enabled: true
            
    collect_backtrace: true
```

### Activer les backtraces dans `config/packages/dev/doctrine.yaml` :

```yaml
doctrine:
    dbal:
        profiling_collect_backtrace: true
```

---

## 3. Commandes d'Analyse

### Utilisation via Symfony Web Profiler (Recommandée)

1. Rafraîchir n'importe quelle page de l'application en environnement `dev`
2. Ouvrir le **Symfony Web Profiler** (barre d'outils en bas)
3. Cliquer sur le panneau **"Doctrine Doctor"** 🩺

Les résultats apparaissent directement avec :
- 📍 **Backtrace** : Pointe vers la ligne exacte dans le template
- 💡 **Suggestion** : Ex: "Use `->addSelect(..)` to eager load"

---

## 4. Exemple de Rapport (Fictif)

### Sample Output - Web Profiler Panel

```
┌─────────────────────────────────────────────────────────────┐
│  🩺 Doctrine Doctor - Performance Report                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ⚠️  N+1 Query Detected                                     │
│  ─────────────────────────────────────────────              │
│  Entity: App\Entity\Patient                                 │
│  Query: SELECT p FROM App\Entity\Patient p                  │
│  Triggered: 15 additional queries                           │
│  Location: src/Controller/DoctorController.php:152           │
│                                                              │
│  💡 Suggestion:                                             │
│  Use ->addSelect('consultations') to eager load             │
│  $repository->findAllWithConsultations()                    │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ⚠️  Slow Query Detected                                    │
│  ─────────────────────────────────────────────              │
│  Query: SELECT * FROM health_entries                         │
│  Execution: 245ms (threshold: 100ms)                        │
│  Location: src/Service/HealthReportService.php:187          │
│                                                              │
│  💡 Suggestion:                                             │
│  Add index on (user_id, created_at)                         │
│  Add WHERE clause to limit results                          │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ⚠️  Missing Index                                          │
│  ─────────────────────────────────────────────              │
│  Table: users                                               │
│  Column: email (used in WHERE clause)                       │
│  Query: SELECT * FROM users WHERE email = ?                │
│                                                              │
│  💡 Fix:                                                    │
│  ALTER TABLE users ADD INDEX idx_email (email);             │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Analyse des Relations - Sample Output

```
┌─────────────────────────────────────────────────────────────┐
│  🩺 Doctrine Doctor - Relations Analysis                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ⚠️  Bidirectional Inconsistency                            │
│  ─────────────────────────────────────────────              │
│  Entity: App\Entity\Consultation                            │
│  Issue: mappedBy="consultation" vs inversedBy="medecin"     │
│  Fix: Update Consultation.php:consultations relationship    │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ⚠️  Missing Cascade Configuration                          │
│  ─────────────────────────────────────────────              │
│  Entity: App\Entity\User                                    │
│  Issue: healthJournals not configured with cascade          │
│  Fix: Add cascade={"persist", "remove"} to relationship    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Recommandations pour WellCare Connect

### A. Entité User (39 entités, Single Table Inheritance)

#### Problèmes détectés par PHPStan :
- Collections sans types génériques définis
- Propriétés `backupCodes`, `trustedDevices` sans type array

#### Recommandations Doctrine :

1. **Ajouter un index sur `email`** (utilisé pour l'authentification)
```sql
ALTER TABLE users ADD INDEX idx_user_email (email);
```

2. **Ajouter un index sur `licenseNumber`** (recherche professionnelle)
```sql
ALTER TABLE users ADD INDEX idx_user_license (licenseNumber);
```

3. **Vérifier les colonnes avec `nullable=true`** :
   - `resetToken` - devrait avoir un index si utilisé pour la recherche
   - `lastLoginAt` - utile pour un index composite

4. **Configuration cascade pour les relations** :
```php
// src/Entity/User.php
#[ORM\OneToMany(mappedBy: 'patient', targetEntity: Healthjournal::class, cascade: ['persist', 'remove'])]
private Collection $healthJournals;
```

---

### B. Entité ProfessionalVerification

#### Colonnes importantes pour les performances :
- `user_id` : FK vers users - index déjà présent (généré par Doctrine)
- `status` : utilisé pour les filtres - **recommander un index**
- `created_at` : utilisé pour le tri - **recommander un index**

```sql
ALTER TABLE professional_verification ADD INDEX idx_pv_status (status);
ALTER TABLE professional_verification ADD INDEX idx_pv_created (created_at);
```

---

### C. Entité Healthentry (Journal santé)

#### Problèmes potentiels :
- Table volumineuse avec plusieurs entrées par utilisateur
- Requêtes fréquentes par `user_id` et `created_at`

**Index recommandés** :
```sql
ALTER TABLE health_entry ADD INDEX idx_he_user_date (user_id, created_at);
ALTER TABLE health_entry ADD INDEX idx_he_journal_date (health_journal_id, created_at);
```

---

### D. Entité Consultation

#### Index recommandés :
```sql
-- Pour les recherches de rendez-vous par médecin
ALTER TABLE consultation ADD INDEX idx_cons_medecin_date (medecin_id, date_consultation);

-- Pour les recherches par patient
ALTER TABLE consultation ADD INDEX idx_cons_patient (patient_id);

-- Pour le statut (fréquemment filtré)
ALTER TABLE consultation ADD INDEX idx_cons_status (status);
```

---

### E. Entité NutritionGoal

#### Index recommandés :
```sql
ALTER TABLE nutrition_goal ADD INDEX idx_ng_user_status (user_id, status);
ALTER TABLE nutrition_goal ADD INDEX idx_ng_target_date (target_date);
```

---

## 6. Vérification des Types de Colonnes

### Problèmes courants détectés par Doctrine Doctor :

| Entité | Propriété | Type Actuel | Type Recommandé | Raison |
|--------|-----------|--------------|-----------------|--------|
| User | password | string | string | OK |
| User | loginAttempts | int | int | OK |
| NutritionGoal | weeklyWeightChangeTarget | string? | string? | OK (float peut être problématique pour l'argent) |
| Healthentry | glycemia | string | float | Précision |
| Consultation | tarif | string | float | Utilisé pour calculs |

### Vérifier avec :
```bash
# Dans le Web Profiler Doctrine Doctor panel
# Voir "Configuration" > "Column Type Analysis"
```

---

## 7. Commandes de Diagnostic

### Vérifier les indexes existants :
```sql
SHOW INDEX FROM users;
SHOW INDEX FROM health_entry;
SHOW INDEX FROM consultation;
```

### Analyser les performances de requêtes :
```sql
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
EXPLAIN SELECT * FROM health_entry WHERE user_id = 'uuid-here';
```

---

## 8. Résumé des Actions Prioritaires

1. **Ajouter les indexes** sur les colonnes fréquemment interrogées
2. **Vérifier les cascades** sur les relations OneToMany/ManyToOne
3. **Configurer Doctrine Doctor** via le Web Profiler en environnement dev
4. **Corriger les N+1 queries** identifiées dans les contrôleurs
5. **Valider les types** des colonnes pour les calculs (float vs string)

---

## 9. Pour Lancer l'Analyse

```bash
# Lancer le serveur Symfony
cd wellcare-connect3
php -d memory_limit=512M bin/console server:run

# Accéder à http://localhost:8000
# Naviguer dans l'application
# Ouvrir Web Profiler > Doctrine Doctor
```

**Note** : Doctrine Doctor fonctionne principalement via le Web Profiler en environnement `dev`. Il n'y a pas de commande CLI directe dans la version 1.x.
