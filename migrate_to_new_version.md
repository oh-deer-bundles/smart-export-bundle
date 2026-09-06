# Guide de migration entre versions

Ce document liste, version par version, les changements cassants (breaking changes) du bundle et les étapes à suivre côté application hôte pour les absorber. À compléter à chaque nouvelle version qui casse la compatibilité.

## Depuis v0.2.01 (non taguée pour l'instant)

### Ce qui change

`SmartExportEngine` :
- Nouveau champ `uuid` (`Symfony\Component\Uid\UuidV7`), généré automatiquement à la création de l'entité, unique, non nullable. C'est désormais **le seul identifiant fiable** de l'engine.
- Nouveau champ `name` (`?string`, nullable en base), destiné à l'affichage.
- `code` devient **nullable** et **n'est plus contrôlé en unicité** (le contrôle qui existait dans `SmartExportEngineRepository::save()` a été supprimé). Il reste disponible comme champ libre, mais ne doit plus être utilisé pour retrouver un engine.

API publique du bundle :
- `SmartExportInterface::add(string $code, ...)` → `add(string $uuid, ...)`
- `SmartExportInterface::createForm(string $code, ...)` → `createForm(string $uuid, ...)`
- `SmartExportInterface::findByCode(string $code)` → `findByUuid(string $uuid)`
- `SmartExportAdminInterface` : `removeEngine`, `toggleEngine`, `handleFormEditEngine`, `findByCode` prennent désormais un `$uuid` (renommé `findByUuid`).
- Option de formulaire interne `code_export` renommée `uuid_export` (impacte uniquement du code qui étendrait `SmartExportType`).

Routes admin (`config/routes.yaml`, ex `routes.xml`) :
- `/admin/edit/{code}` → `/admin/edit/{uuid}`
- `/admin/toggle/{code}` → `/admin/toggle/{uuid}`
- `/admin/remove/{code}` → `/admin/remove/{uuid}`
- `/admin/demo-export/{code}` → `/admin/demo-export/{uuid}`

Configuration du bundle :
- `config/services.xml` → `config/services.yaml`, `config/routes.xml` → `config/routes.yaml` (le format XML de Symfony DI/Routing est déprécié depuis Symfony 7.4 et sera supprimé en 8). Le mapping Doctrine (`config/doctrine/*.orm.xml`) reste en XML, ce format n'est pas concerné.

### Étapes de migration côté application hôte

1. **Mettre à jour la dépendance** puis vider le cache Symfony comme d'habitude.

2. **Adapter tout code appelant l'API publique du bundle.** Partout où vous appeliez `SmartExport::add($code)` / `createForm($code)` avec le `code` métier d'un engine, il faut désormais passer son `uuid` :

   ```php
   // avant
   $smartExport->add('mon_export');

   // après
   $engine = $engineRepository->findOneBy(['code' => 'mon_export']); // ou par name, selon votre usage
   $smartExport->add((string) $engine->getUuid());
   ```

   Si vous stockiez le `code` en dur quelque part (config, base, lien favori...) pour identifier un export, remplacez-le par l'`uuid` de l'engine correspondant.

3. **Adapter les routes/liens personnalisés** si vous aviez construit vos propres pages d'administration (cf. README, section "build your own pages") en vous basant sur `{code}` : passez à `{uuid}` (`$engine->getUuid()`).

4. **Écrire la migration Doctrine.** Le bundle ne fournit pas de migration lui-même (les migrations appartiennent à l'application hôte). Générer un diff automatique (`doctrine:migrations:diff`) ne suffit pas : il ajouterait `uuid` directement en `NOT NULL`, ce qui échoue s'il existe déjà des lignes dans `smart_export_engine`. Il faut :
   1. ajouter `uuid` en **nullable**,
   2. **backfiller** chaque ligne existante avec un `UuidV7` généré côté PHP (pas de fonction SQL native pour ça),
   3. **seulement ensuite** repasser `uuid` en `NOT NULL` et poser l'index unique.

   Exemple réel, tel qu'appliqué dans themis (`themis/migrations/Version20260906095014.php`) :

   ```php
   <?php

   declare(strict_types=1);

   namespace App\Migrations;

   use Doctrine\DBAL\Schema\Schema;
   use Doctrine\Migrations\AbstractMigration;
   use Symfony\Component\Uid\Uuid;

   final class Version20260906095014 extends AbstractMigration
   {
       public function getDescription(): string
       {
           return 'Add uuid (identifier) and name to smart_export_engine, make code nullable';
       }

       public function up(Schema $schema): void
       {
           // 1. Colonnes ajoutées en nullable (uuid) / DEFAULT NULL (name),
           //    et code passé en nullable au passage.
           $this->addSql(<<<'SQL'
               ALTER TABLE
                 smart_export_engine
               ADD
                 uuid BINARY(16) DEFAULT NULL,
               ADD
                 name VARCHAR(255) DEFAULT NULL,
               CHANGE
                 code code VARCHAR(16) DEFAULT NULL
           SQL);

           // 2. Backfill : un UuidV7 généré en PHP pour chaque ligne existante.
           //    Nécessaire car MySQL/MariaDB n'a pas de fonction native pour
           //    générer un UUID v7 (UUID() génère un UUID v1).
           foreach ($this->connection->fetchAllAssociative('SELECT id FROM smart_export_engine') as $row) {
               $this->addSql(
                   'UPDATE smart_export_engine SET uuid = :uuid WHERE id = :id',
                   ['uuid' => Uuid::v7()->toBinary(), 'id' => $row['id']]
               );
           }

           // 3. Une fois toutes les lignes renseignées, on peut contraindre.
           $this->addSql('ALTER TABLE smart_export_engine CHANGE uuid uuid BINARY(16) NOT NULL');
           $this->addSql('CREATE UNIQUE INDEX UNIQ_D126E6D8D17F50A6 ON smart_export_engine (uuid)');
       }

       public function down(Schema $schema): void
       {
           $this->addSql('DROP INDEX UNIQ_D126E6D8D17F50A6 ON smart_export_engine');
           $this->addSql('ALTER TABLE smart_export_engine DROP uuid, DROP name, CHANGE code code VARCHAR(16) NOT NULL');
       }
   }
   ```

   Points d'attention si vous adaptez cet exemple à votre propre projet :
   - Le nom de la table (`smart_export_engine`) et le préfixe/nom de l'index unique dépendent de votre configuration Doctrine (`naming_strategy`) — régénérez le nom de l'index avec `doctrine:migrations:diff` plutôt que de copier `UNIQ_D126E6D8D17F50A6` tel quel.
   - `Uuid::v7()->toBinary()` suppose une colonne `BINARY(16)`, cohérent avec le type Doctrine `uuid` (`Symfony\Bridge\Doctrine\Types\UuidType`). Si votre projet stocke ses uuid en `CHAR(36)` ailleurs, adaptez en conséquence (`Uuid::v7()->toRfc4122()`) — mais alors changez aussi le mapping XML du bundle en local, ou ouvrez le sujet en amont : le mapping livré par le bundle utilise `type="uuid"` sans préciser de stratégie de stockage, donc c'est le type Doctrine enregistré dans votre projet qui décide du format colonne.
   - `down()` échouera si des lignes ont un `code` à `NULL` au moment du rollback : c'est un choix assumé (on ne devine pas quoi remettre à la place).

5. **Vérifier** : `bin/console cache:clear`, `bin/console doctrine:schema:validate`, puis un tour dans l'admin (`/smart-export/admin/` par défaut) pour confirmer que les exports existants sont bien listés avec leur uuid et que les actions (edit/toggle/remove/demo-export) fonctionnent.
