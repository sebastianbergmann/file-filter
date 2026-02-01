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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/Foo.php'));
    }

    public function testAcceptsFileInNestedDirectoryMatchingIncludeDirectoryMatcher(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/subdir/Foo.php'));
    }

    public function testRejectsFileNotMatchingAnyIncludeDirectoryMatcher(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            ['/src/Legacy.php' => true],
        );

        $this->assertFalse($filter->accepts('/src/Legacy.php'));
    }

    public function testRejectsFileMatchingExcludeDirectoryMatcher(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [['regex' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
        );

        $this->assertFalse($filter->accepts('/src/vendor/Package.php'));
    }

    public function testExcludeFilesMapTakesPrecedenceOverIncludeDirectoryMatcher(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
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
            [['regex' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
        );

        $this->assertFalse($filter->accepts('/src/vendor/Special.php'));
    }

    public function testRejectsFilesInHiddenDirectories(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/.hidden/File.php'));
    }

    public function testRejectsFilesInNestedHiddenDirectories(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/subdir/.hidden/File.php'));
    }

    public function testRejectsFilesInGitDirectory(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/.git/config'));
    }

    public function testRejectsHiddenFilesInDirectory(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/.htaccess'));
    }

    public function testAcceptsFilesWithDotInMiddleOfFilename(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertTrue($filter->accepts('/src/config.local.php'));
    }

    public function testMatcherWithPrefixRequirement(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => 'Test', 'suffix' => '']],
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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
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
            [['regex' => '#^/tests(?:/|$)#', 'prefix' => 'Test', 'suffix' => '.php']],
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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
            [],
            [['regex' => '#^/src/generated(?:/|$)#', 'prefix' => 'Gen', 'suffix' => '.php']],
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
                ['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => ''],
                ['regex' => '#^/lib(?:/|$)#', 'prefix' => '', 'suffix' => ''],
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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [
                ['regex' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => ''],
                ['regex' => '#^/src/cache(?:/|$)#', 'prefix' => '', 'suffix' => ''],
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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
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
            [['regex' => '#^/src/(?:[^/]+/)*tests(?:/|$)#', 'prefix' => '', 'suffix' => '']],
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
            [['regex' => '#^/src/[^/]*/models(?:/|$)#', 'prefix' => '', 'suffix' => '']],
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
            [['regex' => '#^/src/v[^/]/api(?:/|$)#', 'prefix' => '', 'suffix' => '']],
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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [['regex' => '#^/src/vendor(?:/|$)#', 'prefix' => '', 'suffix' => '']],
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
                ['regex' => '#^/src(?:/|$)#', 'prefix' => 'A', 'suffix' => ''],
                ['regex' => '#^/src(?:/|$)#', 'prefix' => 'B', 'suffix' => ''],
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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            ['/src/.hidden/explicit.php' => true],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/.hidden/explicit.php'));
    }

    public function testDeepNestedHiddenDirectory(): void
    {
        $filter = new Filter(
            [['regex' => '#^/(?:/|$)#', 'prefix' => '', 'suffix' => '']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/a/b/c/.hidden/d/e/file.php'));
    }

    public function testMatcherWithOnlyPrefix(): void
    {
        $filter = new Filter(
            [['regex' => '#^/src(?:/|$)#', 'prefix' => 'Abstract', 'suffix' => '']],
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
            [['regex' => '#^/src(?:/|$)#', 'prefix' => '', 'suffix' => 'Test.php']],
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
            [['regex' => '#^/(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
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
            [['regex' => '#^/(?:/|$)#', 'prefix' => '', 'suffix' => '.php']],
            [],
            [],
            [],
        );

        $this->assertFalse($filter->accepts('/src/Foo.php'));
    }
}
