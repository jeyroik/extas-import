<?php
namespace extas\interfaces\packages;

use extas\interfaces\IItem;
use extas\interfaces\samples\parameters\IHasSampleParameters;

/**
 * Interface IPackageImport
 *
 * @package extas\interfaces\packages
 * @author jeyroik <jeyroik@gmail.com>
 */
interface IPackageImport extends IItem, IHasSampleParameters
{
    public const SUBJECT = 'extas.package.import';

    public const FIELD__FROM = 'from';
    public const PARAM__ON_MISS_PACKAGE = 'on_miss_package';
    public const PARAM__ON_MISS_SECTION = 'on_miss_section';
    public const ON_MISS__THROW_AN_ERROR = 'throw';
    public const ON_MISS__CONTINUE = 'continue';

    /**
     * @return array
     */
    public function getFrom(): array;
}
