<?php
namespace extas\components\packages;

use extas\components\Item;
use extas\components\THasName;
use extas\interfaces\packages\IPackageImport;

/**
 * Class PackageImport
 *
 * @package extas\components\packages
 * @author jeyroik <jeyroik@gmail.com>
 */
class PackageImport extends Item implements IPackageImport
{
    use THasName;

    /**
     * @return array
     */
    public function getImport(): array
    {
        return $this->config[static::FIELD__IMPORT] ?? [];
    }

    /**
     * @param array $import
     * @return $this|IPackageImport
     */
    public function setImport(array $import): IPackageImport
    {
        $this->config[static::FIELD__IMPORT] = $import;

        return $this;
    }

    /**
     * @return string
     */
    protected function getSubjectForExtension(): string
    {
        return static::SUBJECT;
    }
}
