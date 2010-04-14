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
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

class HTTPServer
{
    protected $_socket;
    protected $_class;
    protected $_isListening = true;

    public function __construct($address, $class)
    {
        pcntl_signal(SIGTERM, array(&$this,'handleSignal'));
        pcntl_signal(SIGINT, array(&$this,'handleSignal'));
        pcntl_signal(SIGCHLD, array(&$this,'handleSignal'));

        $this->_class = $class;

        $this->listen($address);
    }

    public function listen($address)
    {
        $this->_sock = @stream_socket_server($address, $errno, $errstr);
        if (!$this->_sock) {
            echo "cannot listen to {$address}: {$errno} - {$errstr}";
            exit();
        }

        stream_set_blocking($this->_sock, false);
        stream_set_timeout($this->_sock, 0);
        echo "waiting for clients to connect\n";

        while ($this->_isListening)
        {
            $connection = @stream_socket_accept($this->_sock, 0);
            if ($connection === false) {
                // nothing to do
            } elseif ($connection > 0) {
                $this->handleClient($this->_sock, $connection);
            } else {
                echo "error";
                exit();
            }
            usleep(250);
        }
    }

    public function handleClient($ssock, $csock)
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            /* fork failed */
            echo "fork failure!\n";
            exit();
        } elseif ($pid == 0) {
            /* child process */
            $this->_isListening = false;
            fclose($ssock);
            $buffer = '';
            while(!preg_match('/\r?\n\r?\n/', $buffer)) {
                $buffer .= fread($csock, 2046);
            }
            echo "\nCHILD PID: ".getmypid()."\n";
            $response = $this->_class->interact($buffer);
            fputs($csock, $response);
            fclose($csock);
        } else {
            fclose($csock);
        }
    }

    function handleSignal($signal)
    {
        switch($signal)
        {
            case SIGTERM:
            case SIGINT:
                exit();
            break;

            case SIGCHLD:
                pcntl_waitpid(-1, $status);
            break;
        }
    }
}
