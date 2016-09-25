<?php

/**
 * Container of the database connection
 *
 * @author    	Judicaël Paquet <judicael.paquet@gmail.com>
 * @copyright 	Copyright (c) 2013-2014 PAQUET Judicaël FR Inc. (https://github.com/judicaelpaquet)
 * @license   	https://github.com/vyctory/attila-orm/blob/master/LICENSE.md Tout droit réservé à PAQUET Judicaël
 * @version   	Release: 1.0.0
 * @filesource	https://github.com/vyctory/attila-orm
 * @link      	https://github.com/vyctory
 * @since     	1.0.0
 */
namespace Attila\lib\Db;

/**
 * Class Container
 * @package Attila\lib\Db
 */
class Container
{
    /**
     * name of connection
     *
     * @access private
     * @var    array
     */
    private $_sName = '';

    /**
     * Type of databases (mysql, mssql, sqlite...)
     *
     * @access private
     * @var    array
     */
    private $_sType = '';

    /**
     * host
     *
     * @access private
     * @var    array
     */
    private $_sHost = '';

    /**
     * user
     *
     * @access private
     * @var    array
     */
    private $_sUser = '';

    /**
     * password
     *
     * @access private
     * @var    array
     */
    private $_sPassword = '';

    /**
     * database name
     *
     * @access private
     * @var    array
     */
    private $_sDbName = '';

    /**
     *  set name
     *
     *  @access public
     *  @param  string $sName
     *  @return \Attila\lib\Db\Container
     */
    public function setName($sName)
    {
        $this->_sName = $sName;
        return $this;
    }

    /**
     *  get name
     *
     *  @access public
     *  @return tring
     */
    public function getName()
    {
        return $this->_sName;
    }

    /**
     *  set Type
     *
     *  @access public
     *  @param  string $sType
     *  @return \Attila\lib\Db\Container
     */
    public function setType($sType)
    {
        $this->_sType = $sType;
        return $this;
    }

    /**
     *  get Type
     *
     *  @access public
     *  @return tring
     */
    public function getType()
    {
        return $this->_sType;
    }

    /**
     *  set Host
     *
     *  @access public
     *  @param  string $sHost
     *  @return \Attila\lib\Db\Container
     */
    public function setHost($sHost)
    {
        $this->_sHost = $sHost;
        return $this;
    }

    /**
     *  get Host
     *
     *  @access public
     *  @return tring
     */
    public function getHost()
    {
        return $this->_sHost;
    }

    /**
     *  set User
     *
     *  @access public
     *  @param  string $sUser
     *  @return \Attila\lib\Db\Container
     */
    public function setUser($sUser)
    {
        $this->_sUser = $sUser;
        return $this;
    }

    /**
     *  get User
     *
     *  @access public
     *  @return tring
     */
    public function getUser()
    {
        return $this->_sUser;
    }

    /**
     *  set Password
     *
     *  @access public
     *  @param  string $sPassword
     *  @return \Attila\lib\Db\Container
     */
    public function setPassword($sPassword)
    {
        $this->_sPassword = $sPassword;
        return $this;
    }

    /**
     *  get Password
     *
     *  @access public
     *  @return tring
     */
    public function getPassword()
    {
        return $this->_sPassword;
    }

    /**
     *  set DbName
     *
     *  @access public
     *  @param  string $sDbName
     *  @return \Attila\lib\Db\Container
     */
    public function setDbName($sDbName)
    {
        $this->_sDbName = $sDbName;
        return $this;
    }

    /**
     *  get DbName
     *
     *  @access public
     *  @return tring
     */
    public function getDbName()
    {
        return $this->_sDbName;
    }
}
