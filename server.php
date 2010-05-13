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
require_once 'flexihash/include/init.php';
require_once 'Zend/Registry.php';

$hash = new Flexihash();

$servers = array();
$servers[] = 'http://127.0.0.1:1234/';
$servers[] = 'http://127.0.0.1:1235/';
$servers[] = 'http://127.0.0.1:1236/';

Zend_Registry::set('hash', new Flexihash());
Zend_Registry::get('hash')->addTargets($servers);

Zend_Registry::set('self', 'http://127.0.0.1:1234/');

class KeyServer
{
    public static function hasKey($key)
    {
        if (file_exists('sessions/'.$key)) {
            return true;
        } else {
            return false;
        }
    }

    public static function lock($key)
    {
        $fh = fopen('sessions/'.$key, 'r+');
        flock($fh, LOCK_EX); // waiting for exclusive lock
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

        $servers = Zend_Registry::get('hash')->lookupList($key, 2);
        if (!in_array(Zend_Registry::get('self'), $servers)) {
            // The key DOESN'T belong on this server
            $response = $this->makeResponse('Not on this server!');
            return $response;
        } else {
            // The key DOES belong on this server
            if (KeyServer::hasKey($key)) {
                $response = $this->makeResponse('Key located!');
                return $response;
            } else {
                // We need to create it

                $response = $this->makeResponse('Key NOT located!');
                return $response;
            }
        }
        echo "{$method}: {$key}->{$server}\n";
        $response = $this->makeResponse(microtime(true). ' '.$method);
        return $response;
    }
}

$httpServer = new HTTPServer('tcp://127.0.0.1:1234', new Handler());
