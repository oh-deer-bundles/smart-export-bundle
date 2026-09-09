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

### Nouvelles fonctionnalités (non cassantes, aucune migration requise)

#### Limite de lignes (`max_rows`)

Un export ne peut plus être généré au-delà d'un certain nombre de lignes réellement produites (le comptage tient compte du fan-out des relations to-many jointes, pas uniquement des entités primaires distinctes). Un bouton "Vérifier" dans le popup d'export interroge `/admin/count/{uuid}` et débloque "Generate" seulement si le résultat est dans la limite.

Configuration (optionnelle, valeur par défaut `20000`) :

```yaml
# config/packages/odb_smart_export.yaml (ou tbl_smart_export.yaml selon le nom retenu côté hôte)
tbl_smart_export:
    max_rows: 20000
```

#### Sécurité : restriction par ids autorisés (`security.restricted_entities`)

Le bundle peut restreindre les lignes retournées pour certaines entités à une liste d'ids autorisée, par utilisateur. **La restriction est une propriété de l'entité, pas de l'export** : elle s'applique partout où l'entité apparaît dans le graphe de jointures d'un export — qu'elle soit l'entité primaire ou une relation jointe (imbriquée ou non). Par exemple, si `Customer` est restreint, un export `Item -> Contract -> Customer` est filtré exactement comme le serait un export dont `Customer` est l'entité primaire.

**Entièrement géré côté bundle, à la demande (pull), jamais via un événement.** `AllowedIdsResolver` appelle lui-même, au besoin, un service hôte implémentant `AllowedIdsVoterInterface::getAllowedIds(string $className): array`, et met le résultat en cache **lui-même** pendant `security.allowed_ids_ttl` secondes (défaut 10 min). Il n'y a rien à brancher sur un listener de login/switch-user :

```php
namespace Odb\SmartExportBundle\Services;

interface AllowedIdsVoterInterface
{
    /** @return array<int, int|string> ids de $className autorisés pour l'utilisateur courant. */
    public function getAllowedIds(string $className): array;
}
```

```yaml
odb_smart_export:
    security:
        allowed_ids_cache_pool: cache.app     # pool CacheInterface (get-or-compute), défaut cache.app
        allowed_ids_ttl: 600                  # secondes, défaut 600 (10 min)
        ids_voter: App\Services\Security\DataAccess\SmartExportSecurityManager
        restricted_entities:
            - App\Entity\Customer\Customer
```

```php
// App\Services\Security\DataAccess\SmartExportSecurityManager
use Odb\SmartExportBundle\Services\AllowedIdsVoterInterface;

class SmartExportSecurityManager implements AllowedIdsVoterInterface
{
    public function getAllowedIds(string $className): array
    {
        if (Customer::class !== $className) {
            return [];
        }
        // logique métier hôte — peut elle-même être mise en cache (ex. IdsAccessCache,
        // 2h de TTL dans themis) sans que ça pose problème : AllowedIdsResolver
        // met de toute façon son propre résultat en cache par-dessus.
        return $this->idsAccessCache->getCustomerIdsAccess($this->security->getUser());
    }
}
```

**Ancien design (abandonné) :** le bundle ne faisait que *lire* un cache que l'application hôte devait *écrire* de son côté (typiquement depuis un listener `InteractiveLoginEvent`/`SwitchUserEvent`), via `AllowedIdsResolver::cacheKeyFor()`. Piège réel rencontré en production : toute authentification qui ne déclenche pas cet événement précis (reprise de session, un chemin d'authentification différent…) ne remplissait jamais l'entrée de cache — et comme une entrée manquante échoue **fermé** (zéro ligne, jamais "pas de restriction"), l'utilisateur perdait tout accès aux exports concernés sans raison apparente. Le nouveau design (pull + TTL court, entièrement dans le bundle) élimine cette classe de bug : `getAllowedIdsIfRestricted()` calcule lui-même la valeur au premier accès, quel que soit le chemin d'authentification emprunté.

`AllowedIdsResolver::invalidate(string $entityClass, string $userIdentifier)` reste disponible pour invalider immédiatement le cache d'un utilisateur après un changement de droits (au lieu d'attendre l'expiration du TTL) — entièrement optionnel, la correctness n'en dépend jamais.

Comportement **fail closed** inchangé : si une entité est listée dans `restricted_entities` mais que le voter renvoie un tableau vide (ou qu'aucun `security.ids_voter` n'a été configuré — défaut : un voter nul qui n'autorise jamais rien), l'export renvoie **zéro ligne** pour toute requête impliquant cette entité — jamais "pas de restriction". Une entité absente de `restricted_entities` n'est jamais filtrée.

⚠️ Piège YAML fréquent : `restricted_entities: ['App\Entity\Customer\Customer::class']` place le texte littéral `::class` dans la chaîne (YAML n'évalue pas la syntaxe PHP) — la comparaison stricte échoue silencieusement et la restriction ne s'applique jamais. Écrivez le FQCN nu : `['App\Entity\Customer\Customer']`.

#### Filtrage avancé par colonne (`filterable`)

Chaque colonne (`SmartExportColumn`) peut désormais être marquée `filterable` dans l'admin, avec une `filterDefaultValue` optionnelle. Le popup d'export affiche alors une ligne de filtre par colonne filtrable : un sélecteur d'opérateur (dépendant de l'`interpreter` de la colonne, voir `SmartExportFilterOperators`) et une ou deux valeurs. Les valeurs soumises sont persistées côté client dans un cookie `smart_export_filter_{uuid}` (30 jours), relues à la réouverture du popup — la `filterDefaultValue` ne s'applique que tant qu'aucun cookie n'existe.

Un filtre peut porter sur une relation jointe (pas seulement l'entité primaire) : la résolution du chemin réutilise exactement la même logique de jointure que les colonnes exportées, donc un filtre sur `contract.customer.name` fonctionne quel que soit l'endroit de l'export où `Customer` est atteint.

Migration Doctrine nécessaire (colonnes `filterable` et `filter_default_value` sur `smart_export_column`, valeurs par défaut littérales, pas de backfill) :

```sql
ALTER TABLE smart_export_column
  ADD filterable TINYINT DEFAULT 0 NOT NULL,
  ADD filter_default_value VARCHAR(255) DEFAULT NULL;
```

#### Popup d'export embarquable (`smart_export_popup()`) + colonnes/filtres configurables

Le popup d'export (auparavant une page autonome du bundle, avec sa propre mise en page Tailwind/Turbo) est maintenant un **fragment injectable dans n'importe quelle page hôte**, via une unique fonction Twig :

```twig
{{ smart_export_popup(engine.uuid) }}
{{ smart_export_popup(engine.uuid, 'Exporter les clients') }}
```

Cette fonction imprime un bouton déclencheur + une coquille de popup vide (aucun accès base de données) ; le contenu réel (colonnes, filtres, valeurs distinctes — tout ce qui nécessite la base) n'est chargé qu'au clic, via `fetch()` vers la route `odb_smart_export_admin_demo_export` existante. Le CSS/JS du popup (`public/css/popup.css`, `public/js/popup-app.js` + `public/js/vendor/stimulus.js` vendorisé) n'est imprimé qu'au **premier** appel de la fonction sur une même page — appeler `smart_export_popup()` une fois par ligne d'une liste ne duplique jamais les assets. Aucune configuration supplémentaire côté application hôte : `bin/console assets:install` (déjà nécessaire aujourd'hui) suffit.

Le popup lui-même est un wizard à 3 étapes (Filtres → Colonnes → Format), dont l'étape Filtres disparaît entièrement s'il n'y a aucun filtre à afficher. Arriver sur l'étape Format déclenche automatiquement la vérification du nombre de lignes (route `/admin/count/{uuid}`) — il n'y a plus de bouton "Vérifier" séparé.

Trois nouveaux réglages par colonne (`SmartExportColumn`), visibles dans l'admin d'édition d'un export à côté de `filterable` :

- **`columnDisplay`** (défaut `true`) — la colonne est-elle offerte comme champ exportable dans le panneau Colonnes ? Indépendant de `filterable` : une colonne peut servir uniquement de filtre sans jamais être exportée.
- **`selectedByDefault`** (défaut `false`) — si `columnDisplay=true`, la colonne démarre-t-elle cochée dans le panneau Colonnes ?
- **`filterDisplay`** (défaut `true`) — si `filterable=true`, le widget de filtre est-il affiché dans le panneau Filtres, ou appliqué silencieusement (avec `filterDefaultValue`) sans jamais être montré à l'utilisateur ?

Exemple concret — un export "clients actifs" qui filtre toujours sur `actif = oui`, sans jamais montrer ce filtre ni permettre d'exporter la colonne `actif` elle-même :

```php
$column->setFilterable(true);
$column->setFilterDefaultValue('1');
$column->setFilterDisplay(false);
$column->setColumnDisplay(false);
```

⚠️ Si plusieurs colonnes sont fusionnées via `cellGroupIndex` (rendu en une seule cellule à l'export), évitez de mélanger `columnDisplay=true` et `columnDisplay=false` au sein d'un même groupe : le ou les membres masqués disparaissent silencieusement de la cellule fusionnée plutôt que de bloquer tout le groupe.

Migration Doctrine nécessaire :

```sql
ALTER TABLE smart_export_column
  ADD column_display TINYINT DEFAULT 1 NOT NULL,
  ADD selected_by_default TINYINT DEFAULT 0 NOT NULL,
  ADD filter_display TINYINT DEFAULT 1 NOT NULL;
```

#### Simplification des labels de colonne et suppression de `columnGroupIndex`

`SmartExportColumn` n'a plus qu'un seul champ de libellé, `label` (auparavant deux : `choiceLabel`, montré dans le formulaire d'export, et `headerLabel`, utilisé comme entête du fichier généré — les deux étaient en pratique toujours identiques). `getChoiceLabel()`/`setChoiceLabel()` deviennent `getLabel()`/`setLabel()` ; `getHeaderLabel()`/`setHeaderLabel()` disparaissent — l'entête du fichier généré utilise désormais `label`.

Le champ `columnGroupIndex` (fusion de colonnes, distinct de `cellGroupIndex` qui fusionne des *cellules* et reste inchangé) est supprimé : son chemin de code dans `SmartExportChoice::parseChoices()` portait d'ailleurs un commentaire `// todo not working` — il n'a jamais fonctionné correctement et n'est utilisé nulle part côté admin.

#### `smart_export_popup()` : lien `<a>`, label HTML, classe personnalisée, `detailed` et `id`

Le déclencheur imprimé par `smart_export_popup()` est maintenant un `<a href="#">` (auparavant un `<button>`), pour s'intégrer naturellement dans n'importe quelle mise en page hôte (colonne d'actions d'un tableau, barre d'outils…). Trois nouvelles options :

```twig
{{ smart_export_popup(
    customer.uuid,
    '<i class="fa fa-download me-3"></i>Télécharger',
    {'class': 'btn btn-sm btn-success', 'detailed': true, 'id': customer.id}
) }}
```

- **Le label accepte du HTML** (rendu `|raw` — c'est du Twig écrit par le développeur appelant la fonction dans son propre template, jamais une saisie utilisateur), pour par exemple préfixer un texte d'une icône.
- **Le lien est volontairement sans style** : `popup.css` ne porte plus aucune règle pour `smart-export-trigger` (seule classe systématiquement présente sur le `<a>`, en tant que simple hook — JS, CSS hôte… — jamais pour imposer une apparence). C'est à l'appelant de styler le lien lui-même via **`class`** (ajoutée à côté de `smart-export-trigger`, jamais à sa place — rien dans le bundle ne vient donc jamais entrer en conflit avec des classes d'un framework CSS de l'app hôte comme Bootstrap).
- **`detailed`** (bool, défaut `true`) — l'étape Colonnes reste toujours affichée ; à `false`, chaque tuile de colonne montre uniquement son libellé, sans le nom technique (`classProperty`) affiché en dessous — un sélecteur plus simple, moins technique, pour un public non-administrateur.
- **`id`** (`int|string|array`) — restreint l'export à cet id, ou ce tableau d'ids, de l'entité primaire de l'export (ex. un seul client, ou les ids des articles d'un contrat). S'ajoute aux restrictions existantes sans jamais les contourner : si l'entité primaire est configurée dans `security.restricted_entities`, la restriction par ids autorisés (`AllowedIdsResolver`) continue de s'appliquer en plus — un appelant ne peut jamais exporter un id auquel il n'a pas accès simplement en le passant dans cette option.

Aucune migration Doctrine requise pour ce changement (uniquement des ajouts côté Twig/formulaire/requête).

#### Export / import JSON d'un export (page d'édition)

Le bouton **Exporter** de la page d'édition télécharge un snapshot JSON minimal (`header` + `columns`) d'un export — pensé pour être versionné, sauvegardé, ou rejoué sur une autre instance (ex. dev -> recette). Le bouton **Importer** de la liste (`/admin/import`) reprend un tel fichier :

```json
{
    "formatVersion": 1,
    "exportedAt": "2026-09-09T15:22:06+02:00",
    "header": {
        "uuid": "01a07620-7a5a-7f08-af0c-b75ffab2ce82",
        "code": "customer_active",
        "name": "Export clients actifs",
        "description": "...",
        "className": "App\\Entity\\Customer\\Customer",
        "enabled": true
    },
    "columns": [
        {"choicePosition": 0, "classProperty": "active", "label": "Actif", "cellGroupIndex": null,
         "interpreter": "boolean", "enabled": true, "columnDisplay": false, "selectedByDefault": false,
         "filterable": true, "filterDisplay": false, "filterDefaultValue": "true", "filterWidget": "auto"}
    ]
}
```

**L'import n'écrit jamais rien en un seul clic** — trois étapes toujours distinctes (`SmartExportEngineTransfer`) :
1. `parseImport()` — parsing/validation pures (plus une lecture, jamais une écriture : recherche d'un export existant portant le même `uuid`). Erreurs bloquantes (JSON invalide, `header`/`columns` manquant, `className`/`classProperty` absent) vs avertissements non bloquants (classe introuvable dans l'appli courante, `uuid` invalide et donc ignoré).
2. L'écran de confirmation affiche le résultat du parsing — jamais un accès direct en base — et réembarque le JSON dans un champ caché (`import_json`) pour l'étape suivante.
3. Seule la confirmation explicite (`odb_smart_export_admin_import_commit`) déclenche `commitImport()` : ré-analyse le **même** JSON (jamais une confiance aveugle dans ce qui a été confirmé) et, si toujours valide, persiste.

**Correspondance création/mise à jour** : uniquement par `uuid` (jamais par `code`). Si l'`uuid` du fichier correspond à un export déjà présent sur cette instance → **mise à jour** (son `uuid` ne bouge pas, ses colonnes sont intégralement remplacées par celles du fichier — un remplacement de snapshot, jamais une fusion/diff). Sinon → **création**, avec le **même** `uuid` que le fichier (`SmartExportEngine::setUuid()`, nouveau — n'existe que pour ce cas précis) si valide, ou un `uuid` généré sinon. Effet recherché : promouvoir un export de dev vers recette, puis le réimporter après modification, retombe systématiquement en mode mise à jour du même export — et tout code hôte référençant déjà `smart_export_popup(uuid)` continue de fonctionner après la promotion, sans avoir à traquer un nouvel uuid.

⚠️ Deux bugs latents corrigés à cette occasion dans `SmartExportColumn`/`SmartExportEngine` — jamais déclenchés par les parcours existants (édition d'exports déjà en base), mais systématiquement par la création d'entités entièrement neuves (import en mode création) :
- `SmartExportColumn::$filterable` n'avait aucun type PHP déclaré ; Doctrine ne pouvait donc pas en déduire le type de colonne et le traitait comme une chaîne, transformant silencieusement `false` en `''` (chaîne vide) — rejeté par la colonne SQL réellement entière. Corrigé en `private bool $filterable = false;`, aucune migration nécessaire (le schéma SQL était déjà correct, seul le mapping PHP était erroné).
- `SmartExportEngine::$createdAt`/`$updatedAt` sont des propriétés typées non-nullables sans valeur par défaut ; `updateDate()` (le listener `PrePersist`) suppose pourtant pouvoir lire `getCreatedAt() === null` avant le tout premier persist, ce qui lève une `Error` PHP ("must not be accessed before initialization") au lieu de renvoyer `null`. Corrigé en initialisant les deux dans le constructeur, au même endroit où `$uuid` l'est déjà.

Migration Doctrine nécessaire (`CHANGE`, pas `DROP`+`ADD`, pour préserver les valeurs existantes de `choice_label`) :

```sql
ALTER TABLE smart_export_column
  CHANGE choice_label label VARCHAR(128) DEFAULT NULL,
  DROP header_label,
  DROP column_group_index;
```
