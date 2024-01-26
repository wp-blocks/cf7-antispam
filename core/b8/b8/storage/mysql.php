<?php

// SPDX-FileCopyrightText: 2009 Oliver Lillie <ollie@buggedcom.co.uk>
// SPDX-FileCopyrightText: 2006-2021 Tobias Leupold <tl at stonemx dot de>
//
// SPDX-License-Identifier: LGPL-3.0-or-later

namespace b8\storage;

/**
 * A MySQL storage backend
 *
 * @package b8
 */

class mysql extends storage_base
{

    private $mysql = null;
    private $table = null;

    protected function setup_backend(array $config)
    {
        if (! isset($config['resource'])
            || get_class($config['resource']) !== 'mysqli') {

            throw new \Exception(mysql::class . ": No valid mysqli object passed");
        }
        $this->mysql = $config['resource'];

        if (! isset($config['table'])) {
            throw new \Exception(mysql::class . ": No b8 wordlist table name passed");
        }
        $this->table = $config['table'];
    }

    protected function fetch_token_data(array $tokens)
    {
        $data = [];

        $escaped = [];
        foreach ($tokens as $token) {
            $escaped[] = $this->mysql->real_escape_string($token);
        }
        $result = $this->mysql->query('SELECT token, count_ham, count_spam'
                                      . ' FROM ' . $this->table
                                      . ' WHERE token IN '
                                      . "('" . implode("','", $escaped) . "')");

        while ($row = $result->fetch_row()) {
            $data[$row[0]] = [ \b8\b8::KEY_COUNT_HAM  => $row[1],
                               \b8\b8::KEY_COUNT_SPAM => $row[2] ];
        }

        $result->free_result();

        return $data;
    }

    protected function add_token(string $token, array $count)
    {
        $query = $this->mysql->prepare('INSERT INTO ' . $this->table
                                       . '(token, count_ham, count_spam) VALUES(?, ?, ?)');
        $query->bind_param('sii', $token, $count[\b8\b8::KEY_COUNT_HAM],
                                          $count[\b8\b8::KEY_COUNT_SPAM]);
        $query->execute();
    }

    protected function update_token(string $token, array $count)
    {
        $query = $this->mysql->prepare('UPDATE ' . $this->table
                                       . ' SET count_ham = ?, count_spam = ? WHERE token = ?');
        $query->bind_param('iis', $count[\b8\b8::KEY_COUNT_HAM], $count[\b8\b8::KEY_COUNT_SPAM],
                                  $token);
        $query->execute();
    }

    protected function delete_token(string $token)
    {
        $query = $this->mysql->prepare('DELETE FROM ' . $this->table . ' WHERE token = ?');
        $query->bind_param('s', $token);
        $query->execute();
    }

    protected function start_transaction()
    {
        $this->mysql->begin_transaction();
    }

    protected function finish_transaction()
    {
        $this->mysql->commit();
    }

}
