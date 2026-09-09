# Smart Export Bundle

Configurable, on-the-fly data export for Symfony applications. An admin UI lets you define **export engines** (one per entity you want exportable, e.g. Customer) and their **columns** (including columns reached through relations, e.g. `addresses.city`), with per-column filtering, default values, and visibility rules. The result is embeddable in any host page via a single Twig function, `smart_export_popup()`, which lazily loads its own assets and content only when a user actually opens it.

This README covers installation, configuration, integrating the popup into your own Twig templates, and a walkthrough for building your first export. Keep it open during setup — it's written to not skip steps.

## Requirements

- PHP >= 8.2, Symfony 7.4 (`config`, `dependency-injection`, `form`, `http-foundation`, `orm-pack`, `security-bundle`, `string`, `validator`)
- Doctrine ORM
- `phpoffice/phpspreadsheet` (for the Excel export format and the data-structure reference file)

## Installation

### 1. Require the bundle

```console
$ composer require oh-deer-bundles/smart-export-bundle
```

If you're consuming it as a local path repository (as this monorepo does), add it to your app's `composer.json` first:

```json
{
    "repositories": [
        {"type": "path", "url": "../bundles/smart-export"}
    ],
    "require": {
        "oh-deer-bundles/smart-export-bundle": "dev-sf74"
    }
}
```

### 2. Register the bundle

`config/bundles.php`:

```php
return [
    // ...
    Odb\SmartExportBundle\OdbSmartExportBundle::class => ['all' => true],
];
```

### 3. Map the bundle's entities

`config/packages/smart_export.yaml`:

```yaml
doctrine:
    orm:
        mappings:
            OdbSmartExportBundle:
                type: attribute
                is_bundle: true
                prefix: 'Odb\SmartExportBundle\Entity'
                alias: OdbSmartExportBundle
```

### 4. Import the routes

`config/routes/smart_export.yaml`:

```yaml
smart_export:
    resource: '@OdbSmartExportBundle/config/routes.yaml'
    prefix: /smart-export
```

All routes are prefixed accordingly — with the example above, the admin index is `/smart-export/admin/`.

| Route name | Path | Method | Purpose |
|---|---|---|---|
| `odb_smart_export_admin_index` | `/admin/` | GET | List engines, create one |
| `odb_smart_export_admin_edit` | `/admin/edit/{uuid}` | GET/POST | Edit an engine's header + columns |
| `odb_smart_export_admin_toggle` | `/admin/toggle/{uuid}` | GET | Enable/disable an engine |
| `odb_smart_export_admin_remove` | `/admin/remove/{uuid}` | GET | Delete an engine |
| `odb_smart_export_admin_data_structure` | `/admin/data-structure/{token}` | GET | Download an Excel reference of every entity/property/association in your app |
| `odb_smart_export_admin_demo_export` | `/admin/demo-export/{uuid}` | GET/POST | Renders (and, on submit, generates) the export popup — also what `smart_export_popup()` calls |
| `odb_smart_export_admin_count` | `/admin/count/{uuid}` | POST | Row-count check used by the popup's Format step |
| `odb_smart_export_admin_export` | `/admin/export/{uuid}` | GET | Download one engine as a JSON snapshot |
| `odb_smart_export_admin_import` | `/admin/import` | GET/POST | Upload a JSON snapshot, preview it |
| `odb_smart_export_admin_import_commit` | `/admin/import/commit` | POST | Confirm and persist an import |

**Secure the admin routes yourself** — this bundle has no opinion on authentication/authorization. Restrict at least everything under your prefix in your firewall's `access_control`, e.g.:

```yaml
security:
    access_control:
        - { path: ^/smart-export/admin, roles: ROLE_ADMIN }
```

### 5. Run the migration

The bundle ships entities, not migrations — generate one in your app the normal way:

```console
$ php bin/console doctrine:migrations:diff
$ php bin/console doctrine:migrations:migrate
```

## Configuration reference

All keys are optional; shown values are the defaults.

```yaml
odb_smart_export:
    max_rows: 20000                    # generation is blocked past this row count
    security:
        allowed_ids_cache_pool: cache.app  # must implement Symfony\Contracts\Cache\CacheInterface (get-or-compute)
        allowed_ids_ttl: 600                # seconds a user's allowed-ids list is cached (see "Restricting rows per user" below)
        ids_voter: Odb\SmartExportBundle\Services\NullAllowedIdsVoter  # grants no access — override once restricted_entities is non-empty
        restricted_entities: []            # FQCN of entities restricted to a per-user allowed-ids list
```

## Creating your first export

### 1. Look up your data structure

From the admin index (`/smart-export/admin/`), click **Download data structure**. This generates an Excel sheet listing every Doctrine entity in your app, its properties and their types, and its associations (target entity, relation type, inverse property). Use it to pick your **pyramid-tip entity** — the one export rows are built from — and the path to any related data you want to include.

For example, exporting customers with their address's city: the tip is `Customer`, and the sheet tells you `Customer` has an association to `Address` (property `addresses`), which itself has a `city` property.

### 2. Create the engine

Click **New Export** and fill in:

- **Name** / **Code** / **Description** — free text, for your own reference.
- **Related entity** — pick your pyramid-tip entity from the dropdown (populated from every Doctrine entity in your app, by short class name — `Customer`, not the FQCN `App\Entity\Customer\Customer`).

Saving redirects you to the edit page.

### 3. Add columns

Each column maps to one exportable field. Click **Add column** and set:

| Field | Meaning |
|---|---|
| Property | Doctrine property path off the tip entity. A direct property: `name`. Through a relation: `addresses.city` (dot-separated, any depth). |
| Label | Column header in the generated file, and the text shown in the popup's column picker. |
| Group Cell | Optional shared key: columns sharing the same value are offered as a single pickable chip in the popup's column picker, and their values concatenated into one exported cell — instead of separate choices/columns. |
| Interpreter | How the raw value is formatted (`string`, `boolean`, `date`, `integer`, `float`, `euro`…). |
| Column display | Whether this column is offered at all in the popup's column picker. Uncheck for a column that should only ever be used as a silent filter. |
| Selected by default | Whether the column starts pre-checked in the picker. |
| Filterable | Whether users can filter on this column. |
| Filter display | Whether the filter is shown in the popup's Filtres step, or applied silently via **Default filter value** without ever being visible (e.g. an "active customers only" export that always filters `active = true` and never shows that filter or the `active` column itself). |
| Filter widget | `Auto` (text/number/date field matched to the interpreter), `Select` (multi-select from the column's distinct real values), or `Single select` (same, pick one). |
| Default filter value | Pre-fills the filter until the user changes it (or permanently, if Filter display is off). |

Reorder columns with the ↑/↓ arrows — this is also the export's column order. Save when done.

### 4. Try it

Click **Demo export** (the eye icon) on the index. The popup opens with your Filtres → Colonnes → Format wizard exactly as an end user would see it, including the row-count check before generation. Once you're happy with it, the edit page's **Voir le code** button gives you a ready-to-paste Twig snippet, real uuid included, for embedding it in your own app (see below).

## Integrating into your app (Twig)

Render a trigger anywhere in your own templates:

```twig
{{ smart_export_popup(engine.uuid) }}
```

This prints a plain, **unstyled** link plus a lazy popup shell — no CSS/JS is loaded until the first call on the page, and the popup's own content (columns, filters, distinct values) is fetched only when a user clicks the trigger.

### Options

`smart_export_popup(uuid, label = null, options = [])`

| Option | Type | Default | Effect |
|---|---|---|---|
| `label` (2nd arg) | string | translated "Export" | The link's content — accepts HTML (e.g. an icon), rendered as-is since it's authored by you, never end-user input. |
| `class` | string | *(none)* | CSS classes added to the trigger link, alongside its permanent `smart-export-trigger` hook class. The bundle ships no styling of its own, so this is how you make it look like anything — a Bootstrap button, a plain link, an icon button. |
| `detailed` | bool | `true` | Whether each column tile in the Colonnes step additionally shows its technical property path under the label, or just the label — set `false` for a simpler, non-technical picker. |
| `id` | int, string, or array | *(none)* | Restricts the export to this id (or these ids) of the tip entity — e.g. one customer's export button on their own page. Composes with, never bypasses, `security.restricted_entities` below. |

### Example: a Bootstrap-styled download button

```twig
{{ smart_export_popup(
    customer.uuid,
    '<i class="fa fa-download me-1"></i>Export',
    {'class': 'btn btn-sm btn-success', 'detailed': false, 'id': customer.id}
) }}
```

## Backing up and promoting exports between environments

On an engine's edit page, **Exporter** downloads a minimal JSON snapshot (header + columns). **Importer** (on the index page) reads one back in: it's always parse → preview → explicit confirm, never a one-click write. Re-importing a file whose `uuid` already exists on the target updates that engine in place (its columns are fully replaced, not merged); otherwise a new engine is created, keeping the file's own `uuid` when possible — so promoting an export from one environment to another, then re-importing it later after changes, keeps working with the same `uuid` your Twig code already references.

## Restricting rows per user

If some entity in your export graph needs per-user row-level access control (e.g. only the customers a given user is allowed to see), list it under `security.restricted_entities` and implement `AllowedIdsVoterInterface`:

```php
namespace App\Services\Security;

use App\Entity\Customer\Customer;
use Odb\SmartExportBundle\Services\AllowedIdsVoterInterface;

class SmartExportVoter implements AllowedIdsVoterInterface
{
    public function getAllowedIds(string $className): array
    {
        // $className is the FQCN, exactly as listed in restricted_entities below.
        // Return every id of that entity the CURRENT user may see; [] means none.
        return match ($className) {
            Customer::class => $this->accessManager->getAllowedCustomerIds(),
            default => [],
        };
    }
}
```

```yaml
odb_smart_export:
    security:
        restricted_entities: ['App\Entity\Customer\Customer']
        ids_voter: App\Services\Security\SmartExportVoter
```

`AllowedIdsResolver` calls your voter on demand — never via a login event — and caches its result per user for `security.allowed_ids_ttl` seconds. The restriction applies wherever the entity appears in an export's join graph, not just when it's the tip entity, and composes with (never bypasses) the `id` Twig option above. Without a configured `ids_voter`, a restricted entity grants **no** access to anyone (fail closed) — this is deliberate, not a bug: set `ids_voter` as soon as you add anything to `restricted_entities`.
