<?php

namespace Wexample\SymfonyRemoteDbIp\Service;

use DateTimeImmutable;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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
     * @throws RuntimeException when neither month can be downloaded, or the file cannot be written
     */
    public function update(): string
    {
        $now = new DateTimeImmutable('first day of this month');

        foreach ([$now, $now->modify('-1 month')] as $month) {
            $response = $this->httpClient->request('GET', sprintf($this->downloadUrl, $month->format('Y-m')));

            if (200 !== $response->getStatusCode()) {
                continue;
            }

            $directory = dirname($this->database);

            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                throw new RuntimeException(sprintf('The directory "%s" cannot be created.', $directory));
            }

            $temporary = $this->database.'.part';

            try {
                $this->download($response, $temporary);

                if (! rename($temporary, $this->database)) {
                    throw new RuntimeException(sprintf('The database "%s" cannot be put in place.', $this->database));
                }
            } finally {
                // What a failure left half-written; once renamed, nothing.
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }

            return $month->format('Y-m');
        }

        throw new RuntimeException('No DB-IP database could be downloaded for this month or the previous one.');
    }

    /**
     * Inflated as it arrives, chunk by chunk, into $target: the archive is
     * some 20 MB and the database three times that, neither of which a
     * request's memory has to hold.
     */
    private function download(
        ResponseInterface $response,
        string $target
    ): void {
        $handle = fopen($target, 'wb');

        if (false === $handle) {
            throw new RuntimeException(sprintf('The file "%s" cannot be written.', $target));
        }

        try {
            $inflate = inflate_init(ZLIB_ENCODING_GZIP);
            $written = 0;
            $head = '';

            foreach ($this->httpClient->stream($response) as $chunk) {
                $data = $chunk->getContent();

                // Checked before inflating: a gateway's html page would
                // otherwise end as a warning out of zlib instead of a failure
                // the caller knows.
                if (strlen($head) < 2) {
                    $head .= substr($data, 0, 2 - strlen($head));

                    if (strlen($head) >= 2 && "\x1f\x8b" !== $head) {
                        throw new RuntimeException('The DB-IP download is not a gzip file.');
                    }
                }

                if ('' === $data) {
                    continue;
                }

                $inflated = inflate_add($inflate, $data, ZLIB_SYNC_FLUSH);

                if (false === $inflated || false === fwrite($handle, $inflated)) {
                    throw new RuntimeException('The DB-IP download cannot be inflated into place.');
                }

                $written += strlen($inflated);
            }

            // Short of its end, the archive was cut on the way.
            if (ZLIB_STREAM_END !== inflate_get_status($inflate) || 0 === $written) {
                throw new RuntimeException('The DB-IP download is not a whole gzip file.');
            }
        } finally {
            fclose($handle);
        }
    }
}
