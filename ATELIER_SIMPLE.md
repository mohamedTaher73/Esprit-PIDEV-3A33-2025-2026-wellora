# ATELIER DOCTRINE DOCTOR - QUOI FAIRE ?

## VOUS DEVEZ FAIRE SIMPLEMENT CECI :

### Étape 1 : VOIR les problèmes
Ouvrez le terminal et tapez :
```bash
cd wellcare-connect3
php bin/console doctrine:schema:validate
```

Ça va vous montrer les erreurs dans vos entités (relations mal configurées, etc.)

---

### Étape 2 : CORRIGER 2 problèmes au moins

Choisissez des erreurs et corrigez-les dans les fichiers entités.

**Exemple de correction possible :**

Si vous voyez une erreur comme :
```
The field User#nutritionGoals is on the inverse side... 
but the mappedBy on target-entity NutritionGoal#user does not contain 'inversedBy'
```

Il faut ajouter `inversedBy: "user"` dans NutritionGoal.php :

```php
// AVANT
#[ORM\ManyToOne(targetEntity: User::class)]
private ?User $user = null;

// APRÈS
#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'nutritionGoals')]
private ?User $user = null;
```

---

### Étape 3 : Générer une migration

Après avoir corrigé :
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

### Étape 4 : Vérifier

Relancez :
```bash
php bin/console doctrine:schema:validate
```

Si ça affiche "OK", c'est bon !

---

## CE QU'IL FAUT SAVOIR

- **Doctrine** = outil qui gère la base de données dans Symfony
- **Doctrine Doctor** = outil qui ANALYSE les problèmes de performance et d'intégrité
- Les erreurs常见 = relations mal configurées, indexes manquants, requêtes lentes

---

## RÉSUMÉ EN 3 LIGNES

1. Tapez `php bin/console doctrine:schema:validate` pour voir les erreurs
2. Corrigez les erreurs dans les fichiers Entity
3. Générez et lancez les migrations

C'est tout ! 🚀
