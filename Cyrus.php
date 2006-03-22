<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Damian Alejandro Fernandez Sosa <damlists@cnba.uba.ar>       |
// +----------------------------------------------------------------------+


require_once('Net/IMAP.php');

/**
 * Net_Cyrus class provides an API for the administration of Cyrus IMAP servers.
 * please see:
 *      http://asg.web.cmu.edu/cyrus/imapd/
 * @author  Damian Alejandro Fernandez Sosa <damlists@cnba.uba.ar>
 * @package Net_Cyrus
 */

class Net_Cyrus extends Net_IMAP
{

    /**
     * Hostname of server
     * @var string
     */
    var $_host;

    /**
     * Port number of server
     * @var string
     */
    var $_port;

    /**
     * Username used to connect
     * @var string
     */
    var $_user;

    /**
     * Password used to connect
     * @var string
     */
    var $_pass;

    /**
     * Timeout for socket connect
     * @var integer
     */
    var $_timeout;

    /**
     * Constructor.
     *
     * @param string  $user     Cyrus admin username
     * @param string  $pass     Cyrus admin password
     * @param string  $host     Server hostname
     * @param integer $port     Server port number
     * @param integer $timeout  Connection timeout value
     */
    function Net_Cyrus($user = null , $pass = null , $host = 'localhost', $port = 143, $timeout = 5)
    {
        $this->_user      = $user;
        $this->_pass      = $pass;
        $this->_host      = $host;
        $this->_port      = $port;
        $this->_timeout   = $timeout;
    }

    /**
     * Connects and logs into the server. Uses the Auth_SASL
     * library to produce the LOGIN command if available
     *
     * @access public
     */
    function connect($user = null, $pass = null, $host = null , $port = null, $method=true)
    {
        $this->Net_IMAPProtocol();
        // Backward compatibility hack Horde's Net_Cyrus don't allow params in connect()
        if( ($user === null) && ($pass === null) && ($host === null) && ($port === null) ){
            if (PEAR::isError($ret = parent::connect($this->_host, $this->_port) )){
                return $ret;
            }
            if(PEAR::isError($ret = $this->login($this->_user, $this->_pass, true, false ))) {
                return $ret;
            }
        }else{
            // Save this information if we aren't in the hack case so that any other
            // calls to the internal functions still work.
            $this->_user = $user;
            $this->_pass = $pass;
            $this->_host = $host;
            $this->_port = $port;
            if (PEAR::isError($ret = parent::connect($host, $port) )){
                return $ret;
            }
            if(PEAR::isError($ret = $this->login($user, $pass, $method, false ))) {
                return $ret;
            }
        }
        return true;
    }





    /**
    * Handles the errors the class can find
    * on the server
    *
    * @access private
    * @return PEAR_Error
    */

    function _raiseError($msg, $code)
    {
    include_once 'PEAR.php';
    return PEAR::raiseError($msg, $code);
    }



    /**
     * Ends the session. Issues the LOGOUT command first.
     * @access public
     */
    function disconnect()
    {
        return parent::disconnect(false);

    }


    /**
     * Sets admin privileges on a folder/mailbox.
     * useful because by default cyrus don't add delete permission to the admin user
     *
     * @param string $mailbox  Mailbox
     *
     * @return string  Previous permissions for admin user on this mailbox.
     *
     * @access private
     */
    function _setAdminPriv($mailbox)
    {
        $oldPrivs = $this->getACL($mailbox, $this->_user);
        $this->setACL($mailbox, $this->_user, 'lrswipcda');
        return $oldPrivs;
    }

    /**
     * Removes admin privileges on a folder/mailbox
     * after the above function has been used. If the
     * ACLs passed in is null, then the privs are deleted.
     *
     * @param string $mailbox  Mailbox
     * @param string $privs    Previous privileges as returned
     *                         by the _setAdminPriv() method
     *
     * @access private
     */
    function _resetAdminPriv($mailbox, $privs = null)
    {
        if (is_null($privs)) {
            $this->deleteACL($mailbox, $this->_user);
        } else {
            $this->setACL($mailbox, $this->_user, $privs);
        }
    }

    /**
     * Returns quota details.
     *
     * @param string $mailbox  Mailbox to get quota info for.
     *
     * @return mixed  Array of current usage and quota limit or
     *                false on failure.
     * @access public
     */
    function getQuota($mailbox)
    {
        if(PEAR::isError($ret=$this->getStorageQuota($mailbox))){
            return $ret;
        }
        return array(0=>$ret['USED'], 1=>$ret['QMAX']);

    }

    /**
     * Sets a quota.
     *
     * @param string $mailbox  Mailbox to get quota info for
     * @param integer $quota   The quota to set
     *
     * @return mixed  True on success, PEAR_Error otherwise
     */
    function setQuota($mailbox, $quota)
    {
        return $this->setStorageQuota($mailbox, $quota);
    }

    /**
     * Copies a quota from one mailbox to another.
     *
     * @param string $from  Mailbox to copy quota from
     * @param string $to    Mailbox to set quota on
     * @access public
     */
    function copyQuota($from, $to)
    {
        $currentQuota = $this->getQuota($from);
        $oldQuotaMax = trim($currentQuota[1]);
        if ($oldQuotaMax != 'NOT-SET') {
            $this->setQuota($to, $oldQuotaMax);
        }
    }




    /**
     * Retrieves details of current ACL.
     *
     * @param string $mailbox  Name of mailbox
     * @param  string $user    Optional user to get ACL for
     *
     * @return string  Access stuff
     * @access public
     */
    function getACL($mailbox, $user = null)
    {

        $acl=parent::getACL($mailbox);
        $acl_arr=array();
        if(is_array($acl)){
            foreach($acl as $a){
                if( $user === null ){
                    $acl_arr[$a['USER']]=$a['RIGHTS'];
                }else{
                    if( $user == $a['USER'] ){
                        return $a['RIGHTS'];
                    }
                }
            }
            return $acl_arr;
        }else{
            return $acl;
        }

    }



    /**
     * Sets ACL on a mailbox.
     *
     * @param string $mailbox  Name of mailbox
     * @param string $user     Username to apply ACL to
     * @param string $acl      The ACL
     *
     * @return mixed  True on success, PEAR_Error otherwise
     * @access public
     */
    function setACL($mailbox, $user, $acl)
    {
        return parent::setACL($mailbox, $user, $acl);
    }




    /**
     * Deletes ACL from a mailbox.
     *
     * @param string $mailbox  Name of mailbox
     * @param string $user     Username to remove ACL from
     *
     *
     * @return mixed  True on success, PEAR_Error otherwise
     * @access public
     */
    function deleteACL($mailbox, $user)
    {
        return parent::deleteACL($mailbox, $user);
    }



    /**
     * Creates a mailbox.
     *
     * @param string $mailbox  Name of mailbox to create
     *
     *
     * @return mixed  True on success, PEAR error otherwise
     * @access public
     */
    function createMailbox($mailbox)
    {
        $res= parent::createMailbox($mailbox);
        return $res;
    }

    /**
     * Renames a mailbox.
     *
     * @param string $mailbox  Name of mailbox to rename
     * @param string $newname  New name of mailbox
     *
     * @return mixed  True on success, PEAR error otherwise
     * @access public
     */
    function renameMailbox($mailbox, $newname)
    {
        $oldPrivs = $this->_setAdminPriv($mailbox);
        if( PEAR::isError( $response = parent::renameMailbox($mailbox, $newname) )){
            return $response;
        }
        $this->_resetAdminPriv($mailbox, $oldPrivs);
        return true;
    }



    /**
     * Deletes a mailbox.
     *
     * @param string $mailbox  Name of mailbox to delete
     *
     * @return mixed  True on success, PEAR error otherwise
     * @access public
     */
    function deleteMailbox($mailbox)
    {
        $oldPrivs = $this->_setAdminPriv($mailbox);
        if( PEAR::isError( $response = parent::deleteMailbox($mailbox) )){
            $this->_resetAdminPriv($mailbox, $oldPrivs);
            return $response;
        }
        return true;
    }

    /**
     * Returns a list of folders for a particular user.
     *
     * @param string $prepend  Optional string to prepend
     *
     * @return array  Array of folders matched
     * @access public
     */
    function getFolderList($folderMask = null )
    {
        if( $folderMask === null ){
            $folderMask = 'user' . $this->getHierarchyDelimiter( ) . '*' ;
        }
        //echo "FOLDERLIST: $folderMask\n";
        return $this->getMailboxes(''  , $folderMask , false );
    }


    /**
     * Returns a list of users.
     *
     * @return array  Array of users found
     * @access public
     */
    function getUserList()
    {

        $hierarchyDelimiter= $this->getHierarchyDelimiter();
        $user_base='user' . $hierarchyDelimiter . '%' ;
        if(PEAR::isError( $user_list = $this->getFolderList($user_base) ) ){
            return $user_list;
        }
        $users = array();
        foreach ($user_list as $user) {
            $user_arr=explode($hierarchyDelimiter, $user);
            $users[]=$user_arr[1];
        }
        return $users;
    }



    /**
    * Parses a user name
    *
    * @param string $user_name  the user parse
    * @param boolean $append_userPart  true if the method appends 'user.' to the user name
    * @access public
    * @since  1.0
    */
    function getUserName($user_name, $append_userPart = true)
    {
         if(strtolower(substr($user_name,0,5)) == 'user.'){
            $user_arr=explode('user.',$user_name);
            $user_name=$user_arr[1];
        }
        if($append_userPart){
            return 'user' . $this->getHierarchyDelimiter() . $user_name;
        }else{
            return $user_name;
        }

    }





    /**
    * deletes a user. Use this instead of deleteMailbox
    *
    * @param string $user_name  the user to deletes
    *
    * @return mixed true on Success/PearError on Failure
    * @access public
    */
    function deleteUser($user_name)
    {
        $user_name=$this->getUserName($user_name);
        return $this->deleteMailbox($user_name);
    }




    /**
    * creates a user. Use this instead of createMailbox
    *
    * @param string $user_name  the user to create
    *
    * @return mixed true on Success/PearError on Failure
    * @access public
    */
    function createUser($user_name)
    {
        if( $this->userExists($user_name) ){
            return $this->_raiseError("The user $user_name already exists" , 503);
        }
        $user_name=$this->getUserName($user_name);
        return $this->createMailbox($user_name);
    }



   /**
    * check if the user name exists
    *
    * @param string $mailbox     user name to check existance
    *
    * @return boolean true on Success/false on Failure
    * @since 1.0
    */
    function userExists($user_name)
    {
        $user_name = $this->getUserName($user_name);
        return $this->mailboxExist($user_name);
    }


    /**
     * Renames a user. This is here since the RENAME command
     * is not allowed on a user's INBOX (ie. the user.<username>
     * mailbox). Supplied args can be either with or without
     * the "user." at the beginning.
     *
     * @param string $oldUser  Name of user to rename
     * @param string $newUser  New name of user
     *
     * @return mixed true on Success/PearError on Failure
     * @access public
     */
    function renameUser($oldUser, $newUser)
    {

        $oldUsername = $this->getUserName($oldUser,false);
        $newUsername = $this->getUserName($newUser,false);

        $oldUser =$this->getUserName($oldUser);
        $newUser =$this->getUserName($newUser);

        // Check new user doesn't already exist and old user exists
        if (!$this->userExists($oldUsername) ) {
            $msg=sprintf('The user "%s" doesn\'t exist', $oldUsername);
            $code=502;
            return $this->_raiseError($msg, $code);
        }

        if ($this->userExists($newUsername) ) {
            $msg=sprintf('the user "%s" already exists. choose another user name', $newUsername);
            $code=503;
            return $this->_raiseError($msg, $code);
        }

        // Create the new mailbox
        $this->createMailbox($newUser);
        $oldAdminPrivs = $this->_setAdminPriv($newUser);

        // Copy Mail and quotas
        $this->copyMail($oldUser, $newUser);
        $this->copyQuota($oldUser, $newUser);

        // Copy the folders
        $folderList = $this->getFolderList($oldUser . $this->getHierarchyDelimiter() . '*');

        if (!empty($folderList)) {
            foreach ($folderList as $folder) {
                $newFolderName = str_replace($oldUser, $newUser, $folder);
                $this->renameMailbox($folder, $newFolderName);
                $this->setACL($newFolderName, $newUsername, 'lrswipcd');
                $this->deleteACL($newFolderName, $oldUsername);
            }
        }
        $this->_resetAdminPriv($newUser, $oldAdminPrivs);
        $this->deleteMailbox($oldUser);
    }

    /**
     * Copies mail from one folder to another.
     *
     * @param string $from  From mailbox name
     * @param string $to    To mailbox name
     *
     * @return mixed true on Success/PearError on Failure
     * @access public
     */
    function copyMail($from, $to)
    {
        $oldFromPrivs = $this->_setAdminPriv($from);
        $oldToPrivs   = $this->_setAdminPriv($to);

        $this->selectMailbox($from);
        $this->copyMessages($to);

        $this->_resetAdminPriv($from, $oldFromPrivs);
        $this->_resetAdminPriv($to, $oldToPrivs);
    }

}
