<?php
namespace Odb\SmartExportBundle;

use \Symfony\Component\HttpKernel\Bundle\Bundle;

class OdbSmartExportBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}