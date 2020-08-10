<?php
namespace extas\components\plugins\export;

use extas\components\plugins\Plugin;
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
        if (is_string($sectionList)) {
            return $this->setSingle($export, $exportPackage[$sectionName], $sectionName, $sectionList);
        }

        return $this->setMultiple($export, $exportPackage[$sectionName], $sectionName, $sectionList);
    }

    /**
     * @param array $export
     * @param array $section
     * @param string $sectionName
     * @param array $items
     * @return bool
     */
    protected function setMultiple(array &$export, array $section, string $sectionName, array $items): bool
    {
        $has = false;

        foreach ($items as $field) {
            $added = $this->setSingle($export, $section, $sectionName, $field);
            if ($added) {
                $has = true;
            }
        }

        return $has;
    }

    /**
     * @param array $export
     * @param array $section
     * @param string $sectionName
     * @param string $name
     * @return bool
     */
    protected function setSingle(array &$export, array $section, string $sectionName, string $name): bool
    {
        $has = false;
        $export[$sectionName] = $export[$sectionName] ?? [];

        foreach ($section as $item) {
            $current = $item[$this->getFieldName()] ?? '';
            if ($current == $name) {
                $export[$sectionName][] = $item;
                $has = true;
            }
        }

        return $has;
    }

    /**
     * @return string
     */
    protected function getFieldName(): string
    {
        return $this->getParameterValue(static::FIELD__FIELD_NAME, IHasClass::FIELD__CLASS);
    }
}
