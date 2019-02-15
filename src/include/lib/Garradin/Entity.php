<?php

namespace Garradin;

class Entity
{
	protected $id;
	protected $table;

	public function save()
	{
		$db = DB::getInstance();

		if (null === $this->id)
		{
			if ($return = $db->insert($this->table, $this->toArray()))
			{
				$this->id = $db->lastInsertId();
			}
		}
		else
		{
			$return = $db->update($this->table, $this->toArray(), 'id = :id', ['id' => $this->id]);
		}

		return $return;
	}

	public function set($key, $value = null)
	{
		if (is_array($key))
		{
			foreach ($key as $_key => $_value)
			{
				if (!$this->set($_key, $value))
				{
					return false;
				}
			}

			return true;
		}

		$this->$key = $value;
	}

	public function filterUserEntry($key, $value)
	{
		return trim($value);
	}
}
