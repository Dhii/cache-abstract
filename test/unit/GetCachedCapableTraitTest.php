<?php

namespace Dhii\Cache\UnitTest;

use Dhii\Cache\GetCachedCapableTrait as TestSubject;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Xpmock\TestCase;
use Exception as RootException;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_MockObject_MockBuilder as MockBuilder;

/**
 * Tests {@see TestSubject}.
 *
 * @since [*next-version*]
 */
class GetCachedCapableTraitTest extends TestCase
{
    /**
     * The class name of the test subject.
     *
     * @since [*next-version*]
     */
    const TEST_SUBJECT_CLASSNAME = 'Dhii\Cache\GetCachedCapableTrait';

    /**
     * Creates a new instance of the test subject.
     *
     * @since [*next-version*]
     *
     * @param array $methods The methods to mock.
     *
     * @return MockObject The new instance.
     */
    public function createInstance($methods = [])
    {
        is_array($methods) && $methods = $this->mergeValues($methods, [
            '__',
        ]);

        $mock = $this->getMockBuilder(static::TEST_SUBJECT_CLASSNAME)
            ->setMethods($methods)
            ->getMockForTrait();

        $mock->method('__')
                ->will($this->returnArgument(0));

        return $mock;
    }

    /**
     * Merges the values of two arrays.
     *
     * The resulting product will be a numeric array where the values of both inputs are present, without duplicates.
     *
     * @since [*next-version*]
     *
     * @param array $destination The base array.
     * @param array $source      The array with more keys.
     *
     * @return array The array which contains unique values
     */
    public function mergeValues($destination, $source)
    {
        return array_keys(array_merge(array_flip($destination), array_flip($source)));
    }

    /**
     * Creates a mock that both extends a class and implements interfaces.
     *
     * This is particularly useful for cases where the mock is based on an
     * internal class, such as in the case with exceptions. Helps to avoid
     * writing hard-coded stubs.
     *
     * @since [*next-version*]
     *
     * @param string   $className      Name of the class for the mock to extend.
     * @param string[] $interfaceNames Names of the interfaces for the mock to implement.
     *
     * @return MockBuilder The builder for a mock of an object that extends and implements
     *                     the specified class and interfaces.
     */
    public function mockClassAndInterfaces($className, $interfaceNames = [])
    {
        $paddingClassName = uniqid($className);
        $definition = vsprintf('abstract class %1$s extends %2$s implements %3$s {}', [
            $paddingClassName,
            $className,
            implode(', ', $interfaceNames),
        ]);
        eval($definition);

        return $this->getMockBuilder($paddingClassName);
    }

    /**
     * Creates a mock that uses traits.
     *
     * This is particularly useful for testing integration between multiple traits.
     *
     * @since [*next-version*]
     *
     * @param string[] $traitNames Names of the traits for the mock to use.
     *
     * @return MockBuilder The builder for a mock of an object that uses the traits.
     */
    public function mockTraits($traitNames = [])
    {
        $paddingClassName = uniqid('Traits');
        $definition = vsprintf('abstract class %1$s {%2$s}', [
            $paddingClassName,
            implode(
                ' ',
                array_map(
                    function ($v) {
                        return vsprintf('use %1$s;', [$v]);
                    },
                    $traitNames)),
        ]);
        var_dump($definition);
        eval($definition);

        return $this->getMockBuilder($paddingClassName);
    }

    /**
     * Creates a new invocable object.
     *
     * @since [*next-version*]
     *
     * @return MockObject An object that has an `__invoke()` method.
     */
    public function createCallable()
    {
        $mock = $this->getMockBuilder('MyCallable')
            ->setMethods(['__invoke'])
            ->getMock();

        return $mock;
    }

    /**
     * Creates a new exception.
     *
     * @since [*next-version*]
     *
     * @param string $message The exception message.
     *
     * @return RootException|MockObject The new exception.
     */
    public function createException($message = '')
    {
        $mock = $this->getMockBuilder('Exception')
            ->setConstructorArgs([$message])
            ->getMock();

        return $mock;
    }

    /**
     * Creates a new Runtime exception.
     *
     * @since [*next-version*]
     *
     * @param string $message The exception message.
     *
     * @return RuntimeException|MockObject The new exception.
     */
    public function createRuntimeException($message = '')
    {
        $mock = $this->getMockBuilder('RuntimeException')
            ->setConstructorArgs([$message])
            ->getMock();

        return $mock;
    }

    /**
     * Creates a new Not Found exception.
     *
     * @since [*next-version*]
     *
     * @param string $message The exception message.
     *
     * @return RootException|ContainerExceptionInterface|MockObject
     */
    public function createNotFoundException($message = '')
    {
        $mock = $this->mockClassAndInterfaces('Exception', ['Psr\Container\NotFoundExceptionInterface'])
            ->setConstructorArgs([$message])
            ->getMock();

        return $mock;
    }

    /**
     * Tests whether a valid instance of the test subject can be created.
     *
     * @since [*next-version*]
     */
    public function testCanBeCreated()
    {
        $subject = $this->createInstance();

        $this->assertInternalType(
            'object',
            $subject,
            'A valid instance of the test subject could not be created.'
        );
    }

    /**
     * Tests that `_getCached()` works as expected when a cached value exists.
     *
     * @since [*next-version*]
     */
    public function testGetCached()
    {
        $key = uniqid('key');
        $val = uniqid('val');
        $subject = $this->createInstance(['_get']);
        $_subject = $this->reflect($subject);

        $subject->expects($this->exactly(1))
            ->method('_get')
            ->with($key)
            ->will($this->returnValue($val));

        $result = $_subject->_get($key);
        $this->assertEquals($val, $result, 'Wrong value retrieved');
    }

    /**
     * Tests that `_getCached()` works as expected when no cached value exists, and the generator needs to be invoked.
     *
     * @since [*next-version*]
     */
    public function testGetCachedGenerated()
    {
        $key = uniqid('key');
        $val = uniqid('val');
        $ttl = rand(1, 999999);
        $generator = $this->createCallable();
        $genArgs = [$key, $ttl];
        $subject = $this->createInstance(['_get', '_normalizeArray', '_getGeneratorArgs', '_invokeCallable', '_set']);
        $_subject = $this->reflect($subject);
        $exception = $this->createNotFoundException('No such value in cache');

        $subject->expects($this->exactly(1))
            ->method('_get')
            ->with($key)
            ->will($this->throwException($exception));
        $subject->expects($this->exactly(1))
            ->method('_getGeneratorArgs')
            ->with($key, $generator, $ttl)
            ->will($this->returnValue($genArgs));
        $subject->expects($this->exactly(1))
            ->method('_normalizeArray')
            ->with($genArgs)
            ->will($this->returnArgument(0));
        $subject->expects($this->exactly(1))
            ->method('_invokeCallable')
            ->with($generator, $genArgs)
            ->will($this->returnValue($val));
        $subject->expects($this->exactly(1))
            ->method('_set')
            ->with($key, $val, $ttl);

        $result = $_subject->_getCached($key, $generator, $ttl);
        $this->assertEquals($val, $result, 'Wrong generated result');
    }

    /**
     * Tests that `_getCached()` works as expected when no cached value exists, and the generator is a scalar.
     *
     * @since [*next-version*]
     */
    public function testGetCachedDefault()
    {
        $key = uniqid('key');
        $ttl = rand(1, 999999);
        $default = uniqid('default');
        $subject = $this->createInstance(['_get', '_set']);
        $_subject = $this->reflect($subject);
        $exception = $this->createNotFoundException('No such value in cache');

        $subject->expects($this->exactly(1))
            ->method('_get')
            ->with($key)
            ->will($this->throwException($exception));
        $subject->expects($this->exactly(1))
            ->method('_set')
            ->with($key, $default, $ttl);

        $result = $_subject->_getCached($key, $default, $ttl);
        $this->assertEquals($default, $result, 'Wrong default result');
    }

    /**
     * Test that `_getCached()` fails as expected when an exception is thrown during generation.
     *
     * @since [*next-version*]
     */
    public function testGetCachedGenerateException()
    {
        $key = uniqid('key');
        $generator = function () {};
        $ttl = rand(1, 999999);
        $notFoundException = $this->createNotFoundException('No such value in cache');
        $generationException = $this->createException('Problem during generation');
        $runtimeException = $this->createRuntimeException('Problem during generation');

        $subject = $this->createInstance();
        $_subject = $this->reflect($subject);

        $subject->expects($this->exactly(1))
            ->method('_get')
            ->with($key)
            ->will($this->throwException($notFoundException));
        $subject->expects($this->exactly(1))
            ->method('_getGeneratorArgs')
            ->will($this->throwException($generationException));
        $subject->expects($this->exactly(1))
            ->method('_createRuntimeException')
            ->with(
                $this->isType('string'),
                null,
                $generationException
            )
            ->will($this->returnValue($runtimeException));

        $this->setExpectedException('RuntimeException');
        $_subject->_getCached($key, $generator, $ttl);
    }
}
