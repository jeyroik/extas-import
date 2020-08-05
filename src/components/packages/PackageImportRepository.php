<?php
namespace extas\components\packages;

use extas\components\repositories\Repository;

/**
 * Class PackageImportRepository
 *
 * @package extas\components\packages
 * @author jeyroik <jeyroik@gmail.com>
 */
class PackageImportRepository extends Repository
{
    protected string $name = 'packages_imports';
    protected string $scope = 'extas';
    protected string $pk = PackageImport::FIELD__NAME;
    protected string $itemClass = PackageImport::class;
}
