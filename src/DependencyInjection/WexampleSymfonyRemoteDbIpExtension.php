<?php

namespace Wexample\SymfonyRemoteDbIp\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyGeo\Interface\IpLocatorInterface;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;
use Wexample\SymfonyRemoteDbIp\Service\DbIpCountryLocator;

class WexampleSymfonyRemoteDbIpExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('wexample_symfony_remote_db_ip.database', $config['database']);
        $container->setParameter('wexample_symfony_remote_db_ip.download_url', $config['download_url']);
        $container->setParameter('wexample_symfony_remote_db_ip.auto_update', $config['auto_update']);

        $this->loadConfig(
            __DIR__,
            $container
        );

        $container->setAlias(IpLocatorInterface::class, DbIpCountryLocator::class);
    }
}
