<?php

namespace Wexample\SymfonyRemoteDbIp\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_remote_db_ip');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('database')
                    ->info('Where the database is kept once downloaded (db-ip:update): outside the code, a release keeps it.')
                    ->defaultValue('%kernel.project_dir%/var/db-ip/dbip-country-lite.mmdb')
                ->end()
                ->booleanNode('auto_update')
                    ->info('Downloads the file again, once a day after a response, when missing or a month old: no scheduler needed. Off to run db-ip:update --if-stale from one.')
                    ->defaultTrue()
                ->end()
                ->scalarNode('download_url')
                    ->info('The monthly file, %s the month as YYYY-MM. DB-IP publishes it under CC BY 4.0: a page showing its countries credits "IP Geolocation by DB-IP".')
                    ->defaultValue('https://download.db-ip.com/free/dbip-country-lite-%s.mmdb.gz')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
