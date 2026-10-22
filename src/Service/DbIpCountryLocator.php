<?php

namespace Wexample\SymfonyRemoteDbIp\Service;

use MaxMind\Db\Reader;
use Wexample\SymfonyGeo\Interface\IpLocatorInterface;
use Wexample\SymfonyHelpers\Attribute\WorkerSafe;

/**
 * The country of an address, read from the DB-IP file on the machine: no
 * request leaves it, no address is kept. Without the file — not downloaded
 * yet —, nothing is located and nothing fails.
 */
class DbIpCountryLocator implements IpLocatorInterface
{
    #[WorkerSafe('The open database file, the same for every request; opened again when db-ip:update replaced it.')]
    private ?Reader $reader = null;

    #[WorkerSafe('When the open file was written, to see it replaced.')]
    private ?int $readerTime = null;

    public function __construct(
        private readonly string $database,
    ) {
    }

    public function locateCountry(string $ip): ?string
    {
        if (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $record = $this->getReader()?->get($ip);
        $code = is_array($record) ? ($record['country']['iso_code'] ?? null) : null;

        return is_string($code) ? strtoupper($code) : null;
    }

    private function getReader(): ?Reader
    {
        clearstatcache(true, $this->database);
        $time = is_file($this->database) ? filemtime($this->database) : null;

        if (null === $time) {
            return null;
        }

        if ($time !== $this->readerTime) {
            $this->reader?->close();
            $this->reader = new Reader($this->database);
            $this->readerTime = $time;
        }

        return $this->reader;
    }
}
