<?php

declare(strict_types=1);

namespace Wexample\SymfonyRemoteDbIp\Tests\Unit;

use MaxMind\Db\Reader\InvalidDatabaseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Wexample\SymfonyRemoteDbIp\Service\DbIpCountryLocator;

final class DbIpCountryLocatorTest extends TestCase
{
    private string $database;

    protected function setUp(): void
    {
        // Not a database: whatever opens it fails, which shows when an
        // address is looked up and when it is turned away before.
        $this->database = tempnam(sys_get_temp_dir(), 'db-ip');
        file_put_contents($this->database, 'not a database');
    }

    protected function tearDown(): void
    {
        unlink($this->database);
    }

    public static function addressesOfNoCountry(): array
    {
        return [
            'private v4' => ['192.168.1.10'],
            'private v4, 10/8' => ['10.0.0.1'],
            'loopback v4' => ['127.0.0.1'],
            'unspecified v4' => ['0.0.0.0'],
            'link-local v4' => ['169.254.10.1'],
            'private v6' => ['fd00::1'],
            'loopback v6' => ['::1'],
            'not an address' => ['not-an-ip'],
            'empty' => [''],
        ];
    }

    #[DataProvider('addressesOfNoCountry')]
    public function testAnAddressOfNoCountryIsNotLookedUp(string $ip): void
    {
        $this->assertNull((new DbIpCountryLocator($this->database))->locateCountry($ip));
    }

    public function testAPublicAddressIsLookedUp(): void
    {
        $this->expectException(InvalidDatabaseException::class);

        (new DbIpCountryLocator($this->database))->locateCountry('8.8.8.8');
    }

    public function testWithoutTheFileNothingIsLocated(): void
    {
        $this->assertNull((new DbIpCountryLocator($this->database.'.missing'))->locateCountry('8.8.8.8'));
    }
}
