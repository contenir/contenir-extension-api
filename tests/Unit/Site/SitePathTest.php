<?php

declare(strict_types=1);

namespace Contenir\Extension\Api\Tests\Unit\Site;

use Contenir\Extension\Api\Exception\InvalidDeclarationException;
use Contenir\Extension\Api\Site\SitePath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SitePathTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function refusedProvider(): array
    {
        return [
            'nothing'             => [''],
            'the api root'        => ['/api'],
            'the api root, slash' => ['/api/'],
            'outside api'         => ['/admin/users'],
            'no leading slash'    => ['api/seo/inspect'],
            'another host'        => ['https://example.com/api/seo'],
            'another host, short' => ['//example.com/api/seo'],
            'climbing out'        => ['/api/../admin'],
            'a query'             => ['/api/seo?x=1'],
            'a new line after'    => ["/api/seo\n"],
            'a trailing slash'    => ['/api/seo/'],
        ];
    }

    #[Test]
    public function givesBackAnApiPath(): void
    {
        static::assertSame('/api/seo/in_spect-2', SitePath::check('/api/seo/in_spect-2'));
    }

    #[Test]
    #[DataProvider('refusedProvider')]
    public function refusesAnythingButTheSitesApiPaths(string $path): void
    {
        $this->expectException(InvalidDeclarationException::class);
        $this->expectExceptionMessage("\"{$path}\" is not one of the site's /api/ paths");

        SitePath::check($path);
    }
}
