<?php

namespace Wexample\SymfonyRemoteDbIp\Service;

use DateTimeImmutable;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Downloads the month's database, or the previous month's while the new one
 * is not out yet, and puts it in place at once: a locator reading the old
 * file never reads half of the new one.
 */
class DbIpDatabaseUpdater
{
    /** A month and a few days: the file is published monthly. */
    public const int MAX_AGE_DAYS = 32;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $database,
        private readonly string $downloadUrl,
    ) {
    }

    public function isStale(): bool
    {
        clearstatcache(true, $this->database);

        return ! is_file($this->database)
            || filemtime($this->database) < time() - self::MAX_AGE_DAYS * 86400;
    }

    /**
     * @return string the month of the file put in place, YYYY-MM
     * @throws RuntimeException when neither month can be downloaded
     */
    public function update(): string
    {
        $now = new DateTimeImmutable('first day of this month');

        foreach ([$now, $now->modify('-1 month')] as $month) {
            $response = $this->httpClient->request('GET', sprintf($this->downloadUrl, $month->format('Y-m')));

            if (200 !== $response->getStatusCode()) {
                continue;
            }

            $content = gzdecode($response->getContent());

            if (false === $content || '' === $content) {
                throw new RuntimeException('The DB-IP download is not a gzip file.');
            }

            $directory = dirname($this->database);

            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException(sprintf('The directory "%s" cannot be created.', $directory));
            }

            $temporary = $this->database . '.part';
            file_put_contents($temporary, $content);
            rename($temporary, $this->database);

            return $month->format('Y-m');
        }

        throw new RuntimeException('No DB-IP database could be downloaded for this month or the previous one.');
    }
}
