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
use PHPUnit\Framework\TestCase;

#[CoversClass(Filter::class)]
#[Small]
final class FilterTest extends TestCase
{
    public function testAcceptsFileMatchingIncludeDirectoryMatcher(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
    }

    public function testAcceptsFileInNestedDirectoryMatchingIncludeDirectoryMatcher(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/subdir/Foo.php'));
    }

    public function testRejectsFileNotMatchingAnyIncludeDirectoryMatcher(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/tests/FooTest.php'));
    }

    public function testAcceptsFileInIncludeFilesMap(): void
    {
        $filter = new Filter(
            [],
            ['/config/bootstrap.php' => true],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/config/bootstrap.php'));
    }

    public function testRejectsFileNotInIncludeFilesMap(): void
    {
        $filter = new Filter(
            [],
            ['/config/bootstrap.php' => true],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/config/other.php'));
    }

    public function testRejectsFileInExcludeFilesMap(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            ['/src/Legacy.php' => true],
        );

        $this->assertFalse($filter->accepts('/src/Legacy.php'));
    }

    public function testRejectsFileMatchingExcludeDirectoryMatcher(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [['regularExpression' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
        );

        $this->assertFalse($filter->accepts('/src/vendor/Package.php'));
    }

    public function testExcludeFilesMapTakesPrecedenceOverIncludeDirectoryMatcher(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            ['/src/Excluded.php' => true],
        );

        $this->assertTrue($filter->accepts('/src/Included.php'));
        $this->assertFalse($filter->accepts('/src/Excluded.php'));
    }

    public function testExcludeDirectoryMatcherTakesPrecedenceOverIncludeFilesMap(): void
    {
        $filter = new Filter(
            [],
            ['/src/vendor/Special.php' => true],
            [['regularExpression' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
        );

        $this->assertFalse($filter->accepts('/src/vendor/Special.php'));
    }

    public function testRejectsFilesInHiddenDirectories(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/.hidden/File.php'));
    }

    public function testAcceptsFilesInHiddenDirectoriesWhenExcludeHiddenDirectoriesIsFalse(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
            false,
        );

        $this->assertTrue($filter->accepts('/src/.hidden/File.php'));
    }

    public function testRejectsFilesInNestedHiddenDirectories(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/subdir/.hidden/File.php'));
    }

    public function testRejectsFilesInGitDirectory(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/.git/config'));
    }

    public function testRejectsHiddenFilesInDirectory(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/.htaccess'));
    }

    public function testAcceptsFilesWithDotInMiddleOfFilename(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/config.local.php'));
    }

    public function testMatcherWithPrefixRequirement(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Test', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/TestFoo.php'));
        $this->assertFalse($filter->accepts('/src/Foo.php'));
    }

    public function testMatcherWithSuffixRequirement(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/config.xml'));
    }

    public function testMatcherWithPrefixAndSuffixRequirement(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/tests(?:/|$)#', 'prefix' => 'Test', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/tests/TestFoo.php'));
        $this->assertFalse($filter->accepts('/tests/Foo.php'));
        $this->assertFalse($filter->accepts('/tests/TestFoo.xml'));
    }

    public function testExcludeMatcherWithPrefixAndSuffix(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
            [],
            [['regularExpression' => '#^/src/generated(?:/|$)#', 'prefix' => 'Gen', 'suffix' => '.php']],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/src/generated/Manual.php'));
        $this->assertFalse($filter->accepts('/src/generated/GenClass.php'));
    }

    public function testMultipleIncludeDirectoryMatchers(): void
    {
        $filter = new Filter(
            [
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => ''],
                ['regularExpression' => '#^/lib(?:/|$)#', 'prefix' => '', 'suffix' => ''],
            ],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/lib/Bar.php'));
        $this->assertFalse($filter->accepts('/tests/FooTest.php'));
    }

    public function testMultipleExcludeDirectoryMatchers(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [
                ['regularExpression' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => ''],
                ['regularExpression' => '#^/src/cache(?:/|$)#', 'prefix' => '', 'suffix' => ''],
            ],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/vendor/Package.php'));
        $this->assertFalse($filter->accepts('/src/cache/data.php'));
    }

    public function testMultipleIncludeFiles(): void
    {
        $filter = new Filter(
            [],
            [
                '/config/bootstrap.php' => true,
                '/scripts/setup.php'    => true,
            ],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/config/bootstrap.php'));
        $this->assertTrue($filter->accepts('/scripts/setup.php'));
        $this->assertFalse($filter->accepts('/other/file.php'));
    }

    public function testMultipleExcludeFiles(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [
                '/src/Legacy.php'     => true,
                '/src/Deprecated.php' => true,
            ],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/Legacy.php'));
        $this->assertFalse($filter->accepts('/src/Deprecated.php'));
    }

    public function testEmptyConfigurationRejectsAllFiles(): void
    {
        $filter = new Filter([], [], [], []);

        $this->assertFalse($filter->accepts('/any/path/file.php'));
    }

    public function testRegexPatternWithGlobstar(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src/(?:[^/]+/)*tests(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/tests/FooTest.php'));
        $this->assertTrue($filter->accepts('/src/app/tests/FooTest.php'));
        $this->assertTrue($filter->accepts('/src/app/module/tests/FooTest.php'));
        $this->assertFalse($filter->accepts('/src/app/Foo.php'));
    }

    public function testRegexPatternWithSingleStar(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src/[^/]*/models(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/app/models/User.php'));
        $this->assertFalse($filter->accepts('/src/app/deep/models/User.php'));
    }

    public function testRegexPatternWithQuestionMark(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src/v[^/]/api(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/v1/api/Handler.php'));
        $this->assertFalse($filter->accepts('/src/v10/api/Handler.php'));
    }

    public function testCombinationOfIncludeDirectoriesAndFiles(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
            ['/config/bootstrap.php' => true],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertTrue($filter->accepts('/config/bootstrap.php'));
        $this->assertFalse($filter->accepts('/config/other.php'));
    }

    public function testCombinationOfExcludeDirectoriesAndFiles(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [['regularExpression' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            ['/src/Legacy.php' => true],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/vendor/Package.php'));
        $this->assertFalse($filter->accepts('/src/Legacy.php'));
    }

    public function testFirstMatchingIncludeDirectoryMatcherWins(): void
    {
        $filter = new Filter(
            [
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'A', 'suffix' => ''],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'B', 'suffix' => ''],
            ],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/AFile.php'));
        $this->assertTrue($filter->accepts('/src/BFile.php'));
    }

    public function testExcludeFilesCheckedBeforeIncludeFiles(): void
    {
        $filter = new Filter(
            [],
            ['/src/file.php' => true],
            [],
            ['/src/file.php' => true],
        );

        $this->assertFalse($filter->accepts('/src/file.php'));
    }

    public function testHiddenDirectoryCheckHappensFirst(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            ['/src/.hidden/explicit.php' => true],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/.hidden/explicit.php'));
    }

    public function testExplicitlyIncludedFileInHiddenDirectoryAcceptedWhenExcludeHiddenDirectoriesIsFalse(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            ['/src/.hidden/explicit.php' => true],
            [],
            [],
            false,
        );

        $this->assertTrue($filter->accepts('/src/.hidden/explicit.php'));
    }

    public function testDeepNestedHiddenDirectory(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/a/b/c/.hidden/d/e/file.php'));
    }

    public function testMatcherWithOnlyPrefix(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Abstract', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/AbstractClass.php'));
        $this->assertTrue($filter->accepts('/src/AbstractClass.xml'));
        $this->assertFalse($filter->accepts('/src/ConcreteClass.php'));
    }

    public function testMatcherWithOnlySuffix(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => 'Test.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/FooTest.php'));
        $this->assertFalse($filter->accepts('/src/Foo.php'));
        $this->assertFalse($filter->accepts('/src/FooTest.xml'));
    }

    public function testDirectoryAtRootLevel(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/bootstrap.php'));
        $this->assertFalse($filter->accepts('/config.xml'));
    }

    public function testRootDirectoryDoesNotMatchSubdirectories(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/Foo.php'));
    }

    public function testIncludeFileWithEmptyExcludeDirectoryMatchers(): void
    {
        $filter = new Filter(
            [],
            ['/config/bootstrap.php' => true],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/config/bootstrap.php'));
    }

    public function testIncludeFileNotExcludedByDirectoryMatcherWhenDirectoryDoesNotMatch(): void
    {
        $filter = new Filter(
            [],
            ['/other/file.php' => true],
            [['regularExpression' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
        );

        $this->assertTrue($filter->accepts('/other/file.php'));
    }

    public function testIncludeFileNotExcludedByDirectoryMatcherWhenPrefixDoesNotMatch(): void
    {
        $filter = new Filter(
            [],
            ['/src/vendor/file.php' => true],
            [['regularExpression' => '#^/src/vendor(?:/|$)#', 'prefix' => 'Gen', 'suffix' => '']],
            [],
        );

        $this->assertTrue($filter->accepts('/src/vendor/file.php'));
    }

    public function testIncludeFileNotExcludedByDirectoryMatcherWhenSuffixDoesNotMatch(): void
    {
        $filter = new Filter(
            [],
            ['/src/vendor/file.xml' => true],
            [['regularExpression' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
            [],
        );

        $this->assertTrue($filter->accepts('/src/vendor/file.xml'));
    }

    public function testIncludeDirectoryMatcherWithPrefixMatchAndEmptySuffix(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Foo', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/FooBar.php'));
    }

    public function testExcludeDirectoryMatcherWithPrefixMatchButSuffixNotMatching(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Foo', 'suffix' => '.xml']],
            [],
        );

        $this->assertTrue($filter->accepts('/src/FooBar.php'));
    }

    public function testExcludeDirectoryMatcherWithDirectoryNotMatchingButOtherConditionsMet(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [['regularExpression' => '#^/other(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
    }

    public function testMultipleMatchersWhereFirstDoesNotMatchDirectoryButSecondDoes(): void
    {
        $filter = new Filter(
            [
                ['regularExpression' => '#^/other(?:/|$)#', 'prefix' => '', 'suffix' => ''],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => ''],
            ],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
    }

    public function testMultipleMatchersWhereFirstMatchesDirectoryButNotPrefix(): void
    {
        $filter = new Filter(
            [
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Bar', 'suffix' => ''],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Foo', 'suffix' => ''],
            ],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/FooClass.php'));
    }

    public function testMultipleMatchersWhereFirstMatchesDirectoryAndPrefixButNotSuffix(): void
    {
        $filter = new Filter(
            [
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Foo', 'suffix' => '.xml'],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Foo', 'suffix' => '.php'],
            ],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/FooClass.php'));
    }

    public function testExcludeMatcherFirstDoesNotMatchDirectorySecondMatchesAll(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [
                ['regularExpression' => '#^/other(?:/|$)#', 'prefix' => '', 'suffix' => ''],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => ''],
            ],
            [],
        );

        $this->assertFalse($filter->accepts('/src/Foo.php'));
    }

    public function testExcludeMatcherFirstMatchesDirectoryButNotPrefixSecondMatchesAll(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Bar', 'suffix' => ''],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => ''],
            ],
            [],
        );

        $this->assertFalse($filter->accepts('/src/FooClass.php'));
    }

    public function testExcludeMatcherFirstMatchesDirAndPrefixButNotSuffixSecondMatchesAll(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Foo', 'suffix' => '.xml'],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => ''],
            ],
            [],
        );

        $this->assertFalse($filter->accepts('/src/FooClass.php'));
    }

    public function testIncludeFileExcludedByFirstMatcherAfterSecondDoesNotMatch(): void
    {
        $filter = new Filter(
            [],
            ['/src/FooClass.php' => true],
            [
                ['regularExpression' => '#^/other(?:/|$)#', 'prefix' => '', 'suffix' => ''],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => ''],
            ],
            [],
        );

        $this->assertFalse($filter->accepts('/src/FooClass.php'));
    }

    public function testIncludeFileExcludedByMatcherAfterPrefixCheckFails(): void
    {
        $filter = new Filter(
            [],
            ['/src/FooClass.php' => true],
            [
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Bar', 'suffix' => ''],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => ''],
            ],
            [],
        );

        $this->assertFalse($filter->accepts('/src/FooClass.php'));
    }

    public function testIncludeFileExcludedByMatcherAfterSuffixCheckFails(): void
    {
        $filter = new Filter(
            [],
            ['/src/FooClass.php' => true],
            [
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Foo', 'suffix' => '.xml'],
                ['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => ''],
            ],
            [],
        );

        $this->assertFalse($filter->accepts('/src/FooClass.php'));
    }

    public function testNonHiddenDirectoryWithDotInName(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/app.module/Foo.php'));
    }

    public function testMatcherWithEmptyPrefixAndNonEmptySuffixThatMatches(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
    }

    public function testMatcherWithNonEmptyPrefixThatMatchesAndEmptySuffix(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Test', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/TestFoo.php'));
    }

    public function testMatcherWithBothPrefixAndSuffixMatching(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => 'Test', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/TestFoo.php'));
    }

    public function testMatcherWithEmptyPrefixAndEmptySuffix(): void
    {
        $filter = new Filter(
            [['regularExpression' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/AnyFile.txt'));
    }
}
