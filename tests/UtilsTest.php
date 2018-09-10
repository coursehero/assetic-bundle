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
                '/root/a.scss',
                '/root/a.sass',
                '/root/a.css',
                '/root/_a.scss',
                '/root/_a.sass',
                '/root/_a.css'
            ]
        ], $actual);

        $actual = Utils\resolveScssImport(['/root', '/lib/path'], "   @import 'a'");
        $this->assertEquals([
            'a' => [
                '/root/a.scss',
                '/lib/path/a.scss',
                '/root/a.sass',
                '/lib/path/a.sass',
                '/root/a.css',
                '/lib/path/a.css',
                '/root/_a.scss',
                '/lib/path/_a.scss',
                '/root/_a.sass',
                '/lib/path/_a.sass',
                '/root/_a.css',
                '/lib/path/_a.css'
            ]
        ], $actual);

        $actual = Utils\resolveScssImport(['/root'], "@import '_a'");
        $this->assertEquals([
            '_a' => [
                '/root/_a.scss',
                '/root/_a.sass',
                '/root/_a.css'
            ]
        ], $actual);

        $actual = Utils\resolveScssImport(['/root'], "@import 'a.scss'");
        $this->assertEquals([
            'a.scss' => [
               '/root/a.scss',
               '/root/_a.scss'
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
                '/root/nested/a.scss',
                '/root/nested/a.sass',
                '/root/nested/a.css',
                '/root/nested/_a.scss',
                '/root/nested/_a.sass',
                '/root/nested/_a.css'
            ],
            '../b/a' => [
                '/root/b/a.scss',
                '/root/b/a.sass',
                '/root/b/a.css',
                '/root/b/_a.scss',
                '/root/b/_a.sass',
                '/root/b/_a.css'
            ],
            'c.scss' => [
                '/root/nested/c.scss',
                '/root/nested/_c.scss'
            ],
            '_d' => [
                '/root/nested/_d.scss',
                '/root/nested/_d.sass',
                '/root/nested/_d.css'
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
