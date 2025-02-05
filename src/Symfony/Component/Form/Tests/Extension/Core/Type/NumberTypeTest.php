<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Form\Tests\Extension\Core\Type;

use Symfony\Bridge\PhpUnit\ForwardCompatTestTrait;
use Symfony\Component\Intl\Util\IntlTestHelper;

class NumberTypeTest extends BaseTypeTest
{
    use ForwardCompatTestTrait;

    const TESTED_TYPE = 'Symfony\Component\Form\Extension\Core\Type\NumberType';

    private $defaultLocale;

    private function doSetUp()
    {
        parent::setUp();

        // we test against "de_DE", so we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        $this->defaultLocale = \Locale::getDefault();
        \Locale::setDefault('de_DE');
    }

    private function doTearDown()
    {
        parent::tearDown();

        \Locale::setDefault($this->defaultLocale);
    }

    public function testDefaultFormatting(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE);
        $form->setData('12345.67890');

        $this->assertSame('12345,679', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithGrouping(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['grouping' => true]);
        $form->setData('12345.67890');

        $this->assertSame('12.345,679', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithScale(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['scale' => 2]);
        $form->setData('12345.67890');

        $this->assertSame('12345,68', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithScaleFloat(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['scale' => 2]);
        $form->setData(12345.67890);

        $this->assertSame('12345,68', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithScaleAndStringInput(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['scale' => 2, 'input' => 'string']);
        $form->setData('12345.67890');

        $this->assertSame('12345,68', $form->createView()->vars['value']);
    }

    /**
     * @group legacy
     * @expectedDeprecation Using the Symfony\Component\Form\Extension\Core\Type\NumberType with float or int data when the "input" option is set to "string" is deprecated since Symfony 4.4 and will throw an exception in 5.0.
     */
    public function testStringInputWithFloatData(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, 12345.6789, [
            'input' => 'string',
            'scale' => 2,
        ]);

        $this->assertSame('12345,68', $form->createView()->vars['value']);
    }

    /**
     * @group legacy
     * @expectedDeprecation Using the Symfony\Component\Form\Extension\Core\Type\NumberType with float or int data when the "input" option is set to "string" is deprecated since Symfony 4.4 and will throw an exception in 5.0.
     */
    public function testStringInputWithIntData(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, 12345, [
            'input' => 'string',
            'scale' => 2,
        ]);

        $this->assertSame('12345,00', $form->createView()->vars['value']);
    }

    public function testDefaultFormattingWithRounding(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['scale' => 0, 'rounding_mode' => \NumberFormatter::ROUND_UP]);
        $form->setData('12345.54321');

        $this->assertSame('12346', $form->createView()->vars['value']);
    }

    public function testSubmitNull($expected = null, $norm = null, $view = null)
    {
        parent::testSubmitNull($expected, $norm, '');
    }

    public function testSubmitNullUsesDefaultEmptyData($emptyData = '10', $expectedData = 10.0)
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, [
            'empty_data' => $emptyData,
        ]);
        $form->submit(null);

        $this->assertSame($emptyData, $form->getViewData());
        $this->assertSame($expectedData, $form->getNormData());
        $this->assertSame($expectedData, $form->getData());
    }

    public function testSubmitNumericInput(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['input' => 'number']);
        $form->submit('1,234');

        $this->assertSame(1.234, $form->getData());
        $this->assertSame(1.234, $form->getNormData());
        $this->assertSame('1,234', $form->getViewData());
    }

    public function testSubmitNumericInputWithScale(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['input' => 'number', 'scale' => 2]);
        $form->submit('1,234');

        $this->assertSame(1.23, $form->getData());
        $this->assertSame(1.23, $form->getNormData());
        $this->assertSame('1,23', $form->getViewData());
    }

    public function testSubmitStringInput(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['input' => 'string']);
        $form->submit('1,234');

        $this->assertSame('1.234', $form->getData());
        $this->assertSame(1.234, $form->getNormData());
        $this->assertSame('1,234', $form->getViewData());
    }

    public function testSubmitStringInputWithScale(): void
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, ['input' => 'string', 'scale' => 2]);
        $form->submit('1,234');

        $this->assertSame('1.23', $form->getData());
        $this->assertSame(1.23, $form->getNormData());
        $this->assertSame('1,23', $form->getViewData());
    }

    public function testIgnoresDefaultLocaleToRenderHtml5NumberWidgets()
    {
        $form = $this->factory->create(static::TESTED_TYPE, null, [
            'scale' => 2,
            'rounding_mode' => \NumberFormatter::ROUND_UP,
            'html5' => true,
        ]);
        $form->setData(12345.54321);

        $this->assertSame('12345.55', $form->createView()->vars['value']);
        $this->assertSame('12345.55', $form->getViewData());
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\LogicException
     */
    public function testGroupingNotAllowedWithHtml5Widget()
    {
        $this->factory->create(static::TESTED_TYPE, null, [
            'grouping' => true,
            'html5' => true,
        ]);
    }
}
