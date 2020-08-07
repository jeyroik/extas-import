<?php
namespace extas\components\plugins\export;

use extas\components\plugins\Plugin;
use extas\interfaces\stages\IStagePackageExportBuild;

/**
 * Class PluginExportByWildcard
 *
 * @package extas\components\plugins\export
 * @author jeyroik <jeyroik@gmail.com>
 */
class PluginExportByWildcard extends Plugin implements IStagePackageExportBuild
{
    /**
     * @param array $export
     * @param array $exportPackage
     * @param string $sectionName
     * @param $sectionList
     * @return bool
     */
    public function __invoke(array &$export, array $exportPackage, string $sectionName, $sectionList): bool
    {
        if (is_string($sectionList) && ($sectionList == '*')) {
            $export[$sectionName] = $exportPackage[$sectionName];
            return true;
        }

        return false;
    }
}
