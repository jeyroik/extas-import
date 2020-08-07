<?php
namespace extas\components\plugins;

use extas\components\exceptions\MissedOrUnknown;
use extas\components\packages\CrawlerExtas;
use extas\components\packages\Installer;
use extas\components\packages\PackageImport;
use extas\components\THasIO;
use extas\interfaces\packages\IPackageImport;
use extas\interfaces\stages\IStageInstall;
use extas\interfaces\stages\IStagePackageExportBuild;

/**
 * Class PluginImport
 *
 * @package extas\components\plugins
 * @author jeyroik <jeyroik@gmail.com>
 */
class PluginImport extends Plugin implements IStageInstall
{
    use THasIO;

    public const SECTION__IMPORT = 'import';
    public const SECTION__EXPORT = 'export';

    /**
     * @param array $packages
     * @param array $generatedData
     * @throws \Exception
     */
    public function __invoke(array &$packages, array &$generatedData): void
    {
        $packages = $this->preparePackages($packages);

        $installer = new Installer($this->getIO());
        $installer->installPackages($packages);

        $generatedData = $installer->getGeneratedData();
    }

    /**
     * @param array $packages
     * @return array
     * @throws \Exception
     */
    protected function preparePackages(array $packages): array
    {
        $import = $this->getImport($packages);

        if (!$import) {
            return [];
        }

        $byName = array_column($packages, null, 'name');
        $export = [];
        $from = $import->getFrom();
        $onMiss = $import->getParameterValue($import::PARAM__ON_MISS_PACKAGE, $import::ON_MISS__CONTINUE);

        foreach ($from as $exportPackageName => $importList) {
            if ($this->canBeExported($byName, $exportPackageName)) {
                $export[$exportPackageName] = $this->constructExport(
                    $exportPackageName,
                    $byName[$exportPackageName][static::SECTION__EXPORT],
                    $importList,
                    $import
                );
            } else {
                if ($onMiss == $import::ON_MISS__CONTINUE) {
                    continue;
                }
                throw new MissedOrUnknown(
                    'package "' . $exportPackageName . '" for export or section "' . static::SECTION__EXPORT . '"'
                );
            }
        }

        return $export;
    }

    /**
     * @param array $byName
     * @param string $name
     * @return bool
     */
    protected function canBeExported(array $byName, string $name)
    {
        return isset($byName[$name]) && isset($byName[$name][static::SECTION__EXPORT]);
    }

    /**
     * @param string $name
     * @param array $exportPackage
     * @param array $importList
     * @param IPackageImport $import
     * @return array
     * @throws MissedOrUnknown
     */
    protected function constructExport(
        string $name,
        array $exportPackage,
        array $importList,
        IPackageImport $import
    ): array
    {
        $export = ['name' => $name];
        $onMiss = $import->getParameterValue($import::PARAM__ON_MISS_SECTION, $import::ON_MISS__CONTINUE);

        foreach ($importList as $sectionName => $sectionList) {
            if ($this->isContinue($exportPackage, $sectionName, $onMiss, $export)) {
                continue;
            }

            $this->constructCustomExport($export, $exportPackage, $sectionName, $sectionList);
        }

        return $export;
    }

    protected function isContinue(array $exportPackage, string $sectionName, string $onMiss, array $export): bool
    {
        if (!isset($exportPackage[$sectionName])) {
            if ($onMiss == IPackageImport::ON_MISS__CONTINUE) {
                return true;
            }
            throw new MissedOrUnknown('section "' . $sectionName . '" for export in the "' . $export['name'] . '"');
        }

        return false;
    }

    /**
     * @param array $export
     * @param array $exportPackage
     * @param string $sectionName
     * @param $sectionList
     * @return array
     */
    protected function constructCustomExport(
        array &$export,
        array $exportPackage,
        string $sectionName,
        $sectionList
    ): array
    {
        foreach ($this->getPluginsByStage(IStagePackageExportBuild::NAME . '.' . $sectionName) as $plugin) {
            /**
             * @var IStagePackageExportBuild $plugin
             */
            if ($plugin($export, $exportPackage, $sectionName, $sectionList)) {
                return $export;
            }
        }

        foreach ($this->getPluginsByStage(IStagePackageExportBuild::NAME) as $plugin) {
            if ($plugin($export, $exportPackage, $sectionName, $sectionList)) {
                return $export;
            }
        }

        return $export;
    }

    /**
     * @param array $packages
     * @return IPackageImport|null
     */
    protected function getImport(array $packages): ?IPackageImport
    {
        $byName = array_column($packages, null, CrawlerExtas::FIELD__WORKING_DIRECTORY);

        foreach ($byName as $wd => $config) {
            if ((strpos($wd, 'vendor') === false) && isset($config[static::SECTION__IMPORT])) {
                return new PackageImport($config[static::SECTION__IMPORT]);
            }
        }

        return null;
    }
}
