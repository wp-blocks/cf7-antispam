<?php

// SPDX-FileCopyrightText: 2021 Malte Paskuda <malte@paskuda.biz>
//
// SPDX-License-Identifier: LGPL-3.0-or-later

namespace b8\storage;
use PDO;

/**
 * An SQLite storage backend
 *
 * @package b8
 */

class sqlite extends storage_base
{

    private $sqlite = null;
    private $table = null;

    protected function setup_backend(array $config)
    {
        $this->sqlite = $config['resource'];

        if (! isset($config['table'])) {
            $config['table'] = 'b8_wordlist';
        }
        $this->table = $config['table'];
    }

    protected function fetch_token_data(array $tokens)
    {
        $data = [];

        $escaped = [];
        foreach ($tokens as $token) {
            $escaped[] = $this->sqlite->quote($token);
        }

        $result = $this->sqlite->query('SELECT token, count_ham, count_spam'
                                      . ' FROM ' . $this->table
                                      . ' WHERE token IN '
                                      . "(" . implode(",", $escaped) . ")");

        while ($row = $result->fetch()) {
            $data[$row[0]] = [ \b8\b8::KEY_COUNT_HAM  => $row[1],
                               \b8\b8::KEY_COUNT_SPAM => $row[2] ];
        }

        return $data;
    }

    protected function add_token(string $token, array $count)
    {
        $query = $this->sqlite->prepare('INSERT INTO ' . $this->table
                                       . '(token, count_ham, count_spam) VALUES(?, ?, ?)');
        $query->bindParam(1, $token, PDO::PARAM_STR);
        $query->bindParam(2, $count[\b8\b8::KEY_COUNT_HAM], PDO::PARAM_INT);
        $query->bindParam(3, $count[\b8\b8::KEY_COUNT_SPAM], PDO::PARAM_INT);

        $query->execute();
    }

    protected function update_token(string $token, array $count)
    {
        $query = $this->sqlite->prepare('UPDATE ' . $this->table
                                       . ' SET count_ham = ?, count_spam = ? WHERE token = ?');
        $query->bindParam(1, $count[\b8\b8::KEY_COUNT_HAM], PDO::PARAM_INT);
        $query->bindParam(2, $count[\b8\b8::KEY_COUNT_SPAM], PDO::PARAM_INT);
        $query->bindParam(3, $token, PDO::PARAM_STR);
        $query->execute();
    }

    protected function delete_token(string $token)
    {
        $query = $this->sqlite->prepare('DELETE FROM ' . $this->table . ' WHERE token = ?');
        $query->bindParam(1, $token, PDO::PARAM_STR);
        $query->execute();
    }

    protected function start_transaction()
    {
        $this->sqlite->beginTransaction();
    }

    protected function finish_transaction()
    {
        $this->sqlite->commit();
    }

}
