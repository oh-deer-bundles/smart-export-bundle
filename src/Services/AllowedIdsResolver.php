<?php

namespace Odb\SmartExportBundle\Services;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Resolves, for the currently logged-in user, the list of ids they are
 * allowed to see on a given entity class — used by SmartExportQuery to
 * restrict an export wherever a restricted entity appears in its join graph
 * (as the export's primary entity, or reached through a relation), not just
 * when it happens to be the primary entity.
 *
 * Which entities are restricted is bundle configuration
 * (security.restricted_entities), not a per-SmartExportEngine setting — it's
 * a property of the entity's data-access rules, not of any one export.
 *
 * Entirely bundle-owned and pull-based: on a cache miss, getAllowedIdsIfRestricted()
 * calls the host-provided AllowedIdsVoterInterface::getAllowedIds() itself, on
 * demand, and caches the result for security.allowed_ids_ttl seconds (default
 * 10 min). There is no push side and nothing to hook into a login/switch-user
 * event — a host that forgets to wire such an event (or whose authentication
 * path never fires one, e.g. a "remember me" cookie, an API token, some SSO
 * flows) previously meant an empty/missing cache entry, which — since it
 * fails closed — silently locked that user out of every restricted-entity
 * export. Calling the voter on demand removes that failure mode entirely.
 */
class AllowedIdsResolver
{
    /**
     * @param array<string> $restrictedEntities FQCN of entities restricted to a per-user allowed-ids list.
     */
    public function __construct(
        private readonly CacheInterface $allowedIdsCache,
        private readonly Security $security,
        private readonly AllowedIdsVoterInterface $voter,
        private readonly array $restrictedEntities,
        private readonly int $ttl = 600,
    ) {
    }

    /**
     * @param string $entityClass Fully-qualified class name to check (e.g. App\Entity\Customer\Customer::class).
     * @return array|null Null if $entityClass isn't configured as restricted — no filtering needed.
     *                     Otherwise the current user's allowed ids, possibly empty: fail closed, the
     *                     voter returning nothing means no rows, never "unrestricted".
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
        $voter = $this->voter;
        $ttl = $this->ttl;

        return $this->allowedIdsCache->get($key, static function (ItemInterface $item) use ($entityClass, $voter, $ttl) {
            $item->expiresAfter($ttl);
            return $voter->getAllowedIds($entityClass);
        });
    }

    /**
     * Exposed so a host can proactively invalidate one user's cached ids right
     * after a permission change (e.g. from an admin action), instead of
     * waiting up to security.allowed_ids_ttl for it to apply — entirely
     * optional, correctness never depends on it being called.
     */
    public function invalidate(string $entityClass, string $userIdentifier): void
    {
        $this->allowedIdsCache->delete(self::cacheKeyFor($entityClass, $userIdentifier));
    }

    /**
     * The single source of truth for the cache key format.
     */
    public static function cacheKeyFor(string $entityClass, string $userIdentifier): string
    {
        $hash = hash('xxh128', sprintf('SmartExportAllowedIds|%s|%s', $entityClass, $userIdentifier));
        return 'smart_export_allowed_ids_' . $hash;
    }
}
