<?php

namespace RAPL\Bundle\RAPLBundle\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator;
use RAPL\RAPL\Mapping\Driver\YamlDriver as BaseYamlDriver;

/**
 * Class YamlDriver
 * 
 * @author Piotr Minkina <projekty@piotrminkina.pl>
 * @package RAPL\Bundle\RAPLBundle\Mapping\Driver
 */
class YamlDriver extends BaseYamlDriver
{
    /**
     * {@inheritDoc}
     */
    public function __construct($prefixes, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        $locator = new SymfonyFileLocator((array) $prefixes, $fileExtension);
        parent::__construct($locator, $fileExtension);
    }
}
