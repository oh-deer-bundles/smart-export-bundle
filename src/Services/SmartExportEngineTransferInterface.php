<?php

namespace Odb\SmartExportBundle\Services;

use Odb\SmartExportBundle\Entity\SmartExportEngine;
use Odb\SmartExportBundle\Model\SmartExportEngineImportPreview;

interface SmartExportEngineTransferInterface
{
    /**
     * @return array{formatVersion: int, exportedAt: string, header: array, columns: array<int, array>}
     */
    public function export(SmartExportEngine $engine): array;

    /**
     * Pure parsing/validation — the only database access is the read-only
     * lookup for an existing engine to update (matched by uuid). Never
     * writes anything; see commitImport() for that.
     */
    public function parseImport(string $json): SmartExportEngineImportPreview;

    /**
     * Persists $preview (create or update, per isUpdate()) and flushes.
     * Never call this on a preview that isn't isValid().
     */
    public function commitImport(SmartExportEngineImportPreview $preview): SmartExportEngine;
}
