<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper;

use Symfony\Bridge\PhpUnit\ForwardCompatTestTrait;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\TranslatorHelper;
use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTemplateNameParser;
use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;
use Symfony\Component\Form\Extension\Templating\TemplatingExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Tests\AbstractDivLayoutTest;
use Symfony\Component\Templating\Loader\FilesystemLoader;
use Symfony\Component\Templating\PhpEngine;

/**
 * @group legacy
 */
class FormHelperDivLayoutTest extends AbstractDivLayoutTest
{
    use ForwardCompatTestTrait;

    /**
     * @var PhpEngine
     */
    protected $engine;

    protected static $supportedFeatureSetVersion = 404;

    protected function getExtensions()
    {
        // should be moved to the Form component once absolute file paths are supported
        // by the default name parser in the Templating component
        $reflClass = new \ReflectionClass('Symfony\Bundle\FrameworkBundle\FrameworkBundle');
        $root = realpath(\dirname($reflClass->getFileName()).'/Resources/views');
        $rootTheme = realpath(__DIR__.'/Resources');
        $templateNameParser = new StubTemplateNameParser($root, $rootTheme);
        $loader = new FilesystemLoader([]);

        $this->engine = new PhpEngine($templateNameParser, $loader);
        $this->engine->addGlobal('global', '');
        $this->engine->setHelpers([
            new TranslatorHelper(new StubTranslator()),
        ]);

        return array_merge(parent::getExtensions(), [
            new TemplatingExtension($this->engine, $this->csrfTokenManager, [
                'FrameworkBundle:Form',
            ]),
        ]);
    }

    private function doTearDown()
    {
        $this->engine = null;

        parent::tearDown();
    }

    public function testStartTagHasNoActionAttributeWhenActionIsEmpty()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\FormType', null, [
            'method' => 'get',
            'action' => '',
        ]);

        $html = $this->renderStart($form->createView());

        $this->assertSame('<form name="form" method="get">', $html);
    }

    public function testStartTagHasActionAttributeWhenActionIsZero()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\FormType', null, [
            'method' => 'get',
            'action' => '0',
        ]);

        $html = $this->renderStart($form->createView());

        $this->assertSame('<form name="form" method="get" action="0">', $html);
    }

    public function testMoneyWidgetInIso()
    {
        $this->engine->setCharset('ISO-8859-1');

        $view = $this->factory
            ->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\MoneyType')
            ->createView()
        ;

        $this->assertSame('&euro; <input type="text" id="name" name="name" required="required" />', $this->renderWidget($view));
    }

    public function testHelpAttr()
    {
        $form = $this->factory->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\TextType', null, [
            'help' => 'Help text test!',
            'help_attr' => [
                'class' => 'class-test',
            ],
        ]);
        $view = $form->createView();
        $html = $this->renderHelp($view);

        $this->assertMatchesXpath($html,
            '/p
    [@id="name_help"]
    [@class="class-test help-text"]
    [.="[trans]Help text test![/trans]"]
'
        );
    }

    public function testHelpHtmlDefaultIsFalse()
    {
        $form = $this->factory->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\TextType', null, [
            'help' => 'Help <b>text</b> test!',
        ]);
        $view = $form->createView();
        $html = $this->renderHelp($view);

        $this->assertMatchesXpath($html,
            '/p
    [@id="name_help"]
    [@class="help-text"]
    [.="[trans]Help <b>text</b> test![/trans]"]
'
        );

        $this->assertMatchesXpath($html,
            '/p
    [@id="name_help"]
    [@class="help-text"]
    /b
    [.="text"]
', 0
        );
    }

    public function testHelpHtmlIsFalse()
    {
        $form = $this->factory->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\TextType', null, [
            'help' => 'Help <b>text</b> test!',
            'help_html' => false,
        ]);
        $view = $form->createView();
        $html = $this->renderHelp($view);

        $this->assertMatchesXpath($html,
            '/p
    [@id="name_help"]
    [@class="help-text"]
    [.="[trans]Help <b>text</b> test![/trans]"]
'
        );

        $this->assertMatchesXpath($html,
            '/p
    [@id="name_help"]
    [@class="help-text"]
    /b
    [.="text"]
', 0
        );
    }

    public function testHelpHtmlIsTrue()
    {
        $form = $this->factory->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\TextType', null, [
            'help' => 'Help <b>text</b> test!',
            'help_html' => true,
        ]);
        $view = $form->createView();
        $html = $this->renderHelp($view);

        $this->assertMatchesXpath($html,
            '/p
    [@id="name_help"]
    [@class="help-text"]
    [.="[trans]Help <b>text</b> test![/trans]"]
', 0
        );

        $this->assertMatchesXpath($html,
            '/p
    [@id="name_help"]
    [@class="help-text"]
    /b
    [.="text"]
'
        );
    }

    protected function renderForm(FormView $view, array $vars = [])
    {
        return (string) $this->engine->get('form')->form($view, $vars);
    }

    protected function renderLabel(FormView $view, $label = null, array $vars = [])
    {
        return (string) $this->engine->get('form')->label($view, $label, $vars);
    }

    protected function renderHelp(FormView $view)
    {
        return (string) $this->engine->get('form')->help($view);
    }

    protected function renderErrors(FormView $view)
    {
        return (string) $this->engine->get('form')->errors($view);
    }

    protected function renderWidget(FormView $view, array $vars = [])
    {
        return (string) $this->engine->get('form')->widget($view, $vars);
    }

    protected function renderRow(FormView $view, array $vars = [])
    {
        return (string) $this->engine->get('form')->row($view, $vars);
    }

    protected function renderRest(FormView $view, array $vars = [])
    {
        return (string) $this->engine->get('form')->rest($view, $vars);
    }

    protected function renderStart(FormView $view, array $vars = [])
    {
        return (string) $this->engine->get('form')->start($view, $vars);
    }

    protected function renderEnd(FormView $view, array $vars = [])
    {
        return (string) $this->engine->get('form')->end($view, $vars);
    }

    protected function setTheme(FormView $view, array $themes, $useDefaultThemes = true)
    {
        $this->engine->get('form')->setTheme($view, $themes, $useDefaultThemes);
    }

    public static function themeBlockInheritanceProvider()
    {
        return [
            [['TestBundle:Parent']],
        ];
    }

    public static function themeInheritanceProvider()
    {
        return [
            [['TestBundle:Parent'], ['TestBundle:Child']],
        ];
    }
}
