<?php
declare(strict_types=1);

namespace CourseHero\AsseticBundle\Tests;

use CourseHero\AsseticBundle\Utils;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function testScssResolveImports()
    {
        $actual = Utils\resolveScssImport(['/root'], "   @import 'a'");
        $this->assertEquals([
            'a' => [
                '/root/a.sass',
                '/root/_a.sass',
                '/root/a.scss',
                '/root/_a.scss'
            ]
        ], $actual);

        $actual = Utils\resolveScssImport(['/root', '/lib/path'], "   @import 'a'");
        $this->assertEquals([
            'a' => [
                '/root/a.sass',
                '/lib/path/a.sass',
                '/root/_a.sass',
                '/lib/path/_a.sass',
                '/root/a.scss',
                '/lib/path/a.scss',
                '/root/_a.scss',
                '/lib/path/_a.scss'
            ]
        ], $actual);

        $actual = Utils\resolveScssImport(['/root'], "@import '_a'");
        $this->assertEquals([
            '_a' => [
                '/root/_a.sass',
                '/root/_a.scss'
            ]
        ], $actual);

        $actual = Utils\resolveScssImport(['/root'], "@import 'a.scss'");
        $this->assertEquals([
            'a.scss' => [
               '/root/a.scss'
            ]
        ], $actual);

        $actual = Utils\resolveScssImport(['/root/nested'], '@import 
          "a",
          "../b/a",
          "c.scss",
          "_d"
        ;');
        $this->assertEquals([
            'a' => [
                '/root/nested/a.sass',
                '/root/nested/_a.sass',
                '/root/nested/a.scss',
                '/root/nested/_a.scss'
           ],
           '../b/a' => [
                '/root/b/a.sass',
                '/root/b/_a.sass',
                '/root/b/a.scss',
                '/root/b/_a.scss',
           ],
           'c.scss' => [
                '/root/nested/c.scss',
           ],
           '_d' => [
                '/root/nested/_d.sass',
                '/root/nested/_d.scss'
           ]
       ], $actual);
    }

    public function testRemoveRelPathComponents()
    {
        $this->assertEquals('/', Utils\removeRelPathComponents('/test/folder/../../'));
        $this->assertEquals('/', Utils\removeRelPathComponents('/'));
        $this->assertEquals('test', Utils\removeRelPathComponents('test'));
        $this->assertEquals('/test/folder/tests/b', Utils\removeRelPathComponents('/test/folder/tests/nested/../b/'));
        $this->assertEquals('/test/folder/b', Utils\removeRelPathComponents('/test/folder/tests/nested/../../b/'));
        $this->assertEquals('test/folder/tests/b', Utils\removeRelPathComponents('test/folder/tests/nested/../b/'));
        $this->assertEquals('test/folder/b', Utils\removeRelPathComponents('test/folder/tests/nested/../../b/'));
    }
}
