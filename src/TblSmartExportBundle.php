<?php
namespace Tbl\SmartExportBundle;

use \Symfony\Component\HttpKernel\Bundle\Bundle;

class TblSmartExportBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}