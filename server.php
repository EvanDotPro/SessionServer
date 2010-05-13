#!/usr/bin/php -q
<?php
 /**
 * This file is part of SessionServer.
 *
 * SessionServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 of the License.
 *
 * SessionServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GtkGrab.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   SessionServer
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPL
 * @copyright  Copyright 2010 Evan Coury (http://www.Evan.pro/)
 * @package    Server
 */
declare(ticks = 1);
require_once 'HTTPServer.php';

class KeyServer
{
    protected $_key;
    protected $_fileHandle;
    protected $_lockFile;


    public function __construct($key)
    {
        $this->_key = $key;
        $this->_lockFile = 'data/locks/'.$key;
    }

    public function lock()
    {
        $lockID = sha1(rand(0,99999).microtime(true));
        do {
            $this->_fileHandle = @fopen($this->_lockFile, 'x');
            if ($this->_fileHandle === false) {
                usleep(rand(5000,10000));
            }
        } while ($this->_fileHandle === false);

        if (fwrite($this->_fileHandle, $lockID) === false) {
            unlink($this->_lockFile);
            // recursively try again? this could cause problems....
            usleep(rand(5000,10000));
            return $this->lock();
        } else {
            return $lockID;
        }
    }

    public function unlock($lockID)
    {
        if (file_exists($this->_lockFile)) {
            if (file_get_contents($this->_lockFile) === $lockID) {
                unlink($this->_lockFile);
                return true;
            }
        }
        return false;
    }
}

class Handler extends HTTPServerHandler
{
    public function interact($method, $path, $headers, $body)
    {
        $path = explode('/', $path);
        // Only accept http://host:port/namespace/keyname
        if (!is_array($path) || count($path) != 3 || !ctype_alnum($path[1]) || !ctype_alnum($path[2])) {
            echo "Invalid request\n";
            $response = $this->makeResponse('Invalid Request');
            return $response;
        }

        // Build the 'real' key name
        $key = $path[1].'::'.$path[2];

        // acquire lock
        echo microtime(true) . " {$method}: {$key}\n";
        $keyHandler = new KeyServer($key);
        if ($method == 'GET') {
            $lockID = $keyHandler->lock($key);
            $response = $this->makeResponse('Lock ID acquired: '.$lockID);
            return $response;
        } else if ($method == 'POST') {
            $lockID = trim($body);
            $result = $keyHandler->unlock($lockID);
            if ($result == true) {
                $response = $this->makeResponse('Lock ID released: '.$lockID);
            } else {
                $response = $this->makeResponse('Unable to relase lock ID: '.$lockID);
            }
            return $response;
        }
    }
}

$httpServer = new HTTPServer('tcp://127.0.0.1:1234', new Handler());
