<?php
namespace Facuz\Theme;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class Manifest
{
    /**
     * Path of all themes.
     *
     * @var string
     */
    protected ?string $themePath = null;

    /**
     * Filesystem.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new theme instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     *
     * @return \Facuz\Theme\Manifest
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }


    /**
     * Sets the specified themes path.
     *
     * @param string $themePath
     *
     * @return void
     */
    public function setThemePath(string $themePath): void
    {
        $this->themePath = $themePath;
    }

    /**
     * Get path of theme JSON file.
     *
     * @return string
     */
    public function getJsonPath(): string
    {
        if ($this->themePath === null) {
            throw new \LogicException('A theme path must be set before reading the manifest.');
        }

        return $this->themePath . '/theme.json';
    }

    /**
     * Get theme JSON content as an array.
     *
     * @return array|mixed
     */
    public function getJsonContents(): array
    {
        $path = $this->getJsonPath();

        if ($this->files->exists($path)) {
            $contents = $this->files->get($path);

            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($manifest)) {
                throw new \UnexpectedValueException('The theme manifest must contain a JSON object.');
            }

            return $manifest;
        }

        throw new UnknownFileException("The theme must have a valid theme.json manifest file.");
    }

    /**
     * Set theme manifest JSON content property value.
     *
     * @param array $content
     *
     * @return integer
     */
    protected function setJsonContents(array $content): bool
    {
        $encodedContent = json_encode($content, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        return $this->files->put($this->getJsonPath(), $encodedContent);
    }

    /**
     * Get a theme manifest key value.
     *
     * @param string $key
     * @param null|string $default
     *
     * @return mixed
     */
    public function getProperty(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->getJsonContents(), $key, $default);
    }

    /**
     * Set a theme manifest key value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public function setProperty(string $key, mixed $value): bool
    {

        $content = $this->getJsonContents();

        if (count($content)) {
            if (isset($content[$key])) {
                unset($content[$key]);
            }

            $content[$key] = $value;

            $this->setJsonContents($content);

            return true;
        }

        return false;
    }


}
