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

class Handler
{
    public function interact($request)
    {
        $method = strtoupper(substr($request, 0, strpos($request, ' ')));
        $response = microtime(true). ' '.$method;
        $resp  = "HTTP/1.0 " . 200 . " OK" . "\r\n";
		$resp .= "Date: " . gmdate("D, d M Y H:i:s T") . "\r\n";
		$resp .= "Server: sesserve/0.0.1\r\n";
		$resp .= "Content-Type: text/html" . "\r\n";
		$resp .= "Content-Length: " . strlen($response) . "\r\n";
		$resp .= "Connection: Close" . "\r\n";
		$resp .= "\r\n";
        $resp .= $response;

        return $resp;
    }
}

$httpServer = new HTTPServer('tcp://127.0.0.1:1234', new Handler());
