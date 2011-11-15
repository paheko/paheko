<?php

class Garradin_DB
{
    protected $db = null;

    public function __construct()
    {
        $exists = file_exists(GARRADIN_DB_FILE) ? true : false;

        $this->db = new SQLite3(GARRADIN_DB_FILE);

        if (!$exists)
        {
            $this->db->exec('BEGIN;');
            $this->db->exec(file_get_contents(GARRADIN_DB_SCHEMA));
            $this->db->exec('END;');
        }
    }

    public function __destruct()
    {
        $this->db->close();
    }
}

?>