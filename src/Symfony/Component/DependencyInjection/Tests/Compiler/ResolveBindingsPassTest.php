<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Warning;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Compiler\AutowireRequiredMethodsPass;
use Symfony\Component\DependencyInjection\Compiler\ResolveBindingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Tests\Fixtures\CaseSensitiveClass;
use Symfony\Component\DependencyInjection\Tests\Fixtures\NamedArgumentsDummy;
use Symfony\Component\DependencyInjection\Tests\Fixtures\ParentNotExists;
use Symfony\Component\DependencyInjection\TypedReference;

require_once __DIR__.'/../Fixtures/includes/autowiring_classes.php';

class ResolveBindingsPassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $bindings = [CaseSensitiveClass::class => new BoundArgument(new Reference('foo'))];

        $definition = $container->register(NamedArgumentsDummy::class, NamedArgumentsDummy::class);
        $definition->setArguments([1 => '123']);
        $definition->addMethodCall('setSensitiveClass');
        $definition->setBindings($bindings);

        $container->register('foo', CaseSensitiveClass::class)
            ->setBindings($bindings);

        $pass = new ResolveBindingsPass();
        $pass->process($container);

        $this->assertEquals([new Reference('foo'), '123'], $definition->getArguments());
        $this->assertEquals([['setSensitiveClass', [new Reference('foo')]]], $definition->getMethodCalls());
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage A binding is configured for an argument named "$quz" for service "Symfony\Component\DependencyInjection\Tests\Fixtures\NamedArgumentsDummy", but no corresponding argument has been found. It may be unused and should be removed, or it may have a typo.
     */
    public function testUnusedBinding()
    {
        $container = new ContainerBuilder();

        $definition = $container->register(NamedArgumentsDummy::class, NamedArgumentsDummy::class);
        $definition->setBindings(['$quz' => '123']);

        $pass = new ResolveBindingsPass();
        $pass->process($container);
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegexp Unused binding "$quz" in service [\s\S]+ Invalid service ".*\\ParentNotExists": class NotExists not found\.
     */
    public function testMissingParent()
    {
        if (\PHP_VERSION_ID >= 70400) {
            throw new Warning('PHP 7.4 breaks this test, see https://bugs.php.net/78351.');
        }

        $container = new ContainerBuilder();

        $definition = $container->register(ParentNotExists::class, ParentNotExists::class);
        $definition->setBindings(['$quz' => '123']);

        $pass = new ResolveBindingsPass();
        $pass->process($container);
    }

    public function testTypedReferenceSupport()
    {
        $container = new ContainerBuilder();

        $bindings = [CaseSensitiveClass::class => new BoundArgument(new Reference('foo'))];

        // Explicit service id
        $definition1 = $container->register('def1', NamedArgumentsDummy::class);
        $definition1->addArgument($typedRef = new TypedReference('bar', CaseSensitiveClass::class));
        $definition1->setBindings($bindings);

        $definition2 = $container->register('def2', NamedArgumentsDummy::class);
        $definition2->addArgument(new TypedReference(CaseSensitiveClass::class, CaseSensitiveClass::class));
        $definition2->setBindings($bindings);

        $pass = new ResolveBindingsPass();
        $pass->process($container);

        $this->assertEquals([$typedRef], $container->getDefinition('def1')->getArguments());
        $this->assertEquals([new Reference('foo')], $container->getDefinition('def2')->getArguments());
    }

    public function testScalarSetter()
    {
        $container = new ContainerBuilder();

        $definition = $container->autowire('foo', ScalarSetter::class);
        $definition->setBindings(['$defaultLocale' => 'fr']);

        (new AutowireRequiredMethodsPass())->process($container);
        (new ResolveBindingsPass())->process($container);

        $this->assertEquals([['setDefaultLocale', ['fr']]], $definition->getMethodCalls());
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     * @exceptedExceptionMessage Invalid service "Symfony\Component\DependencyInjection\Tests\Fixtures\NamedArgumentsDummy": method "setLogger()" does not exist.
     */
    public function testWithNonExistingSetterAndBinding()
    {
        $container = new ContainerBuilder();

        $bindings = [
            '$c' => (new Definition('logger'))->setFactory('logger'),
        ];

        $definition = $container->register(NamedArgumentsDummy::class, NamedArgumentsDummy::class);
        $definition->addMethodCall('setLogger');
        $definition->setBindings($bindings);

        $pass = new ResolveBindingsPass();
        $pass->process($container);
    }
}
