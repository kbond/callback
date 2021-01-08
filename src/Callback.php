<?php

namespace Zenstruck;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Callback
{
    /** @var \ReflectionFunction */
    private $function;

    /** @var int */
    private $minArguments = 0;

    /** @var array */
    private $typeReplace = [];

    private function __construct(\ReflectionFunction $function)
    {
        $this->function = $function;
    }

    /**
     * @param callable|\ReflectionFunction $value
     */
    public static function createFor($value): self
    {
        if (\is_callable($value)) {
            $value = new \ReflectionFunction(\Closure::fromCallable($value));
        }

        if (!$value instanceof \ReflectionFunction) {
            throw new \InvalidArgumentException('$value must be callable.');
        }

        return new self($value);
    }

    public function minArguments(int $min): self
    {
        $this->minArguments = $min;

        return $this;
    }

    public function replaceTypedArgument(string $typehint, $value): self
    {
        $this->typeReplace[$typehint] = $value;

        return $this;
    }

    public function replaceUntypedArgument($value): self
    {
        $this->typeReplace[null] = $value;

        return $this;
    }

    public function execute()
    {
        $arguments = $this->function->getParameters();

        if (\count($arguments) < $this->minArguments) {
            throw new \ArgumentCountError("{$this->minArguments} argument(s) required.");
        }

        $arguments = \array_map([$this, 'replaceArgument'], $arguments);

        return $this->function->invoke(...$arguments);
    }

    private function replaceArgument(\ReflectionParameter $argument)
    {
        $type = $argument->getType();

        if (!$type && \array_key_exists(null, $this->typeReplace)) {
            return $this->typeReplace[null] instanceof \Closure ? $this->typeReplace[null]() : $this->typeReplace[null];
        }

        if (!$type instanceof \ReflectionNamedType) {
            throw new \TypeError("Unable to replace argument \"{$argument->getName()}\". No replaceUntypedArgument set.");
        }

        foreach (\array_keys($this->typeReplace) as $typehint) {
            if ($type->isBuiltin() && $typehint === $type->getName()) {
                return $this->typeReplace[$typehint];
            }

            if (!\is_a($type->getName(), $typehint, true)) {
                continue;
            }

            if (!($value = $this->typeReplace[$typehint]) instanceof \Closure) {
                return $value;
            }

            return $value($type->getName());
        }

        throw new \TypeError("Unable to replace argument \"{$argument->getName()}\".");
    }
}
