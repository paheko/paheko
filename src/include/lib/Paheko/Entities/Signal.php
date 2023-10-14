<?php

namespace Paheko\Entities;

class Signal
{
	protected string $name;

	/**
	 * Incoming values
	 */
	protected array $in = [];

	/**
	 * Outgoing values
	 */
	protected array $out = [];

	protected bool $stoppable = false;
	protected bool $stop = false;

	public function __construct(string $name, bool $stoppable = false, array $in = [], array $out = [])
	{
		$this->name = $name;
		$this->stoppable = $stoppable;
		$this->in = $in;
		$this->out = $out;
	}

	public function isStoppable(): bool
	{
		return $this->stoppable;
	}

	public function isStopped(): bool
	{
		return $this->stop;
	}

	public function stop(): void
	{
		if (!$this->stoppable) {
			throw new \LogicException('Trying to stop a non-stoppable signal');
		}

		$this->stop = true;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getIn(?string $name = null)
	{
		if (null === $name) {
			return $this->in;
		}

		if (!array_key_exists($name, $this->in)) {
			throw new \LogicException(sprintf('Cannot get incoming variable "%s" in signal "%s": unknown variable name', $name, $this->name));
		}

		return $this->in[$name];
	}

	public function setOut(string $name, $value): void
	{
		$this->out[$name] = $value;
	}

	public function getOut(?string $name = null)
	{
		if (null === $name) {
			return $this->out;
		}

		return $this->out[$name] ?? null;
	}
}
