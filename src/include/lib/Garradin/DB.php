<?php

namespace Garradin;

use KD2\DB_SQLite3;

class DB extends DB_SQLite3
{
    /**
     * Application ID pour SQLite
     * @link https://www.sqlite.org/pragma.html#pragma_application_id
     */
    const APPID = 0x5da2d811;

    static protected $_instance = null;

    static public function getInstance($create = false)
    {
        return self::$_instance ?: self::$_instance = new DB($create);
    }

    private function __clone()
    {
        // Désactiver le clonage, car on ne veut qu'une seule instance
    }

    public function __construct($create = false)
    {
        $flags = \SQLITE3_OPEN_READWRITE;

        if ($create)
        {
            $flags |= \SQLITE3_OPEN_CREATE;
        }

        parent::__construct(DB_FILE, $flags);

        // Ne pas se connecter ici, on ne se connectera que quand une requête sera faite
    }

    public function connect()
    {
        if (parent::connect())
        {
            // Activer les contraintes des foreign keys
            $this->db->exec('PRAGMA foreign_keys = ON;');

            $this->db->createFunction('transliterate_to_ascii', ['Garradin\Utils', 'transliterateToAscii']);
        }
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

        return $this->db->exec($sql);
    }
}
