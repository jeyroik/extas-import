<?php
namespace extas\components\packages;

use extas\components\Item;
use extas\components\samples\parameters\THasSampleParameters;
use extas\interfaces\packages\IPackageImport;

/**
 * Class PackageImport
 *
 * @package extas\components\packages
 * @author jeyroik <jeyroik@gmail.com>
 */
class PackageImport extends Item implements IPackageImport
{
    use THasSampleParameters;

    /**
     * @return array
     */
    public function getFrom(): array
    {
        return $this->config[static::FIELD__FROM] ?? [];
    }

    /**
     * @return string
     */
    protected function getSubjectForExtension(): string
    {
        return static::SUBJECT;
    }
}
