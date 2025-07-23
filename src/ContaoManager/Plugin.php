<?php

declare(strict_types=1);

namespace shopware_bundle\ShopwareBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\CoreBundle\ContaoCoreBundle;
use shopware_bundle\ShopwareBundle\ShopwareBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(ShopwareBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}