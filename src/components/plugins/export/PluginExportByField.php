<?php
namespace extas\components\plugins;

use extas\interfaces\IHasClass;
use extas\interfaces\stages\IStagePackageExportBuild;

/**
 * Class PluginExportByClass
 *
 * @package extas\components\plugins
 * @author jeyroik <jeyroik@gmail.com>
 */
class PluginExportByField extends Plugin implements IStagePackageExportBuild
{
    public const FIELD__FIELD_NAME = 'field';

    /**
     * @param array $export
     * @param array $exportPackage
     * @param string $sectionName
     * @param $sectionList
     * @return bool
     */
    public function __invoke(array &$export, array $exportPackage, string $sectionName, $sectionList): bool
    {
        $byField = array_column($exportPackage[$sectionName], null, $this->getFieldName());

        if (is_string($sectionList)) {
            if (isset($byField[$sectionList])) {
                $export[$sectionName] = [$byField[$sectionList]];

                return true;
            }
        } elseif (is_array($sectionList)) {
            $itemsForExport = [];
            foreach ($sectionList as $field) {
                $itemsForExport[] = $byField[$field];
            }
            $export[$sectionName] = $itemsForExport;

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getFieldName(): string
    {
        return $this->config[static::FIELD__FIELD_NAME] ?? IHasClass::FIELD__CLASS;
    }
}
