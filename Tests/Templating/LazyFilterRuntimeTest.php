<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Tests\Templating;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Templating\LazyFilterRuntime;
use Liip\ImagineBundle\Tests\AbstractTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Liip\ImagineBundle\Templating\LazyFilterRuntime
 */
class LazyFilterRuntimeTest extends AbstractTest
{
    private const FILTER = 'thumbnail';
    private const VERSION = 'v2';

    /**
     * @var LazyFilterRuntime
     */
    private $runtime;
    /**
     * @var CacheManager&MockObject
     */
    private $manager;

    protected function setUp(): void
    {
        $this->manager = $this->createCacheManagerMock();
        $this->runtime = new LazyFilterRuntime($this->manager);
    }

    public function provideImageNames(): iterable
    {
        yield 'regular' => ['image' => 'cats.jpeg', 'urlimage' => 'cats.jpeg'];
        yield 'whitespace' => ['image' => 'white cat.jpeg', 'urlimage' => 'white%20cat.jpeg'];
        yield 'plus' => ['image' => 'cat+plus.jpeg', 'urlimage' => 'cat%2Bplus.jpeg'];
        yield 'questionmark' => ['image' => 'cat?question.jpeg', 'urlimage' => 'cat%3Fquestion.jpeg'];
        yield 'hash' => ['image' => 'cat#hash.jpeg', 'urlimage' => 'cat%23hash.jpeg'];
    }

    /**
     * @dataProvider provideImageNames
     */
    public function testInvokeFilterMethod($image, $urlimage): void
    {
        $this->manager
            ->expects($this->once())
            ->method('getBrowserPath')
            ->with($image, self::FILTER)
            ->willReturn($urlimage);

        $actualPath = $this->runtime->filter($image, self::FILTER);

        $this->assertSame($urlimage, $actualPath);
    }

    public function testVersionHandling(): void
    {
        $this->runtime = new LazyFilterRuntime($this->manager, self::VERSION);

        $sourcePath = 'thePathToTheImage';
        $cachePath = 'thePathToTheCachedImage';

        $this->manager
            ->expects($this->once())
            ->method('getBrowserPath')
            ->with($sourcePath, self::FILTER)
            ->willReturn($cachePath);

        $actualPath = $this->runtime->filter($sourcePath.'?'.self::VERSION, self::FILTER);

        $this->assertSame($cachePath.'?'.self::VERSION, $actualPath);
    }

    public function testDifferentVersion(): void
    {
        $this->runtime = new LazyFilterRuntime($this->manager, self::VERSION);

        $sourcePath = 'thePathToTheImage?v22';
        $cachePath = 'thePathToTheCachedImage';

        $this->manager
            ->expects($this->once())
            ->method('getBrowserPath')
            ->with($sourcePath, self::FILTER)
            ->willReturn($cachePath);

        $actualPath = $this->runtime->filter($sourcePath, self::FILTER);

        $this->assertSame($cachePath.'?'.self::VERSION, $actualPath);
    }

    public function testInvokeFilterCacheMethod(): void
    {
        $expectedInputPath = 'thePathToTheImage';
        $expectedCachePath = 'thePathToTheCachedImage';

        $this->manager
            ->expects($this->once())
            ->method('resolve')
            ->with($expectedInputPath, self::FILTER)
            ->willReturn($expectedCachePath);

        $actualPath = $this->runtime->filterCache($expectedInputPath, self::FILTER);

        $this->assertSame($expectedCachePath, $actualPath);
    }

    public function testInvokeFilterCacheMethodWithRuntimeConfig(): void
    {
        $expectedInputPath = 'thePathToTheImage';
        $expectedCachePath = 'thePathToTheCachedImage';
        $runtimeConfig = [
            self::FILTER => [
                'size' => [100, 100],
            ],
        ];
        $expectedRuntimeConfigPath = 'thePathToTheImageWithRuntimeConfig';

        $this->manager
            ->expects($this->once())
            ->method('getRuntimePath')
            ->with($expectedInputPath, $runtimeConfig)
            ->willReturn($expectedRuntimeConfigPath);
        $this->manager
            ->expects($this->once())
            ->method('resolve')
            ->with($expectedRuntimeConfigPath, self::FILTER)
            ->willReturn($expectedCachePath);

        $actualPath = $this->runtime->filterCache($expectedInputPath, self::FILTER, $runtimeConfig);

        $this->assertSame($expectedCachePath, $actualPath);
    }
}
