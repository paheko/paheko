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
        if (!defined('\SQLITE3_OPEN_READWRITE'))
        {
            throw new \Exception('Module SQLite3 de PHP non présent. Merci de l\'installer.');
        }

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
            $this->exec('PRAGMA foreign_keys = ON;');

            // 10 secondes
            $this->db->busyTimeout(10 * 1000);
            $this->exec('PRAGMA journal_mode = TRUNCATE;');

            $this->db->createFunction('transliterate_to_ascii', ['Garradin\Utils', 'transliterateToAscii']);
        }
    }

    public function close()
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

    public function deleteUndoTriggers()
    {
        $triggers = $this->getAssoc('SELECT name, name FROM sqlite_master
            WHERE type = \'trigger\' AND name LIKE \'!_%!_log!__t\' ESCAPE \'!\';');

        foreach ($triggers as $trigger)
        {
            $this->exec(sprintf('DROP TRIGGER %s;', $this->quoteIdentifier($trigger)));
        }
    }

    public function createUndoTriggers()
    {
        $this->exec('CREATE TABLE undolog (
            seq INTEGER PRIMARY KEY,
            sql TEXT NOT NULL,
            date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            type TEXT NOT NULL,
            action TEXT NOT NULL
        );');

        // List all tables except SQLite tables
        $tables = $this->getAssoc('SELECT name, name FROM sqlite_master
            WHERE type = \'table\'
            AND name NOT LIKE \'sqlite!_%\' ESCAPE \'!\' AND name NOT LIKE \'wiki!_recherche%\' ESCAPE \'!\'
            AND name != \'undolog\';');


        $query = 'CREATE TRIGGER _%table_log_it AFTER INSERT ON %table BEGIN
                DELETE FROM undolog WHERE rowid IN (SELECT rowid FROM undolog LIMIT 500,1000);
                INSERT INTO undolog (type, action, sql) VALUES (\'%table\', \'I\', \'DELETE FROM %table WHERE rowid=\'||new.rowid);
            END;
            CREATE TRIGGER _%table_log_ut AFTER UPDATE ON %table BEGIN
                DELETE FROM undolog WHERE rowid IN (SELECT rowid FROM undolog LIMIT 500,1000);
                INSERT INTO undolog (type, action, sql) VALUES (\'%table\', \'U\',  \'UPDATE %table SET %columns_update WHERE rowid = \'||old.rowid);
            END;
            CREATE TRIGGER _%table_log_dt BEFORE DELETE ON %table BEGIN
                DELETE FROM undolog WHERE rowid IN (SELECT rowid FROM undolog LIMIT 500,1000);
                INSERT INTO undolog (type, action, sql) VALUES (\'%table\', \'D\', \'INSERT INTO %table (rowid, %columns_list) VALUES(\'||old.rowid||\', %columns_insert)\');
            END;';

        foreach ($tables as $table)
        {
            $columns = $this->getAssoc(sprintf('PRAGMA table_info(%s);', $this->quoteIdentifier($table)));
            $columns_insert = [];
            $columns_update = [];

            foreach ($columns as &$name)
            {
                $columns_update[] = sprintf('%s = \'||quote(old.%1$s)||\'', $name);
                $columns_insert[] = sprintf('\'||quote(old.%s)||\'', $name);
            }

            $sql = strtr($query, [
                '%table' => $table,
                '%columns_list' => implode(', ', $columns),
                '%columns_update' => implode(', ', $columns_update),
                '%columns_insert' => implode(', ', $columns_insert),
            ]);

            $this->exec($sql);
        }
    }
}
