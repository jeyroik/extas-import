<?php
namespace extas\interfaces\stages;

/**
 * Interface IStagePackageExportBuild
 *
 * @package extas\interfaces\stages
 * @author jeyroik <jeyroik@gmail.com>
 */
interface IStagePackageExportBuild
{
    public const NAME = 'extas.package.export.build';

    /**
     * Is export finished is returned.
     *
     * @param array $export
     * @param string $sectionName
     * @param array $exportPackage
     * @param $sectionList
     * @return bool
     */
    public function __invoke(array &$export, array $exportPackage, string $sectionName, $sectionList): bool;
}
