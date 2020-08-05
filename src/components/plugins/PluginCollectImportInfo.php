<?php
namespace extas\components\plugins;

use extas\components\packages\CrawlerExtas;
use extas\components\packages\PackageImport;
use extas\components\packages\PackageImportRepository;
use extas\components\THasIndex;
use extas\components\THasIO;
use extas\interfaces\packages\IPackageImport;
use extas\interfaces\stages\IStageInitialize;

/**
 * Class PluginCollectImportInfo
 *
 * @package extas\components\plugins
 * @author jeyroik <jeyroik@gmail.com>
 */
class PluginCollectImportInfo extends Plugin implements IStageInitialize
{
    use THasIO;
    use THasIndex;

    public const SECTION = 'import';

    /**
     * @param string $packageName
     * @param array $package
     * @throws \Exception
     */
    public function __invoke(string $packageName, array $package): void
    {
        $wd = $package[CrawlerExtas::FIELD__WORKING_DIRECTORY] ?? '';

        $root = $wd && (strpos($wd, 'vendor') === false);

        if ($root) {
            $this->saveImport($packageName, $package);
        }
    }

    /**
     * @param string $packageName
     * @param array $package
     * @return bool
     * @throws \Exception
     */
    protected function saveImport(string $packageName, array $package): bool
    {
        if (!isset($package[static::SECTION])) {
            return false;
        }

        $repo = new PackageImportRepository();

        /**
         * @var IPackageImport $existed
         */
        $existed = $repo->one([IPackageImport::FIELD__NAME => $packageName]);

        if ($existed) {
            $curHash = sha1(json_encode($package[static::SECTION]));
            $exHash = sha1(json_encode($existed->getImport()));

            if ($curHash != $exHash) {
                $existed->setImport($package[static::SECTION]);
                $repo->update($existed);
            }
        } else {
            $repo->create(new PackageImport([
                PackageImport::FIELD__NAME => $packageName,
                PackageImport::FIELD__IMPORT => $package[static::SECTION]
            ]));
        }

        return true;
    }
}
