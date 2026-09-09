<?php

namespace Odb\SmartExportBundle\Services;

/**
 * Default `security.ids_voter` when the host application hasn't configured a
 * real one: grants no access to any restricted entity. Only relevant once
 * security.restricted_entities is non-empty — at that point the host MUST
 * override `security.ids_voter` with a real implementation, or every export
 * touching a restricted entity returns zero rows for every user.
 */
class NullAllowedIdsVoter implements AllowedIdsVoterInterface
{
    public function getAllowedIds(string $className): array
    {
        return [];
    }
}
