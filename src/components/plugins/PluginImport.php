<?php
namespace extas\components\plugins;

use extas\components\packages\CrawlerExtas;
use extas\components\packages\Installer;
use extas\components\packages\PackageImportRepository;
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
        $main = $this->getMainName($packages);

        if (!$main) {
            return [];
        }

        $import = $this->getImport($main);

        if (!$import) {
            return [];
        }

        $byName = array_column($packages, null, 'name');
        $importConfig = $import->getImport();

        $export = [];

        foreach ($importConfig as $exportPackageName => $importList) {
            if (!isset($byName[$exportPackageName]) || !isset($byName[$exportPackageName]['export'])) {
                continue;
            }

            $export[$exportPackageName] = $this->constructExport($byName[$exportPackageName]['export'], $importList);
        }

        return $export;
    }

    /**
     * @param array $exportPackage
     * @param array $importList
     * @return array
     */
    protected function constructExport(array $exportPackage, array $importList): array
    {
        $export = [
            'name' => $exportPackage['name']
        ];

        foreach ($importList as $sectionName => $sectionList) {
            if (!isset($exportPackage[$sectionName])) {
                continue;
            }

            if (is_string($sectionList) && ($sectionList == '*')) {
                $export[$sectionName] = $exportPackage[$sectionName];
            }

            $this->constructCustomExport($export, $sectionName, $sectionList);
        }

        return $export;
    }

    /**
     * @param array $export
     * @param array $exportPackage
     * @param string $sectionName
     * @param $sectionList
     * @return array
     */
    protected function constructCustomExport(
        array $export,
        array $exportPackage,
        string $sectionName,
        $sectionList
    ): array
    {
        foreach ($this->getPluginsByStage(IStagePackageExportBuild::NAME . '.' . $sectionName) as $plugin) {
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
     * @param string $name
     * @return IPackageImport
     * @throws \Exception
     */
    protected function getImport(string $name): IPackageImport
    {
        $repo = new PackageImportRepository();

        /**
         * @var IPackageImport $import
         */
        $import = $repo->one([IPackageImport::FIELD__NAME => $name]);

        return $import;
    }

    /**
     * @param array $packages
     * @return string
     */
    protected function getMainName(array $packages): string
    {
        $byName = array_column($packages, CrawlerExtas::FIELD__WORKING_DIRECTORY, 'name');

        $main = '';

        foreach ($byName as $name => $wd) {
            if (strpos($wd, 'vendor') === false) {
                $main = $name;
                break;
            }
        }

        return $main;
    }
}
