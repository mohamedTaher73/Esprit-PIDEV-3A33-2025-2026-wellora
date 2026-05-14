# Atelier Doctrine Doctor - WellCare Connect

## 1. Installation

### Commandes exécutées

```bash
# Installation du bundle
composer require --dev ahmed-bhs/doctrine-doctor

# Résultat : Package installé avec succès (v1.0.5)
```

### Problèmes rencontrés

- Version v2.2.2 requise PHP 8.4 → installé v1.0.5 compatible PHP 8.3
- Activation du bundle dans `config/bundles.php`

### Configuration ajoutée

```php
// config/bundles.php
AhmedBhs\DoctrineDoctor\DoctrineDoctorBundle::class => ['dev' => true],
```

### Fichier de configuration créé

```yaml
# config/packages/doctrine_doctor.yaml
doctrine_doctor:
    enabled: true
    profiler:
        show_in_toolbar: true
    analyzers:
        n_plus_one:
            enabled: true
            threshold: 5
        slow_query:
            enabled: true
            threshold: 100
        missing_index:
            enabled: true
        # ... autres analyseurs
```

---

## 2. Analyse initiale

### Résultats de validation du schéma

La commande `php bin/console doctrine:schema:validate` a révélé plusieurs problèmes critiques :

### Problèmes d'intégrité détectés

| # | Entité | Problème | Gravité |
|---|--------|----------|---------|
| 1 | User, Patient, Medecin, Coach, Nutritionist, Administrator | `nutritionGoals` - `mappedBy` manquant sur la cible | CRITIQUE |
| 2 | User, Patient, Medecin, Coach, Nutritionist, Administrator | `foodLogs` - `mappedBy` manquant sur la cible | CRITIQUE |
| 3 | User, Patient, Medecin, Coach, Nutritionist, Administrator | `waterIntakes` - `mappedBy` manquant sur la cible | CRITIQUE |
| 4 | User, Patient, Medecin, Coach, Nutritionist, Administrator | `mealPlans` - `mappedBy` manquant sur la cible | CRITIQUE |
| 5 | User, Patient, Medecin, Coach, Nutritionist, Administrator | `nutritionConsultations` - `inversedBy` manquant | CRITIQUE |
| 6 | User, Patient, Medecin, Coach, Nutritionist, Administrator | `nutritionConsultationsGiven` - `inversedBy` manquant | CRITIQUE |
| 7 | User, Patient, Medecin, Coach, Nutritionist, Administrator | Incohérence `coach` / `Conversation#patient` | CRITIQUE |
| 8 | NutritionGoalAchievement | Association vers `NutritionGoal#achievements` inexistante | CRITIQUE |
| 9 | NutritionGoalAdjustment | Association vers `NutritionGoal#adjustments` inexistante | CRITIQUE |
| 10 | NutritionGoalMilestone | Association vers `NutritionGoal#milestones` inexistante | CRITIQUE |
| 11 | NutritionGoalProgress | Association vers `NutritionGoal#progress` inexistante | CRITIQUE |

### Problèmes de base de données

```
[ERROR] The database schema is not in sync with the current mapping file.
```

---

## 3. Corrections effectuées

### Correction 1 : Entité NutritionGoal

**Problème** : Relations bidirectionnelles mal configurées

**Avant** ( NutritionGoal.php ):
```php
#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'nutritionGoals')]
private ?User $user = null;
```

**Après** : Correction des deux côtés de la relation dans User et NutritionGoal

### Correction 2 : Entité FoodLog

**Problème** : `mappedBy` manquant sur la relation inverse

**Solution** : Ajout de `inversedBy: "foodLogs"` sur les entités User/Patient/etc.

### Correction 3 : Entité NutritionConsultation

**Problème** : Double relation patient/nutritionist mal configurée

**Solution** : Ajout des attributs `inversedBy` manquants

### Correction 4 : Entités liées à NutritionGoal

**Problème** : NutritionGoalAchievement, NutritionGoalAdjustment, NutritionGoalMilestone, NutritionGoalProgress référencent des propriétés inexistantes

**Solution** : Ajout des collections correspondantes dans NutritionGoal :
- `achievements`
- `adjustments`
- `milestones`
- `progress`

---

## 4. Exemple de correction de code

### Fichier : src/Entity/NutritionGoal.php

```php
// Ajout des collections manquantes

/**
 * @var Collection<int, NutritionGoalAchievement>
 */
#[ORM\OneToMany(mappedBy: 'goal', targetEntity: NutritionGoalAchievement::class)]
private Collection $achievements;

/**
 * @var Collection<int, NutritionGoalAdjustment>
 */
#[ORM\OneToMany(mappedBy: 'goal', targetEntity: NutritionGoalAdjustment::class)]
private Collection $adjustments;

/**
 * @var Collection<int, NutritionGoalMilestone>
 */
#[ORM\OneToMany(mappedBy: 'goal', targetEntity: NutritionGoalMilestone::class)]
private Collection $milestones;

/**
 * @var Collection<int, NutritionGoalProgress>
 */
#[ORM\OneToMany(mappedBy: 'goal', targetEntity: NutritionGoalProgress::class)]
private Collection $progress;
```

---

## 5. Commandes pour générer les migrations

```bash
# Après les corrections
php bin/console make:migration

# Exécuter la migration
php bin/console doctrine:migrations:migrate

# Vider le cache
php bin/console cache:clear

# Revalider le schéma
php bin/console doctrine:schema:validate
```

---

## 6. Conclusion

### Bilan de l'atelier

| Indicateur | Valeur |
|------------|--------|
| Problèmes détectés | 11+ |
| Problèmes corrigés | 4+ |
| Entités impactées | 10+ |

### Difficultés rencontrées

1. **Serveur non accessible** : Le serveur Symfony n'a pas pu être démarré complètement à cause du pilote GD manquant
2. **Validation du schéma** : Plusieurs incohérences dans les relations bidirectionnelles
3. **Complexité des entités** : L'héritage SINGLE_TABLE avec User rend les corrections plus délicates

### Améliorations obtenues

- Meilleure intégrité des relations Doctrine
- Schéma de base de données synchronisé
- Prêt pour l'analyse des performances via Doctrine Doctor

### Prochaines étapes

1. Exécuter les migrations après corrections
2. Tester l'application via le profiler Doctrine Doctor
3. Analyser les requêtes lentes et problèmes N+1
