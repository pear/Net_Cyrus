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






/*
This sample shows the use of the IMAP methods
this is only useful for testing and to high level IMAP access example use
*/


require_once('Net/Cyrus.php');
//include_once('./Cyrus_horde.php');

error_reporting(E_ALL);

$user="user";
$passwd="password";
$host="localhost";
$port="143";



//you can create a file called passwords.php and store your $user,$pass,$host and $port values in it
// or you can modify this script
@require_once("./passwords.php");


//$cyrus= new  Net_Cyrus($user,$passwd,$host,$port);
$cyrus= new  Net_Cyrus();
//$cyrus->setDebug(true);

if(PEAR::isError($ret= $cyrus->connect($user,$passwd,$host, $port) ) ){
    echo "Error. "  . $ret->getMessage() . "\n" ;
    exit();
}


//print_r($cyrus->connect());




/*print_R($cyrus);
exit();
*/


$mailbox='user.damian';


/*
echo "getQuota('$mailbox')\n";
print_r($cyrus->getQuota($mailbox));
echo "\n";

echo "getACL('$mailbox')\n";
print_r($cyrus->getACL($mailbox ));
echo "\n";

echo "getACL('$mailbox', 'cyrus')\n";
print_r($cyrus->getACL($mailbox , 'cyrus' ));
echo "\n";

echo "getFolderList('$mailbox' . '.basura.*')\n";
print_r($cyrus->getFolderList($mailbox . '.basura.*'));
echo "\n";

echo "getFolderList()\n";
print_r($cyrus->getFolderList());
echo "\n";

*/

echo "getUserList()\n";
print_r($cyrus->getUserList());
echo "\n";




$user_to_create='damiancito';
$user_to_create_new_name='damiancito_new';


echo "deleteUser('$user_to_create')\n";
if( PEAR::isError($res=$cyrus->deleteUser($user_to_create))){
    echo "The user $user_to_create was NOT deleted. Reason:" . $res->getMessage() . "\n";
}else{
    echo "The user $user_to_create was deleted";
}
echo "\n";


echo "deleteUser('$user_to_create_new_name')\n";
if( PEAR::isError($res=$cyrus->deleteUser($user_to_create_new_name))){
    echo "The user $user_to_create_new_name was NOT deleted. Reason:" . $res->getMessage() . "\n";
}else{
    echo "The user $user_to_create_new_name was deleted";
}
echo "\n";





echo "createUser('$user_to_create')\n";
if( PEAR::isError($res=$cyrus->createUser( $user_to_create ) ) ){
    echo "The user $user_to_create was NOT created. Reason:" . $res->getMessage() . "\n";
}else{
        echo "The user $user_to_create was created";
}
echo "\n";

echo "renameUser('$user_to_create','$user_to_create_new_name')\n";
if( PEAR::isError($res=$cyrus->renameUser( $user_to_create, $user_to_create_new_name ) ) ){
    echo "The user $user_to_create was NOT renamed. Reason:" . $res->getMessage() . "\n";
}else{
        echo "The user $user_to_create was renamed to $user_to_create_new_name";
}
echo "\n";




$cyrus->renameUser($user_to_create, $user_to_create_new_name);


echo "userExists('$user_to_create')\n";
if($cyrus->userExists($user_to_create) ){
    echo "The mailbox $user_to_create exists!!\n";
}else{
    echo "ERROR!!! The mailbox $user_to_create does NOT exists!!\n";
}

echo "userExists('$user_to_create_new_name')\n";
if($cyrus->userExists($user_to_create_new_name) ){
    echo "The mailbox $user_to_create_new_name exists!!\n";
}else{
    echo "ERROR!!! The mailbox $user_to_create_new_name does NOT exists!!\n";
}


echo "getUserList()\n";
print_r($cyrus->getUserList());
echo "\n";

echo "getACL('$user_to_create_new_name')\n";
print_r($cyrus->getACL('user.' . $user_to_create_new_name ));
echo "\n";


// clean up
echo "deleteUser('$user_to_create')\n";
if( PEAR::isError($res=$cyrus->deleteUser($user_to_create))){
    echo "The user $user_to_create was NOT deleted. Reason:" . $res->getMessage() . "\n";
}else{
    echo "The user $user_to_create was deleted";
}
echo "\n";

echo "deleteUser('$user_to_create_new_name')\n";
if( PEAR::isError($res=$cyrus->deleteUser($user_to_create_new_name))){
    echo "The user $user_to_create_new_name was NOT deleted. Reason:" . $res->getMessage() . "\n";
}else{
    echo "The user $user_to_create_new_name was deleted";
}
echo "\n";


$cyrus->disconnect();
?>
