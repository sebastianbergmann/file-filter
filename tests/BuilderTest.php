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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Builder::class)]
#[UsesClass(Filter::class)]
#[Small]
final class BuilderTest extends TestCase
{
    public function testAcceptsFileInIncludedDirectory(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/Bar.php'));
        $this->assertTrue($filter->accepts('/src/subdir/Baz.php'));
    }

    public function testRejectsFileNotInIncludedDirectory(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/tests/FooTest.php'));
        $this->assertFalse($filter->accepts('/vendor/autoload.php'));
    }

    public function testAcceptsExplicitlyIncludedFile(): void
    {
        $filter = (new Builder)->build(
            [],
            ['/config/bootstrap.php', '/scripts/setup.php'],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/config/bootstrap.php'));
        $this->assertTrue($filter->accepts('/scripts/setup.php'));
    }

    public function testRejectsFileNotExplicitlyIncluded(): void
    {
        $filter = (new Builder)->build(
            [],
            ['/config/bootstrap.php'],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/config/other.php'));
        $this->assertFalse($filter->accepts('/scripts/setup.php'));
    }

    public function testRejectsFileInExcludedDirectory(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '']],
            [],
            [['path' => '/src/vendor', 'prefix' => '', 'suffix' => '']],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/vendor/autoload.php'));
        $this->assertFalse($filter->accepts('/src/vendor/package/Class.php'));
    }

    public function testRejectsExplicitlyExcludedFile(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            ['/src/Legacy.php', '/src/Deprecated.php'],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/Legacy.php'));
        $this->assertFalse($filter->accepts('/src/Deprecated.php'));
    }

    public function testExcludedFileOverridesIncludedDirectory(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            ['/src/Excluded.php'],
        );

        $this->assertTrue($filter->accepts('/src/Included.php'));
        $this->assertFalse($filter->accepts('/src/Excluded.php'));
    }

    public function testExcludedDirectoryOverridesIncludedFile(): void
    {
        $filter = (new Builder)->build(
            [],
            ['/src/vendor/Special.php'],
            [['path' => '/src/vendor', 'prefix' => '', 'suffix' => '']],
            [],
        );

        $this->assertFalse($filter->accepts('/src/vendor/Special.php'));
    }

    public function testRejectsFilesInHiddenDirectories(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/.hidden/File.php'));
        $this->assertFalse($filter->accepts('/src/.git/config'));
        $this->assertFalse($filter->accepts('/src/subdir/.cache/data.php'));
    }

    public function testFiltersWithPrefixRequirement(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => 'Test', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/TestFoo.php'));
        $this->assertTrue($filter->accepts('/src/TestBar.php'));
        $this->assertFalse($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/FooTest.php'));
    }

    public function testFiltersWithSuffixRequirement(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/Bar.php'));
        $this->assertFalse($filter->accepts('/src/config.xml'));
        $this->assertFalse($filter->accepts('/src/readme.txt'));
    }

    public function testFiltersWithPrefixAndSuffixRequirement(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/tests', 'prefix' => 'Test', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/tests/TestFoo.php'));
        $this->assertFalse($filter->accepts('/tests/TestFoo.xml'));
        $this->assertFalse($filter->accepts('/tests/Foo.php'));
        $this->assertFalse($filter->accepts('/tests/FooTest.php'));
    }

    public function testExcludeDirectoryWithPrefixAndSuffix(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '.php']],
            [],
            [['path' => '/src/generated', 'prefix' => 'Gen', 'suffix' => '.php']],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/generated/Manual.php'));
        $this->assertFalse($filter->accepts('/src/generated/GenClass.php'));
    }

    public function testGlobPatternSingleStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/*/models', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/app/models/User.php'));
        $this->assertTrue($filter->accepts('/src/admin/models/Admin.php'));
        $this->assertFalse($filter->accepts('/src/models/User.php'));
        $this->assertFalse($filter->accepts('/src/app/deep/models/User.php'));
    }

    public function testGlobPatternQuestionMark(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/v?/api', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/v1/api/Handler.php'));
        $this->assertTrue($filter->accepts('/src/v2/api/Handler.php'));
        $this->assertFalse($filter->accepts('/src/v10/api/Handler.php'));
        $this->assertFalse($filter->accepts('/src/api/Handler.php'));
    }

    public function testGlobPatternGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/**/tests', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/tests/FooTest.php'));
        $this->assertTrue($filter->accepts('/src/app/tests/FooTest.php'));
        $this->assertTrue($filter->accepts('/src/app/module/tests/FooTest.php'));
        $this->assertFalse($filter->accepts('/src/app/Foo.php'));
    }

    public function testGlobstarAtEndOfPath(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/vendor/**', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/vendor/package/src/Class.php'));
        $this->assertTrue($filter->accepts('/vendor/other/Class.php'));
        $this->assertTrue($filter->accepts('/vendor/Class.php'));
    }

    public function testMultipleIncludeDirectories(): void
    {
        $filter = (new Builder)->build(
            [
                ['path' => '/src', 'prefix' => '', 'suffix' => '.php'],
                ['path' => '/lib', 'prefix' => '', 'suffix' => '.php'],
            ],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/lib/Bar.php'));
        $this->assertFalse($filter->accepts('/tests/FooTest.php'));
    }

    public function testMultipleExcludeDirectories(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '']],
            [],
            [
                ['path' => '/src/vendor', 'prefix' => '', 'suffix' => ''],
                ['path' => '/src/cache', 'prefix' => '', 'suffix' => ''],
            ],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/vendor/Package.php'));
        $this->assertFalse($filter->accepts('/src/cache/data.php'));
    }

    public function testCombinationOfIncludeDirectoriesAndFiles(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '.php']],
            ['/config/bootstrap.php', '/scripts/init.php'],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/config/bootstrap.php'));
        $this->assertTrue($filter->accepts('/scripts/init.php'));
        $this->assertFalse($filter->accepts('/config/other.php'));
    }

    public function testCombinationOfExcludeDirectoriesAndFiles(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '']],
            [],
            [['path' => '/src/vendor', 'prefix' => '', 'suffix' => '']],
            ['/src/Legacy.php'],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/vendor/Package.php'));
        $this->assertFalse($filter->accepts('/src/Legacy.php'));
    }

    public function testEmptyConfiguration(): void
    {
        $filter = (new Builder)->build([], [], [], []);

        $this->assertFalse($filter->accepts('/any/path/file.php'));
        $this->assertFalse($filter->accepts('/src/Foo.php'));
    }

    public function testPathsWithSpecialRegexCharacters(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/app.module', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/app.module/Foo.php'));
        $this->assertFalse($filter->accepts('/src/appXmodule/Foo.php'));
    }

    public function testNestedDirectoryMatching(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/project/src/modules', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/project/src/modules/User.php'));
        $this->assertTrue($filter->accepts('/project/src/modules/sub/Admin.php'));
        $this->assertFalse($filter->accepts('/project/src/Foo.php'));
    }

    public function testDirectoryMatchingAtRootLevel(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/bootstrap.php'));
        $this->assertFalse($filter->accepts('/config.xml'));
    }

    public function testRootDirectoryDoesNotMatchSubdirectories(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/Foo.php'));
    }

    public function testGlobstarFromRootMatchesAllDirectories(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/bootstrap.php'));
        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/subdir/Bar.php'));
        $this->assertFalse($filter->accepts('/config.xml'));
    }
}
