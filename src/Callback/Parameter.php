<?php

namespace Zenstruck\Callback;

use Zenstruck\Callback\Exception\UnresolveableArgument;
use Zenstruck\Callback\Parameter\TypedParameter;
use Zenstruck\Callback\Parameter\UnionParameter;
use Zenstruck\Callback\Parameter\UntypedParameter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Parameter
{
    final public static function union(self ...$parameters): self
    {
        return new UnionParameter(...$parameters);
    }

    final public static function typed(string $type, $value): self
    {
        return new TypedParameter($type, $value);
    }

    final public static function untyped($value): self
    {
        return new UntypedParameter($value);
    }

    final public static function factory(callable $factory): ValueFactory
    {
        return new ValueFactory($factory);
    }

    /**
     * @return mixed
     *
     * @throws UnresolveableArgument
     */
    final public function resolve(\ReflectionParameter $parameter)
    {
        $value = $this->valueFor($parameter);

        if (!$value instanceof ValueFactory) {
            return $value;
        }

        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return $value(null);
        }

        return $value($type->getName());
    }

    abstract public function type(): string;

    abstract protected function valueFor(\ReflectionParameter $refParameter);
}
