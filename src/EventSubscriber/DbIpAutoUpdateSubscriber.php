<?php

namespace Wexample\SymfonyRemoteDbIp\EventSubscriber;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientException;
use Wexample\SymfonyRemoteDbIp\Service\DbIpDatabaseUpdater;

/**
 * Keeps the database of the month without a scheduler: once a day, after a
 * response has left, the file is downloaded again when missing or a month
 * old. The day is marked before downloading, so two workers do not both
 * fetch it; a failure waits for the next day, logged.
 */
class DbIpAutoUpdateSubscriber implements EventSubscriberInterface
{
    private const string CACHE_KEY = 'wexample_symfony_remote_db_ip.checked';

    public function __construct(
        private readonly DbIpDatabaseUpdater $updater,
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly bool $autoUpdate,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => ['onTerminate', -1024]];
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if (! $this->autoUpdate || ! $event->isMainRequest()) {
            return;
        }

        $checked = $this->cache->getItem(self::CACHE_KEY);

        if ($checked->isHit()) {
            return;
        }

        $this->cache->save($checked->set(true)->expiresAfter(86400));

        if (! $this->updater->isStale()) {
            return;
        }

        try {
            $this->updater->update();
        } catch (RuntimeException|HttpClientException $exception) {
            $this->logger->warning('The DB-IP database could not be updated: {message}', ['message' => $exception->getMessage()]);
        }
    }
}
