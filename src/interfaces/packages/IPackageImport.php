<?php
namespace extas\interfaces\packages;

use extas\interfaces\IHasName;
use extas\interfaces\IItem;

/**
 * Interface IPackageImport
 *
 * @package extas\interfaces\packages
 * @author jeyroik <jeyroik@gmail.com>
 */
interface IPackageImport extends IItem, IHasName
{
    public const SUBJECT = 'extas.package.import';

    public const FIELD__IMPORT = 'import';

    /**
     * @return array
     */
    public function getImport(): array;

    /**
     * @param array $import
     * @return IPackageImport
     */
    public function setImport(array $import): IPackageImport;
}
