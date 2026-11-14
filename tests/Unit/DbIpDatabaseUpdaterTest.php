<?php

declare(strict_types=1);

namespace Wexample\SymfonyRemoteDbIp\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Wexample\SymfonyRemoteDbIp\Service\DbIpDatabaseUpdater;

final class DbIpDatabaseUpdaterTest extends TestCase
{
    private const string URL = 'https://db-ip.test/dbip-country-lite-%s.mmdb.gz';

    private string $directory;

    private string $database;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/db-ip-'.bin2hex(random_bytes(4));
        $this->database = $this->directory.'/sub/country.mmdb';
    }

    protected function tearDown(): void
    {
        foreach ([$this->database, $this->database.'.part'] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        foreach ([$this->directory.'/sub', $this->directory] as $directory) {
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    /**
     * @param list<string> $urls
     */
    private function updater(
        array $responses,
        ?array &$urls = null
    ): DbIpDatabaseUpdater {
        $http = new MockHttpClient(function (string $method, string $url) use (&$responses, &$urls): MockResponse {
            $urls[] = $url;

            return array_shift($responses);
        });

        return new DbIpDatabaseUpdater($http, $this->database, self::URL);
    }

    /**
     * The archive in pieces, as the network hands it.
     */
    private function gzip(string $content): MockResponse
    {
        return new MockResponse(str_split(gzencode($content), 7));
    }

    public function testTheMonthsFileIsInflatedInPlace(): void
    {
        $content = str_repeat('database bytes ', 1000);

        $month = $this->updater([$this->gzip($content)], $urls)->update();

        $this->assertSame((new DateTimeImmutable('first day of this month'))->format('Y-m'), $month);
        $this->assertSame([sprintf(self::URL, $month)], $urls);
        $this->assertSame($content, file_get_contents($this->database));
        $this->assertFileDoesNotExist($this->database.'.part');
    }

    public function testThePreviousMonthIsTakenWhileTheNewOneIsNotOut(): void
    {
        $previous = (new DateTimeImmutable('first day of this month'))->modify('-1 month')->format('Y-m');

        $month = $this->updater([new MockResponse('', ['http_code' => 404]), $this->gzip('previous')], $urls)->update();

        $this->assertSame($previous, $month);
        $this->assertSame(sprintf(self::URL, $previous), $urls[1]);
        $this->assertSame('previous', file_get_contents($this->database));
    }

    public function testNeitherMonthOut(): void
    {
        $this->expectException(RuntimeException::class);

        $this->updater([new MockResponse('', ['http_code' => 404]), new MockResponse('', ['http_code' => 404])])->update();
    }

    public static function brokenDownloads(): array
    {
        $archive = gzencode(str_repeat('database bytes ', 1000));

        return [
            'an html page' => [['<html>Bad gateway</html>']],
            'an archive cut short' => [[substr($archive, 0, (int) (strlen($archive) / 2))]],
            'nothing' => [['']],
        ];
    }

    #[DataProvider('brokenDownloads')]
    public function testABrokenDownloadLeavesTheOldFileAndNothingElse(array $body): void
    {
        mkdir(dirname($this->database), 0775, true);
        file_put_contents($this->database, 'old database');

        try {
            $this->updater([new MockResponse($body)])->update();
            $this->fail('A broken download was put in place.');
        } catch (RuntimeException) {
        }

        $this->assertSame('old database', file_get_contents($this->database));
        $this->assertFileDoesNotExist($this->database.'.part');
    }

    public function testStaleness(): void
    {
        $updater = $this->updater([]);
        $this->assertTrue($updater->isStale());

        mkdir(dirname($this->database), 0775, true);
        file_put_contents($this->database, 'database');
        $this->assertFalse($updater->isStale());

        touch($this->database, time() - (DbIpDatabaseUpdater::MAX_AGE_DAYS + 1) * 86400);
        $this->assertTrue($updater->isStale());
    }
}
