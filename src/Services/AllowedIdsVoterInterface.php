<?php

namespace Odb\SmartExportBundle\Services;

/**
 * Implemented by the HOST application as a single service covering every FQCN
 * listed in this bundle's security.restricted_entities config (see
 * themis/config/packages/smart_export.yaml for a real example) — dispatch on
 * $className internally if more than one entity is restricted.
 *
 * Called ON DEMAND by AllowedIdsResolver, only on a cache miss (never eagerly,
 * never from a login/switch-user event): implementations should be reasonably
 * fast, but AllowedIdsResolver wraps every call in its own short-lived cache
 * (security.allowed_ids_ttl, default 10 min) regardless, so an implementation
 * that itself hits the database on every call is still fine.
 */
interface AllowedIdsVoterInterface
{
    /**
     * @return array<int, int|string> Every id of $className the CURRENTLY
     *                                 logged-in user is allowed to see. Empty
     *                                 means none — AllowedIdsResolver treats
     *                                 that as real "no access", never as
     *                                 "unrestricted" (fail closed).
     */
    public function getAllowedIds(string $className): array;
}
