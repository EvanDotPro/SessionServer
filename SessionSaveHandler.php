<?php
class SessionSaveHandler implements Zend_Session_SaveHandler_Interface
{

    protected $_namespace;

    protected $_server;

    protected $_lockID;

    protected $_hash;

    /**
     * Open Session - retrieve resources
     *
     * @param string $save_path
     * @param string $name
     */
    public function open($save_path, $name)
    {
        $this->_namespace = $name;
        $this->_server = $save_path;
    }

    /**
     * Close Session - free resources
     *
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id
     */
    public function read($id)
    {
        $file = file_get_contents("{$this->_server}{$this->_namespace}/{$id}", false);
        if(strpos($http_response_header[4], 'X-Lock-ID:') !== false){
            $this->_lockID = str_replace('X-Lock-ID: ','', $http_response_header[4]);
            $this->_hash = sha1(trim($file));
            return $file;
        } else {
            // possibly throw an exception?
            return false;
        }
    }

    /**
     * Write Session - commit data to resource
     *
     * @param string $id
     * @param mixed $data
     */
    public function write($id, $data)
    {
        if(sha1(trim($data)) !== $this->_hash){
            $context = stream_context_create(array('http' => array(
                'method' => 'POST',
                'header' => "X-Lock-ID: {$this->_lockID}",
                'content' => $data
            )));
            $file = file_get_contents("{$this->_server}{$this->_namespace}/{$id}", false, $context);
        } else {
            $file = '1';
        }

        if ($file == '1') {
            $context = stream_context_create(array('http' => array(
                'method' => 'UNLOCK',
                'header' => "X-Lock-ID: {$this->_lockID}"
            )));
            $file = file_get_contents("{$this->_server}{$this->_namespace}/{$id}", false, $context);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Destroy Session - remove data from resource for
     * given session id
     *
     * @param string $id
     */
    public function destroy($id){
        $context = stream_context_create(array('http' => array(
            'method' => 'UNLOCK',
            'header' => "X-Lock-ID: {$this->_lockID}"
        )));
        $file = file_get_contents("{$this->_server}{$this->_namespace}/{$id}", false, $context);
        return true;
    }

    /**
     * Garbage Collection - remove old session data older
     * than $maxlifetime (in seconds)
     *
     * @param int $maxlifetime
     */
    public function gc($maxlifetime)
    {
        return true;
    }

}
