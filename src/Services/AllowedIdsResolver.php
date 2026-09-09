<?php

namespace Odb\SmartExportBundle\Services;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Resolves, for the currently logged-in user, the list of ids they are
 * allowed to see on a given entity class — used by SmartExportQuery to
 * restrict an export wherever a restricted entity appears in its join graph
 * (as the export's primary entity, or reached through a relation), not just
 * when it happens to be the primary entity: e.g. restricting Customer must
 * still apply to an "Item -> Contract -> Customer" export just as much as a
 * "Customer -> Contract -> Item" one.
 *
 * Which entities are restricted is bundle configuration
 * (security.restricted_entities), not a per-SmartExportEngine setting — it's
 * a property of the entity's data-access rules, not of any one export.
 *
 * The bundle only READS from the cache: the host application is responsible
 * for writing the allowed ids under the exact key returned by cacheKeyFor()
 * (e.g. from a login listener), whenever/however it sees fit. There is no
 * interface to implement — cacheKeyFor() is the only contract, so the host
 * never has to know or reproduce the key format itself.
 */
class AllowedIdsResolver
{
    /**
     * @param array<string> $restrictedEntities FQCN of entities restricted to a per-user allowed-ids list.
     */
    public function __construct(
        private readonly CacheItemPoolInterface $allowedIdsCache,
        private readonly Security $security,
        private readonly array $restrictedEntities,
    ) {
    }

    /**
     * @param string $entityClass Fully-qualified class name to check (e.g. App\Entity\Customer\Customer::class).
     * @return array|null Null if $entityClass isn't configured as restricted — no filtering needed.
     *                     Otherwise the current user's allowed ids, possibly empty: fail closed, an
     *                     entity marked restricted with nothing cached for this user means no rows,
     *                     never "unrestricted".
     */
    public function getAllowedIdsIfRestricted(string $entityClass): ?array
    {
        if (!in_array($entityClass, $this->restrictedEntities, true)) {
            return null;
        }

        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        $key = self::cacheKeyFor($entityClass, $user->getUserIdentifier());
        $item = $this->allowedIdsCache->getItem($key);

        return $item->isHit() ? (array) $item->get() : [];
    }

    /**
     * The single source of truth for the cache key format. Host applications
     * must call this (never rebuild the key by hand) when writing the allowed
     * ids for a user, e.g.:
     *
     *   $key = AllowedIdsResolver::cacheKeyFor(Customer::class, $user->getUserIdentifier());
     *   $item = $cache->getItem($key);
     *   $item->set([12, 45, 78]);
     *   $cache->save($item);
     */
    public static function cacheKeyFor(string $entityClass, string $userIdentifier): string
    {
        $hash = hash('xxh128', sprintf('SmartExportAllowedIds|%s|%s', $entityClass, $userIdentifier));
        return 'smart_export_allowed_ids_' . $hash;
    }
}
