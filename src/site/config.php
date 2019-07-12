<?php

use Gregwar\RST\Environment;
use Gregwar\RST\Parser;

class PhpMdEnvironment extends Environment
{
    public static $letters = array('=', '-', '`', '~', '*', '^', '"');

    public function reset()
    {
        parent::reset();

        $this->titleLetters = [
            2 => '=',
            3 => '-',
            4 => '`',
            5 => '~',
            6 => '*',
            7 => '^',
            8 => '"',
        ];
    }
}

$changelogContent = file_get_contents(__DIR__.'/../../CHANGELOG');
$parser = new Parser(new PhpMdEnvironment);

return [
    'index' => 'about.html',
    'baseHref' => ltrim(getenv('BASE_HREF') ?: '', ':'),
    'cname' => getenv('CNAME'),
    'websiteDirectory' => __DIR__.'/../../dist/website',
    'sourceDirectory' => __DIR__.'/rst',
    'assetsDirectory' => __DIR__.'/resources/web',
    'layout' => __DIR__.'/resources/layout.php',
    'extensions' => [
        'rst' => function ($file) use ($parser, $changelogContent) {
            $content = file_get_contents($file);
            $content = str_replace(
                '.. include:: ../release/parts/latest.rst',
                $changelogContent,
                $content
            );
            $content = $parser->parse($content);
            // Rewrite links anchors
            $content = preg_replace_callback('/(<a id="[^"]+"><\/a>)\s*<h(?<level>[1-6])([^>]*>)(?<content>[\s\S]*)<\/h\\g<level>>/U', function ($match) {
                $level = $match['level'];
                $content = $match['content'];
                // Use content as anchor
                $hash = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($match['content'])));

                return "<a id=\"$hash\"></a>\n<h$level>$content</h$level>";
            }, $content);
            $content = preg_replace(
                '/phpmd-(\d+\.\S+)/',
                '<a href="https://github.com/phpmd/phpmd/releases/tag/$1" title="$0 release">$0</a>',
                $content
            );

            return $content;
        },
    ],
];
