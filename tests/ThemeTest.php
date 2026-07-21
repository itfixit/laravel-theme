<?php

use Facuz\Theme\Asset;
use Facuz\Theme\AssetContainer;
use Facuz\Theme\Manifest;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class ThemeTest extends TestCase
{
    private array $manifestDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();

        Asset::$containers = [];
        Asset::$path = null;
    }

    protected function tearDown(): void
    {
        foreach ($this->manifestDirectories as $directory) {
            (new Filesystem())->deleteDirectory($directory);
        }

        parent::tearDown();
    }

    public function testAssetContainerRegistersAssetsWithoutDynamicProperties(): void
    {
        $container = new AssetContainer('default');

        set_error_handler(function ($severity, $message, $file, $line) {
            if ($severity === E_DEPRECATED) {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
        });

        try {
            $container->add('app', 'css/app.css');
        } finally {
            restore_error_handler();
        }

        $assets = $this->readProperty($container, 'assets');

        $this->assertSame('css/app.css', $assets['style']['app']['source']);
        $this->assertSame(['media' => 'all'], $assets['style']['app']['attributes']);
    }

    public function testAssetDependenciesAreSorted(): void
    {
        $container = new AssetContainer('default');
        $assets = [
            'app' => [
                'source' => 'app.js',
                'dependencies' => ['jquery'],
                'attributes' => [],
            ],
            'jquery' => [
                'source' => 'jquery.js',
                'dependencies' => [],
                'attributes' => [],
            ],
        ];

        $arranged = $this->invokeMethod($container, 'arrange', [$assets]);

        $this->assertSame(['jquery', 'app'], array_keys($arranged));
    }

    public function testCircularAssetDependenciesAreRejected(): void
    {
        $container = new AssetContainer('default');
        $assets = [
            'app' => [
                'source' => 'app.js',
                'dependencies' => ['jquery'],
                'attributes' => [],
            ],
            'jquery' => [
                'source' => 'jquery.js',
                'dependencies' => ['app'],
                'attributes' => [],
            ],
        ];

        $this->expectException(Exception::class);

        $this->invokeMethod($container, 'arrange', [$assets]);
    }

    public function testCookedAssetsAreFlushedWhenServed(): void
    {
        $asset = new Asset();
        $called = false;

        $asset->cook('app', function () use (&$called) {
            $called = true;
        });

        $this->assertSame($asset, $asset->serve('app'));

        $asset->flush();

        $this->assertTrue($called);
    }

    public function testAssetContainersAreNamedSingletons(): void
    {
        $this->assertSame(Asset::container('header'), Asset::container('header'));
        $this->assertNotSame(Asset::container('header'), Asset::container('footer'));
    }

    public function testAssetPathIsNormalized(): void
    {
        $asset = new Asset();

        $asset->addPath('/themes/default/assets');

        $this->assertSame('/themes/default/assets/', Asset::$path);
    }

    public function testDefaultConfigDoesNotRegisterCallbacks(): void
    {
        $config = require __DIR__.'/../config/theme.php';

        $this->assertNull($config['events']['before']);
        $this->assertNull($config['events']['asset']);
    }

    public function testManifestReadsAndWritesJsonProperties(): void
    {
        $directory = $this->createManifestDirectory();
        $manifest = new Manifest(new Filesystem());
        $manifest->setThemePath($directory);

        file_put_contents($directory.'/theme.json', json_encode([
            'name' => 'default',
            'meta' => ['version' => 1],
        ]));

        $this->assertSame(1, $manifest->getProperty('meta.version'));
        $this->assertTrue($manifest->setProperty('name', 'updated'));
        $this->assertSame('updated', $manifest->getProperty('name'));

    }

    public function testManifestRejectsInvalidJson(): void
    {
        $directory = $this->createManifestDirectory();
        $manifest = new Manifest(new Filesystem());
        $manifest->setThemePath($directory);

        file_put_contents($directory.'/theme.json', '{invalid');

        $this->expectException(JsonException::class);
        $manifest->getJsonContents();

    }

    private function readProperty(object $object, string $property)
    {
        $reflection = new ReflectionProperty($object, $property);

        return $reflection->getValue($object);
    }

    private function invokeMethod(object $object, string $method, array $arguments = [])
    {
        $reflection = new ReflectionMethod($object, $method);

        return $reflection->invokeArgs($object, $arguments);
    }

    private function createManifestDirectory(): string
    {
        $directory = sys_get_temp_dir().'/facuz-theme-'.uniqid('', true);

        mkdir($directory, 0777, true);
        $this->manifestDirectories[] = $directory;

        return $directory;
    }
}
