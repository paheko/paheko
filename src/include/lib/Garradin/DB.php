<?php

namespace Garradin;

use KD2\DB\SQLite3;

class DB extends SQLite3
{
    /**
     * Application ID pour SQLite
     * @link https://www.sqlite.org/pragma.html#pragma_application_id
     */
    const APPID = 0x5da2d811;

    static protected $_instance = null;

    static public function getInstance($create = false, $readonly = false)
    {
        if (null === self::$_instance) {
            self::$_instance = new DB('sqlite', ['file' => DB_FILE]);

            $flags = \SQLITE3_OPEN_READWRITE;

            if ($create)
            {
                $flags |= \SQLITE3_OPEN_CREATE;
            }

            self::$_instance->flags = $flags;
        }

        return self::$_instance;
    }

    private function __clone()
    {
        // Désactiver le clonage, car on ne veut qu'une seule instance
    }

    public function connect(): void
    {
        if (null !== $this->db) {
            return;
        }

        parent::connect();

        // Activer les contraintes des foreign keys
        $this->db->exec('PRAGMA foreign_keys = ON;');

        // 10 secondes
        $this->db->busyTimeout(10 * 1000);
        $this->exec('PRAGMA journal_mode = TRUNCATE;');

        $this->db->createFunction('transliterate_to_ascii', ['Garradin\Utils', 'transliterateToAscii']);
    }

    public function close(): void
    {
        parent::close();
        self::$_instance = null;
    }

    /**
     * Import a file containing SQL commands
     * Allows to use the statement ".read other_file.sql" to load other files
     * @param  string $file Path to file containing SQL commands
     * @return boolean
     */
    public function import($file)
    {
        $sql = file_get_contents($file);

        $dir = dirname($file);

        $sql = preg_replace_callback('/^\.read (.+\.sql)$/m', function ($match) use ($dir) {
            return file_get_contents($dir . DIRECTORY_SEPARATOR . $match[1]) . "\n";
        }, $sql);

        return $this->exec($sql);
    }

    public function beginSchemaUpdate()
    {
        $this->toggleForeignKeys(false);
        $this->begin();
    }

    public function commitSchemaUpdate()
    {
        $this->commit();
        $this->toggleForeignKeys(true);
    }

    /**
     * @see https://www.sqlite.org/lang_altertable.html
     */
    public function toggleForeignKeys($enable)
    {
        assert(is_bool($enable));

        if (!$enable) {
            $this->db->exec('PRAGMA legacy_alter_table = ON;');
            $this->db->exec('PRAGMA foreign_keys = OFF;');
        }
        else {
            $this->db->exec('PRAGMA legacy_alter_table = OFF;');
            $this->db->exec('PRAGMA foreign_keys = ON;');
        }
    }
}
