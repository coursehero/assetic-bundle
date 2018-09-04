<?php

namespace CourseHero\AsseticBundle\Utils;

// references
// https://jonathantneal.github.io/sass-import-resolve/
// http://sass-lang.com/documentation/file.SASS_REFERENCE.html#import
// https://www.npmjs.com/package/sass-import-resolve
function resolveScssImport(array $loadPaths, string $importStatement)
{
    $cleanedStatement = substr($importStatement, strpos($importStatement, '@import') + strlen('@import'));
    $cleanedStatement = rtrim($cleanedStatement, ';');
    $cleanedStatement = trim($cleanedStatement, ' ');
    $splitByComma = explode(',', $cleanedStatement);
    $imports = array_map(function(string $dirtyBase) {
        return trim($dirtyBase, " \n\r\"'");
    }, $splitByComma);

    $resolvedMap = [];
    foreach ($imports as $import) {
        $resolvedBases = [];
        $parts = explode('/', $import);
        $base = array_pop($parts);

        if ($base[0] === '_') {
            array_push($resolvedBases, "$base.sass");
            array_push($resolvedBases, "$base.scss");
        } else if (substr($base, -strlen('.scss')) === '.scss') {
            array_push($resolvedBases, "$base");
        } else {
            array_push($resolvedBases, "$base.sass");
            array_push($resolvedBases, "_$base.sass");
            array_push($resolvedBases, "$base.scss");
            array_push($resolvedBases, "_$base.scss");
        }

        $resolved = [];
        foreach ($resolvedBases as $base) {
            foreach ($loadPaths as $loadPath) {
                $dir = removeRelPathComponents($loadPath . '/' . implode('/', $parts));
                array_push($resolved, "$dir/$base");
            }
        }

        $resolvedMap[$import] = $resolved;
    }

    return $resolvedMap;
}

// https://stackoverflow.com/a/39796579/2788187
function removeRelPathComponents(string $filename): string
{
    $path = [];
    foreach (explode('/', $filename) as $part) {
         // ignore parts that have no value
        if (empty($part) || $part === '.') {
            if (empty($path)) {
                array_push($path, '');
            }
            continue;
        }

        if ($part !== '..') {
            // cool, we found a new part
            array_push($path, $part);
        } elseif (count($path) > 0) {
            // going back up? sure
            array_pop($path);
        } else {
            // now, here we don't like
            throw new \Exception('Climbing above the root is not permitted.');
        }
    }
    
    if (count($path) == 1 && $path[0] == '') {
        return '/';
    }

    return join('/', $path);
}
