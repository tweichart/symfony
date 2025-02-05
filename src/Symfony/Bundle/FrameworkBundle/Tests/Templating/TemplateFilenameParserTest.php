<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Templating;

use Symfony\Bridge\PhpUnit\ForwardCompatTestTrait;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateFilenameParser;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

/**
 * @group legacy
 */
class TemplateFilenameParserTest extends TestCase
{
    use ForwardCompatTestTrait;

    protected $parser;

    private function doSetUp()
    {
        $this->parser = new TemplateFilenameParser();
    }

    private function doTearDown()
    {
        $this->parser = null;
    }

    /**
     * @dataProvider getFilenameToTemplateProvider
     */
    public function testParseFromFilename($file, $ref)
    {
        $template = $this->parser->parse($file);

        if (false === $ref) {
            $this->assertFalse($template);
        } else {
            $this->assertEquals($template->getLogicalName(), $ref->getLogicalName());
        }
    }

    public function getFilenameToTemplateProvider()
    {
        return [
            ['/path/to/section/name.format.engine', new TemplateReference('', '/path/to/section', 'name', 'format', 'engine')],
            ['\\path\\to\\section\\name.format.engine', new TemplateReference('', '/path/to/section', 'name', 'format', 'engine')],
            ['name.format.engine', new TemplateReference('', '', 'name', 'format', 'engine')],
            ['name.format', false],
            ['name', false],
        ];
    }
}
