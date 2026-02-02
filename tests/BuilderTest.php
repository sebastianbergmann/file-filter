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

    public function testGlobstarWithSingleCharPrefix(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/Z**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/Zlib/Bar.php'));
        $this->assertTrue($filter->accepts('/src/deep/nested/Baz.php'));
        $this->assertFalse($filter->accepts('/other/Foo.php'));
    }

    public function testGlobstarWithMultiCharPrefix(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/ZZ**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/ZZlib/Foo.php'));
        $this->assertTrue($filter->accepts('/src/ZZlib/sub/Bar.php'));
        $this->assertFalse($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/Zlib/Foo.php'));
    }

    public function testGlobstarWithSingleCharPrefixFollowedBySlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/Z**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/Zlib/Bar.php'));
    }

    public function testGlobstarWithMultiCharPrefixFollowedBySlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/ZZ**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/ZZlib/Foo.php'));
        $this->assertFalse($filter->accepts('/src/Foo.php'));
    }

    public function testGlobstarAtStartFollowedByDirectory(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/src', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('app/src/Foo.php'));
        $this->assertTrue($filter->accepts('project/deep/src/Bar.php'));
        $this->assertFalse($filter->accepts('/src/Foo.php'));
    }

    public function testGlobstarAfterSlashFollowedBySlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/project/**/src/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/project/src/Foo.php'));
        $this->assertTrue($filter->accepts('/project/deep/src/Bar.php'));
    }

    public function testGlobstarWithNoSlashBefore(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'src/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('src/Foo.php'));
        $this->assertTrue($filter->accepts('src/subdir/Bar.php'));
    }

    public function testSingleStarMatchesOneDirectoryLevel(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/app/Foo.php'));
        $this->assertTrue($filter->accepts('/src/app/deep/Foo.php'));
    }

    public function testQuestionMarkOnly(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/a/Foo.php'));
        $this->assertFalse($filter->accepts('/src/ab/Foo.php'));
    }

    public function testPathWithMultipleSpecialCharTypes(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/v?/*/tests', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/v1/app/tests/FooTest.php'));
        $this->assertFalse($filter->accepts('/src/v10/app/tests/FooTest.php'));
    }

    public function testPathWithQuestionMarkAndGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/v?/**/tests', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/v1/tests/FooTest.php'));
        $this->assertTrue($filter->accepts('/src/v1/app/module/tests/FooTest.php'));
    }

    public function testPathWithSpecialRegexCharsDotPlusDollar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/app.v1+$end', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/app.v1+$end/Foo.php'));
        $this->assertFalse($filter->accepts('/src/appXv1X$end/Foo.php'));
    }

    public function testPathWithParenthesesAndBrackets(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/(group)[class]', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/(group)[class]/Foo.php'));
    }

    public function testPathWithCaretAndPipe(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/^start|alt', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/^start|alt/Foo.php'));
    }

    public function testGlobstarAtStartWithNoSlashBefore(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
        $this->assertTrue($filter->accepts('/src/Foo.php'));
    }

    public function testSingleCharPrefixBeforeGlobstarNoSlashInPath(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'Z**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
        $this->assertTrue($filter->accepts('/Zlib/Foo.php'));
    }

    public function testMultiCharPrefixBeforeGlobstarNoSlashInPath(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ZZ**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ZZlib/Foo.php'));
        $this->assertTrue($filter->accepts('ZZlib/deep/Foo.php'));
        $this->assertFalse($filter->accepts('Foo.php'));
    }

    public function testGlobstarPrecededBySingleCharAfterSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/b**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
        $this->assertTrue($filter->accepts('/a/blib/Foo.php'));
    }

    public function testGlobstarPrecededByMultipleCharsAfterSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/bc**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/bclib/Foo.php'));
        $this->assertFalse($filter->accepts('/a/Foo.php'));
    }

    public function testEmptyIncludeDirectoriesWithExcludeFiles(): void
    {
        $filter = (new Builder)->build(
            [],
            ['/src/Foo.php'],
            [],
            ['/src/Foo.php'],
        );

        $this->assertFalse($filter->accepts('/src/Foo.php'));
    }

    public function testPathNormalizationWithBackslashes(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
    }

    public function testMultipleIncludeFilesWithExcludeDirectory(): void
    {
        $filter = (new Builder)->build(
            [],
            ['/src/Foo.php', '/src/vendor/Special.php'],
            [['path' => '/src/vendor', 'prefix' => '', 'suffix' => '']],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/vendor/Special.php'));
    }

    public function testPathWithOnlySlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testPathWithMultipleSlashes(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/b/c', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/b/c/Foo.php'));
    }

    public function testPathWithOnlyStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('app/Foo.php'));
    }

    public function testPathWithOnlyQuestionMark(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testPathWithOnlyGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testPathWithGlobstarThenSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
    }

    public function testPathSlashStarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/*/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testPathSlashQuestionSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/?/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testPathSlashGlobstarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testPathStarStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/*/b/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/x/b/y/Foo.php'));
    }

    public function testPathQuestionQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/?/b/?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/x/b/y/Foo.php'));
    }

    public function testPathGlobstarGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/a/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
        $this->assertTrue($filter->accepts('/x/a/y/Foo.php'));
    }

    public function testPathMixStarQuestionGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/*/a/?/b/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/x/a/y/b/Foo.php'));
    }

    public function testPathTextStarText(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/app*/lib', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/app/lib/Foo.php'));
        $this->assertTrue($filter->accepts('/src/appXYZ/lib/Foo.php'));
    }

    public function testPathTextQuestionText(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/v?/api', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/v1/api/Foo.php'));
    }

    public function testPathTextGlobstarText(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/**/tests', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/tests/FooTest.php'));
        $this->assertTrue($filter->accepts('/src/a/b/tests/FooTest.php'));
    }

    public function testPathSingleCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
        $this->assertTrue($filter->accepts('/alib/Foo.php'));
    }

    public function testPathSingleCharGlobstarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testPathMultiCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ablib/Foo.php'));
    }

    public function testPathMultiCharGlobstarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ablib/Foo.php'));
    }

    public function testPathGlobstarAtStart(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/lib', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('lib/Foo.php'));
        $this->assertTrue($filter->accepts('a/lib/Foo.php'));
    }

    public function testPathGlobstarAtEnd(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/a/b/Foo.php'));
    }

    public function testPathSingleCharGlobstarNoSlashBefore(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
        $this->assertTrue($filter->accepts('alib/Foo.php'));
    }

    public function testPathMultiCharGlobstarNoSlashBefore(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ablib/Foo.php'));
    }

    public function testPathSingleCharGlobstarWithSlashAfter(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
    }

    public function testPathMultiCharGlobstarWithSlashAfter(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ablib/Foo.php'));
    }

    public function testPathSlashSingleCharGlobstarText(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a**/lib', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/lib/Foo.php'));
        $this->assertTrue($filter->accepts('/alib/lib/Foo.php'));
    }

    public function testPathSlashMultiCharGlobstarText(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab**/lib', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ablib/lib/Foo.php'));
    }

    public function testPathTextSlashSingleCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/a**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/alib/Foo.php'));
    }

    public function testPathTextSlashMultiCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/ablib/Foo.php'));
    }

    public function testEmptyPath(): void
    {
        $filter = (new Builder)->build(
            /** @phpstan-ignore argument.type */
            [['path' => '', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testPathWithEscapedCharacters(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/[test]', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/[test]/Foo.php'));
        $this->assertFalse($filter->accepts('/src/t/Foo.php'));
    }

    public function testPathWithBackslash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src\\lib', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        // Backslash is escaped as a literal character in regex, not treated as path separator
        $this->assertFalse($filter->accepts('/src/lib/Foo.php'));
    }

    public function testGlobstarAfterSlashThenText(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/src/lib', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/lib/Foo.php'));
        $this->assertTrue($filter->accepts('/a/src/lib/Foo.php'));
    }

    public function testGlobstarNoSlashBeforeWithSlashAfterThenText(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/src', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('src/Foo.php'));
        $this->assertTrue($filter->accepts('a/src/Foo.php'));
    }

    public function testSingleCharBeforeGlobstarAfterSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/x**/lib', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/lib/Foo.php'));
        $this->assertTrue($filter->accepts('/src/xlib/lib/Foo.php'));
    }

    public function testMultiCharBeforeGlobstarAfterSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/xy**/lib', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/xylib/lib/Foo.php'));
    }

    public function testConsecutiveStars(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/*/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/a/b/Foo.php'));
    }

    public function testConsecutiveQuestionMarks(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/??', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/ab/Foo.php'));
    }

    public function testStarFollowedByQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/*?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/ab/Foo.php'));
    }

    public function testQuestionFollowedByStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/?*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/ab/Foo.php'));
    }

    public function testGlobstarFollowedByStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/**/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/a/Foo.php'));
    }

    public function testGlobstarFollowedByQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/**/?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/a/Foo.php'));
    }

    public function testStarFollowedByGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/*/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/a/Foo.php'));
        $this->assertTrue($filter->accepts('/src/a/b/Foo.php'));
    }

    public function testQuestionFollowedByGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/src/?/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/a/Foo.php'));
        $this->assertTrue($filter->accepts('/src/a/b/Foo.php'));
    }

    public function testSingleChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testTwoChars(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/Foo.php'));
    }

    public function testThreeChars(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'abc', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abc/Foo.php'));
    }

    public function testCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testCharStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/Foo.php'));
    }

    public function testStarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('xa/Foo.php'));
    }

    public function testCharQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/Foo.php'));
    }

    public function testQuestionChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('xa/Foo.php'));
    }

    public function testSlashStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testStarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testSlashQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testQuestionSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testGlobstarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
        $this->assertTrue($filter->accepts('x/a/Foo.php'));
    }

    public function testCharCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abx/Foo.php'));
    }

    public function testCharCharGlobstarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abx/Foo.php'));
    }

    public function testSlashCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testSlashCharCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/abx/Foo.php'));
    }

    public function testSlashCharGlobstarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testSlashCharCharGlobstarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/abx/Foo.php'));
    }

    public function testCharSlashGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testCharSlashGlobstarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testGlobstarSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testSlashGlobstarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testSlashGlobstarSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/x/a/Foo.php'));
    }

    public function testCharSlashCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/b**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testCharSlashCharCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/bc**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/bcx/Foo.php'));
    }

    public function testSlashCharSlashCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/b**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testSlashCharSlashCharCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/bc**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/bcx/Foo.php'));
    }

    public function testCharGlobstarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a**/b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('b/Foo.php'));
    }

    public function testCharCharGlobstarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab**/c', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abx/c/Foo.php'));
    }

    public function testCharGlobstarSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a**/b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('x/b/Foo.php'));
    }

    public function testSlashStarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/*a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/xa/Foo.php'));
    }

    public function testCharStarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a*/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/Foo.php'));
    }

    public function testSlashQuestionChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/?a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/xa/Foo.php'));
    }

    public function testCharQuestionSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a?/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/Foo.php'));
    }

    public function testStarStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testQuestionQuestionSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '??/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/Foo.php'));
    }

    public function testSlashSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '//', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/a/Foo.php'));
    }

    public function testCharCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/Foo.php'));
    }

    public function testSlashCharChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ab/Foo.php'));
    }

    public function testCharSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testSlashCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testStarQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/Foo.php'));
    }

    public function testQuestionStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/Foo.php'));
    }

    public function testGlobstarStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '***', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testStarGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '***', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
    }

    public function testGlobstarQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testStarStarCharGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/a/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
        $this->assertTrue($filter->accepts('x/a/y/Foo.php'));
    }

    public function testGlobstarGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '****', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
    }

    public function testSlashStarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/*/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testSlashQuestionSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/?/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testSlashGlobstarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testCharStarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a*b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('axb/Foo.php'));
    }

    public function testCharQuestionChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a?b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('axb/Foo.php'));
    }

    public function testStarCharStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*a*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('xax/Foo.php'));
    }

    public function testQuestionCharQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?a?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('xax/Foo.php'));
    }

    public function testSlashCharStarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a*b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/axb/Foo.php'));
    }

    public function testCharStarCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a*b/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('axb/Foo.php'));
    }

    public function testSlashCharQuestionChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a?b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/axb/Foo.php'));
    }

    public function testCharQuestionCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a?b/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('axb/Foo.php'));
    }

    // Additional path coverage tests - targeting specific branch sequences
    public function testPathSlashSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '//a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/a/Foo.php'));
    }

    public function testPathCharCharChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'abc', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abc/Foo.php'));
    }

    public function testPathCharCharCharChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'abcd', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abcd/Foo.php'));
    }

    public function testPathStarStarStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*/*/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/c/Foo.php'));
    }

    public function testPathQuestionQuestionQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '???', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abc/Foo.php'));
    }

    public function testPathSlashSlashSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '///', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/a/b/Foo.php'));
    }

    public function testPathCharStarQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a*?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('axb/Foo.php'));
    }

    public function testPathCharQuestionStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a?*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('axb/Foo.php'));
    }

    public function testPathStarCharQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*a?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('xab/Foo.php'));
    }

    public function testPathStarQuestionChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*?a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('xya/Foo.php'));
    }

    public function testPathQuestionCharStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?a*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('xab/Foo.php'));
    }

    public function testPathQuestionStarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?*a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('xya/Foo.php'));
    }

    public function testPathCharSlashStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testPathCharSlashQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testPathStarSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('x/a/Foo.php'));
    }

    public function testPathStarSlashStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testPathStarSlashQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*/?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testPathQuestionSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('x/a/Foo.php'));
    }

    public function testPathQuestionSlashStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testPathQuestionSlashQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?/?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testPathSlashCharStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ab/Foo.php'));
    }

    public function testPathSlashCharQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ab/Foo.php'));
    }

    public function testPathSlashStarStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/*/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/b/Foo.php'));
    }

    public function testPathSlashStarQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/*?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ab/Foo.php'));
    }

    public function testPathSlashQuestionStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/?*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ab/Foo.php'));
    }

    public function testPathSlashQuestionQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/??', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ab/Foo.php'));
    }

    public function testPathSlashCharCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ab/Foo.php'));
    }

    public function testPathCharSlashCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/b/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testPathSlashCharSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/b/Foo.php'));
    }

    public function testPathCharCharSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab/c', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('ab/c/Foo.php'));
    }

    public function testPathCharSlashCharChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/bc', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/bc/Foo.php'));
    }

    public function testPathGlobstarSlashStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testPathGlobstarSlashQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testPathGlobstarSlashGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testPathStarSlashGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testPathQuestionSlashGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '?/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testPathCharSlashGlobstarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/**/b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/Foo.php'));
    }

    public function testPathGlobstarCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/a/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testPathCharGlobstarSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
    }

    public function testPathSlashCharGlobstarChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a**/b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/b/Foo.php'));
    }

    public function testPathSlashGlobstarCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/a/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testPathCharGlobstarCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a**/b/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('b/Foo.php'));
    }

    public function testPathCharCharGlobstarCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab**/c/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abx/c/Foo.php'));
    }

    public function testPathSlashCharCharGlobstarCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab**/c/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/abx/c/Foo.php'));
    }

    public function testPathFourSlashes(): void
    {
        $filter = (new Builder)->build(
            [['path' => '////', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/a/b/c/Foo.php'));
    }

    public function testPathFourChars(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'abcd', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abcd/Foo.php'));
    }

    public function testPathFourStars(): void
    {
        $filter = (new Builder)->build(
            [['path' => '*/*/*/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/c/d/Foo.php'));
    }

    public function testPathFourQuestions(): void
    {
        $filter = (new Builder)->build(
            [['path' => '????', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abcd/Foo.php'));
    }

    public function testPathSlashCharSlashCharSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/b/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/b/Foo.php'));
    }

    public function testPathCharSlashCharSlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/b/c', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/b/c/Foo.php'));
    }

    public function testPathSlashStarSlashStar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/*/*/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/b/Foo.php'));
    }

    public function testPathSlashQuestionSlashQuestion(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/?/?/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/b/Foo.php'));
    }

    public function testPathSlashGlobstarSlashGlobstar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testPathComplexMix1(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a*/b?/c', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/ax/by/c/Foo.php'));
    }

    public function testPathComplexMix2(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/?a/*b/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/xa/yb/Foo.php'));
    }

    public function testPathComplexMix3(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a?b*c', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/axbyc/Foo.php'));
    }

    public function testPathComplexMix4(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/a/*/b', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/x/b/Foo.php'));
    }

    public function testPathComplexMix5(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a/**/b/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/b/x/Foo.php'));
    }

    // Targeting specific globstar branch combinations
    public function testGlobstarAfterTwoCharsWithSlashBetween(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/b**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testGlobstarAfterTwoCharsNoSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abx/Foo.php'));
    }

    public function testGlobstarAfterThreeCharsWithSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a/bc**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/bcx/Foo.php'));
    }

    public function testGlobstarAfterThreeCharsNoSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'abc**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abcx/Foo.php'));
    }

    public function testSlashGlobstarNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testSlashGlobstarWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testGlobstarAtStartNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
    }

    public function testGlobstarAtStartWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
    }

    public function testSingleCharGlobstarNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
    }

    public function testSingleCharGlobstarWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'a**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('Foo.php'));
    }

    public function testMultiCharGlobstarNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abx/Foo.php'));
    }

    public function testMultiCharGlobstarWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'ab**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('abx/Foo.php'));
    }

    public function testSlashSingleCharGlobstarNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testSlashSingleCharGlobstarWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/a**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/Foo.php'));
    }

    public function testSlashMultiCharGlobstarNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/abx/Foo.php'));
    }

    public function testSlashMultiCharGlobstarWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/ab**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/abx/Foo.php'));
    }

    public function testCharSlashSingleCharGlobstarNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'x/a**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('x/Foo.php'));
    }

    public function testCharSlashSingleCharGlobstarWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'x/a**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('x/Foo.php'));
    }

    public function testCharSlashMultiCharGlobstarNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'x/ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('x/abx/Foo.php'));
    }

    public function testCharSlashMultiCharGlobstarWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'x/ab**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('x/abx/Foo.php'));
    }

    public function testSlashCharSlashSingleCharGlobstarNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/x/a**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/x/Foo.php'));
    }

    public function testSlashCharSlashSingleCharGlobstarWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/x/a**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/x/Foo.php'));
    }

    public function testSlashCharSlashMultiCharGlobstarNoTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/x/ab**', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/x/abx/Foo.php'));
    }

    public function testSlashCharSlashMultiCharGlobstarWithTrailingSlash(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/x/ab**/', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/x/abx/Foo.php'));
    }

    // Additional globstar combinations with text after
    public function testGlobstarFollowedByChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testSlashGlobstarFollowedByChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testSingleCharGlobstarFollowedByChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'x**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testMultiCharGlobstarFollowedByChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'xy**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('xyz/a/Foo.php'));
    }

    public function testSlashSingleCharGlobstarFollowedByChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/x**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/a/Foo.php'));
    }

    public function testSlashMultiCharGlobstarFollowedByChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '/xy**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/xyz/a/Foo.php'));
    }

    public function testGlobstarFollowedBySlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('x/a/Foo.php'));
    }

    public function testSingleCharGlobstarFollowedBySlashChar(): void
    {
        $filter = (new Builder)->build(
            [['path' => 'x**/a', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('y/a/Foo.php'));
    }

    public function testGlobstarFollowedByStarAtEnd(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/*', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }

    public function testGlobstarFollowedByQuestionAtEnd(): void
    {
        $filter = (new Builder)->build(
            [['path' => '**/?', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('a/Foo.php'));
    }
}
