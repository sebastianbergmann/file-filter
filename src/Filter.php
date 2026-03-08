<?php declare(strict_types=1);
/*
 * This file is part of sebastian/file-filter.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\FileFilter;

use const DIRECTORY_SEPARATOR;
use function array_key_exists;
use function basename;
use function dirname;
use function preg_match;
use function str_ends_with;
use function str_replace;
use function str_starts_with;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for this library
 */
final readonly class Filter
{
    /**
     * @var list<array{regularExpression: string, prefix: string, suffix: string}>
     */
    private array $includeDirectoryMatchers;

    /**
     * @var array<string, true>
     */
    private array $includeFilesMap;

    /**
     * @var list<array{regularExpression: string, prefix: string, suffix: string}>
     */
    private array $excludeDirectoryMatchers;

    /**
     * @var array<string, true>
     */
    private array $excludeFilesMap;
    private bool $excludeHiddenDirectories;

    /**
     * @param list<array{regularExpression: string, prefix: string, suffix: string}> $includeDirectoryMatchers
     * @param array<string, true>                                                    $includeFilesMap
     * @param list<array{regularExpression: string, prefix: string, suffix: string}> $excludeDirectoryMatchers
     * @param array<string, true>                                                    $excludeFilesMap
     */
    public function __construct(array $includeDirectoryMatchers, array $includeFilesMap, array $excludeDirectoryMatchers, array $excludeFilesMap, bool $excludeHiddenDirectories = true)
    {
        $this->includeDirectoryMatchers = $includeDirectoryMatchers;
        $this->includeFilesMap          = $includeFilesMap;
        $this->excludeDirectoryMatchers = $excludeDirectoryMatchers;
        $this->excludeFilesMap          = $excludeFilesMap;
        $this->excludeHiddenDirectories = $excludeHiddenDirectories;
    }

    /**
     * @param non-empty-string $path
     */
    public function accepts(string $path): bool
    {
        $normalizedPath = str_replace(DIRECTORY_SEPARATOR, '/', $path);

        if ($this->excludeHiddenDirectories && $this->isInHiddenDirectory($normalizedPath)) {
            return false;
        }

        $directory = dirname($normalizedPath);
        $filename  = basename($normalizedPath);

        // Check if explicitly excluded by file
        if (array_key_exists($normalizedPath, $this->excludeFilesMap)) {
            return false;
        }

        // Check if explicitly included by file
        if (array_key_exists($normalizedPath, $this->includeFilesMap)) {
            // Still need to check if excluded by directory
            if ($this->matchesDirectory($this->excludeDirectoryMatchers, $directory, $filename)) {
                return false;
            }

            return true;
        }

        // Check if included by directory
        if (!$this->matchesDirectory($this->includeDirectoryMatchers, $directory, $filename)) {
            return false;
        }

        // Check if excluded by directory
        if ($this->matchesDirectory($this->excludeDirectoryMatchers, $directory, $filename)) {
            return false;
        }

        return true;
    }

    private function isInHiddenDirectory(string $path): bool
    {
        // Check if any directory component starts with a dot
        return (bool) preg_match('#/\.[^/]+/#', $path . '/');
    }

    /**
     * @param list<array{regularExpression: string, prefix: string, suffix: string}> $matchers
     */
    private function matchesDirectory(array $matchers, string $directory, string $filename): bool
    {
        foreach ($matchers as $matcher) {
            // Check if directory matches the regular expression
            if (preg_match($matcher['regularExpression'], $directory . '/') !== 1) {
                continue;
            }

            // Check prefix
            if ($matcher['prefix'] !== '' && !str_starts_with($filename, $matcher['prefix'])) {
                continue;
            }

            // Check suffix
            if ($matcher['suffix'] !== '' && !str_ends_with($filename, $matcher['suffix'])) {
                continue;
            }

            return true;
        }

        return false;
    }
}
