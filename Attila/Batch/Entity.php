<?php

/**
 * Batch that create entity
 *
 * @author    	Judicaël Paquet <judicael.paquet@gmail.com>
 * @copyright 	Copyright (c) 2013-2014 PAQUET Judicaël FR Inc. (https://github.com/judicaelpaquet)
 * @license   	https://github.com/vyctory/attila-orm/blob/master/LICENSE.md Tout droit réservé à PAQUET Judicaël
 * @version   	Release: 1.0.0
 * @filesource	https://github.com/vyctory/attila-orm
 * @link      	https://github.com/vyctory
 * @since     	1.0.0
 *
 * @tutorial    You could launch this Batch in /private/
 * 				php launch.php scaffolding -p [portal]
 * 				-p [portal] => it's the name where you want add your entities and models
 * 				-r [rewrite] => if we force rewrite file
 * 					by default, it's Batch
 */
namespace Attila\Batch;

use \Attila\lib\Db as Db;
use \Attila\lib\Db\Container as DbContainer;
use \VenusBash\Bash;
use \VenusPhpdoc\Reader;

/**
 * Class Entity
 * @package Attila\Batch
 */
class Entity
{
    /**
     * options of the batch
     *
     * @access public
     * @var    array
     */
    public static $aOptionsBatch = array(
        "p" => "string",
        "r" => false,
        "c" => false,
        "e" => false,
        "d" => false,
        "f" => false,
        "a" => "string",
        "g" => "string",
        "h" => "string",
        "i" => "string",
        "v" => false
    );

    private $decimalTypes = ['float', 'decimal', 'double', 'precision', 'real', 'float4', 'float8', 'numeric'];

    /**
     * @var bool
     */
    private $verbose = false;

    /**
     * @var string
     */
    private $portal = 'Attila';

    /**
     * @var string
     */
    private $entitiesPath = '';

    /**
     * @var string
     */
    private $modelsPath = '';

    /**
     * @var string
     */
    private $entitiesNamespace = '';

    /**
     * @var string
     */
    private $modelsNamespace = '';

    /**
     * @var string
     */
    private $confFile = '';

    /**
     * @var
     */
    private $defaultConnection;

    /**
     * run the batch to create entity
     * @tutorial launch.php scaffolding
     *
     * @access public
     * @param  array $aOptions options of script
     * @return void
     */

    public function runScaffolding(array $aOptions = array())
    {
        /**
         * option -v [if you want the script tell you - dump of sql]
         */

        if (isset($aOptions['v'])) { $bDumpSql = true;}
        else { $bDumpSql = false; }

        /**
         * option -p [portail]
         */

        if (isset($aOptions['p'])) {

            $sPortal = $aOptions['p'];
            define('ENTITY_FINAL_NAME', preg_replace('/Batch/', $sPortal, ENTITY_NAMESPACE));
        }
        else {

            echo 'Error: you must indicated the Entity Path';
            exit;
        }

        /**
         * option -r [yes/no]
         */

        if (isset($aOptions['r']) && $aOptions['r'] === 'yes') { $sRewrite = $aOptions['r']; }
        else { $sRewrite = 'no'; }

        /**
         * option -c [create table]
         */

        if (isset($aOptions['c'])) { $bCreate = true; }
        else { $bCreate = false; }

        /**
         * option -e [create entity and models]
         */

        if (isset($aOptions['e'])) { $bCreateEntity = true; }
        else { $bCreateEntity = false; }

        /**
         * option -f [create models if not exists]
         */

        if (isset($aOptions['f'])) {

            $bCreateModelIfNotExists = true;
            $bCreateEntity = true;
        }
        else {

            $bCreateModelIfNotExists = false;
        }

        /**
         * option -a [indicated the sql json file]
         */

        if (isset($aOptions['a'])) { $sSqlJsonFile = $aOptions['a']; }
        else { $sSqlJsonFile = false; }

        /**
         * option -b [indicated the sql json]
         */

        if (isset($aOptions['b'])) { $sSqlJson = $aOptions['b']; }
        else { $sSqlJson = false; $sSqlJsonFile = str_replace('Batch', '', __DIR__).'Db.conf'; }

        /**
         * option -g [indicate the Entities directory]
         */

        if (isset($aOptions['g'])) { $sEntitiesPath = $aOptions['g']; }
        else { $sEntitiesPath = ''; }

        /**
         * option -h [indicate the Models directory]
         */

        if (isset($aOptions['h'])) { $sModelsPath = $aOptions['h']; }
        else { $sModelsPath = ''; }

        /**
         * option -o [passed an array of options)
         */

        if (isset($aOptions['o'])) { $options = $aOptions['o']; }
        else { $options = []; }

        /**
         * option -i [indicated the const json file to manage annotation in files]
         */

        if (isset($aOptions['i'])) { $oConstJson = json_decode(file_get_contents($aOptions['i']));}
        else { $oConstJson = '../Const.conf'; }

        if (is_object($oConstJson)) {

            foreach ($oConstJson as $sKey => $mValue) {

                if (is_string($mValue) || is_int($mValue) || is_float($mValue)) {

                    if (!defined(strtoupper($sKey))) { define(strtoupper($sKey), $mValue); }
                }
            }
        }

        if ($sSqlJsonFile !== false) { $oJson = json_decode(file_get_contents($sSqlJsonFile)); }
        else { $oJson = json_decode($sSqlJson); }

        //$this->constructConfFileOfEntities($options, $sEntitiesPath);

        $oConnection = $oJson->configuration;

        if (!defined('SQL_FIELD_NAME_SEPARATOR')) {

            if ($oConnection->type == 'mysql') {

                define('SQL_FIELD_NAME_SEPARATOR', '`');
            }
            else {

                define('SQL_FIELD_NAME_SEPARATOR', '');
            }
        }

        /**
         * scaffolding of the database
         */

        if ($bCreate === true) {

            $oContainer = new DbContainer;

            $oContainer->setDbName($oConnection->db)
                ->setHost($oConnection->host)
                ->setName($oConnection->db)
                ->setPassword($oConnection->password)
                ->setType($oConnection->type)
                ->setUser($oConnection->user);

            $oPdo = Db::connect($oContainer);

            foreach ($oConnection->tables as $sTableName => $oOneTable) {

                foreach ($oOneTable->fields as $sFieldName => $oOneField) {

                    if (isset($oOneField->many_to_many)) {

                        if (!isset($oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many})) {

                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many} = new \stdClass();
                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields = new \stdClass();

                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$sTableName} = new \stdClass();
                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$sTableName}->type = $oOneField->type;
                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$sTableName}->key = 'primary';

                            if (isset($oOneField->null)) {

                                $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$sTableName}->null = $oOneField->null;
                            }

                            if (isset($oOneField->unsigned)) {

                                $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$sTableName}->unsigned = $oOneField->unsigned;
                            }

                            foreach ($oConnection->tables->{$oOneField->many_to_many}->fields as $sNameOfManyToManyField => $oField) {

                                if (isset($oField->key) && $oField->key == 'primary') { $sFieldOfManyToMany = $oField; }
                            }

                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$oOneField->many_to_many} = new \stdClass();
                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$oOneField->many_to_many}->type = $sFieldOfManyToMany->type;
                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$oOneField->many_to_many}->key = 'primary';
                            //@todo : attribute ne se rajoute pas en field donc erreur dans jointure
                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$oOneField->many_to_many}->join = $oOneField->many_to_many;
                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$oOneField->many_to_many}->join_by_field = 'id';
                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$sTableName}->join = $sTableName;
                            $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$sTableName}->join_by_field = 'id';

                            $oConnection->tables->{$oOneField->many_to_many}->fields->{'id'}->join = array();
                            $oConnection->tables->{$oOneField->many_to_many}->fields->{'id'}->join_by_field = array();
                            $oConnection->tables->{$oOneField->many_to_many}->fields->{'id'}->join[] = $sTableName.'_'.$oOneField->many_to_many;
                            $oConnection->tables->{$oOneField->many_to_many}->fields->{'id'}->join_by_field[] = 'id_'.$oOneField->many_to_many;
                            $oConnection->tables->{$sTableName}->fields->{'id'}->join = array();
                            $oConnection->tables->{$sTableName}->fields->{'id'}->join_by_field = array();
                            $oConnection->tables->{$sTableName}->fields->{'id'}->join[] = $sTableName.'_'.$oOneField->many_to_many;
                            $oConnection->tables->{$sTableName}->fields->{'id'}->join_by_field[] = 'id_'.$oOneField->many_to_many;

                            if (isset($sFieldOfManyToMany->null)) {

                                $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$oOneField->many_to_many}->null = $sFieldOfManyToMany->null;
                            }

                            if (isset($sFieldOfManyToMany->unsigned)) {

                                $oConnection->tables->{$sTableName.'_'.$oOneField->many_to_many}->fields->{'id_'.$oOneField->many_to_many}->unsigned = $sFieldOfManyToMany->unsigned;
                            }
                        }
                    }

                    if (isset($oOneField->join)) {

                        if (isset($oOneField->join_by_field)) { $sJoinByField = $oOneField->join_by_field; }
                        else { $sJoinByField = $oOneField->join; }

                        if (is_string($oOneField->join)) { $aOneFieldJoin = array($oOneField->join); }
                        else { $aOneFieldJoin = $oOneField->join; }

                        if (is_string($sJoinByField)) { $aJoinByField = array($sJoinByField); }
                        else { $aJoinByField = $sJoinByField; }

                        foreach ($aOneFieldJoin as $iKey => $sOneFieldJoin) {

                            $sJoinByField = $aJoinByField[$iKey];

                            if (isset($oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->key)
                                && $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->key == 'primary'
                                && !isset($oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join)) {

                                $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join = array();
                                $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join[0] = $sTableName;
                                $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join_by_field[0] = $sFieldName;
                            }
                            else if (isset($oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->key)
                                && $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->key == 'primary'
                                && isset($oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join)
                                && is_array($oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join)
                                && !in_array($sTableName, $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join)) {

                                $iIndex = count($oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join);
                                $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join[$iIndex] = $sTableName;
                                $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join_by_field[$iIndex] = $sFieldName;
                            }
                            else if (!isset($oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join)) {

                                $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join = $sTableName;
                                $oConnection->tables->{$sOneFieldJoin}->fields->{$sJoinByField}->join_by_field = $sFieldName;
                            }
                        }
                    }
                }
            }

            //var_dump($oConnection->tables);

            foreach ($oConnection->tables as $sTableName => $oOneTable) {

                $query = "SELECT 1 FROM ".$sTableName."";
                $results = $oPdo->query($query);

                if ($results) {
                    $tableExists = true;
                    $query = 'DESC ' . SQL_FIELD_NAME_SEPARATOR . $sTableName . SQL_FIELD_NAME_SEPARATOR;
                    $results = $oPdo->query($query);
                    $tableDesc = [];

                    if ($results) {
                        foreach ($results as $key => $one) {

                            $one[1] = str_replace('int(11)', 'int', $one[1]);

                            $tableDesc[$one[0]] = [
                                'type' => preg_replace('/^([a-zA-Z]+).*$/', '$1', $one[1]),
                                'value' => preg_replace('/^[a-zA-Z]+\(?([^\)]*)\)?.*$/', '$1', $one[1]),
                                'null' => $one[2] == 'NO' ? false : true,
                                'key' => $one[3],
                                'default' => $one[4],
                                'extra' => $one[5],
                                'unsigned' => preg_match('/unsigned/', $one[1]) ? true : false,
                                'table_exists' => false
                            ];
                        }
                    }

                } else {
                    $tableExists = false;
                    $sQuery = 'CREATE TABLE IF NOT EXISTS ' . SQL_FIELD_NAME_SEPARATOR . $sTableName . SQL_FIELD_NAME_SEPARATOR . ' (';
                }

                $aIndex = array();
                $aUnique = array();
                $aPrimaryKey = array();
                //var_dump($oConnection->tables);
                foreach ($oOneTable->fields as $sFieldName => $oOneField) {

                    $field = SQL_FIELD_NAME_SEPARATOR.$sFieldName.SQL_FIELD_NAME_SEPARATOR.' '.$oOneField->type;

                    if (isset($oOneField->precision) && isset($oOneField->scale) && in_array($oOneField->type, $this->decimalTypes)) {

                        $field .= '("'.$oOneField->precision.', '.$oOneField->scale.'") ';
                    }
                    else if (isset($oOneField->values) && $oOneField->type === 'enum' && is_array($oOneField->values)) {

                        $field .= '("'.implode('","', $oOneField->values).'") ';
                    }
                    else if (isset($oOneField->value) && (is_int($oOneField->value) || preg_match('/^[0-9,]+$/', $oOneField->value))) {

                        $field .= '('.$oOneField->value.') ';
                    }

                    if (isset($oOneField->unsigned) && $oOneField->unsigned === true) {

                        $field .= ' UNSIGNED ';
                    }

                    if (isset($oOneField->null) && $oOneField->null === true) { $field .= ' NULL '; }
                    else  { $field .= ' NOT NULL '; }

                    if (isset($oOneField->default) && is_string($oOneField->default)) {

                        $field .= ' DEFAULT "'.$oOneField->default.'" ';
                    }
                    else if (isset($oOneField->default)) {

                        $field .= ' DEFAULT '.$oOneField->default.' ';
                    }

                    if (isset($oOneField->autoincrement) && $oOneField->autoincrement === true) {

                        $field .= ' AUTO_INCREMENT ';
                    }

                    if ($tableExists !== true) {
                        $sQuery .= $field . ', ';

                        if (isset($oOneField->key) && $oOneField->key === 'primary') { $aPrimaryKey[] = $sFieldName; }
                        else if (isset($oOneField->key) && $oOneField->key === 'index') { $aIndex[] = $sFieldName; }
                        else if (isset($oOneField->key) && $oOneField->key === 'unique') { $aUnique[] = $sFieldName; }

                        if (isset($oOneField->join) && is_string($oOneField->join)) {

                            if (isset($oOneField->constraint) && is_string($oOneField->constraint)) {

                                $sQuery .= ' CONSTRAINT '.$oOneField->constraint.' ';
                            }

                            $sQuery .= 'FOREIGN KEY('.$sFieldName.') REFERENCES '.$oOneField->join.'('.$oOneField->join_by_field.') ';

                            if (isset($oOneField->join_delete) && is_string($oOneField->join_delete)) {

                                $sQuery .= ' ON DELETE '.$oOneField->join_delete.' ';
                            }

                            if (isset($oOneField->join_update) && is_string($oOneField->join_update)) {

                                $sQuery .= ' ON UPDATE '.$oOneField->join_update.' ';
                            }

                            $sQuery .= ',';
                        }
                    } else if (isset($tableDesc[$sFieldName])) {

                        $tableDesc[$sFieldName]['table_exists'] = true;
                        $futurField = $this->createFieldByArray($sTableName, $sFieldName, $tableDesc[$sFieldName], 'MODIFY');
                        $field = 'ALTER TABLE ' . SQL_FIELD_NAME_SEPARATOR . $sTableName . SQL_FIELD_NAME_SEPARATOR . ' MODIFY '.$field;

                        if ($futurField !== $field) {
                            if ($bDumpSql) {
                                echo $field . "\n";
                            } else if ($oPdo->query($field) === false) {

                                echo "\n[ERROR SQL] " . $oPdo->errorInfo()[2] . " for the table " . $sTableName . "\n";
                                echo "\n" . $field . "\n";
                            }
                        }
                    } else if (!isset($tableDesc[$sFieldName]) && isset($oOneField)) {

                        $tableDesc[$sFieldName]['table_exists'] = true;
                        $futurField = $this->createFieldByArray($sTableName, $sFieldName, (array)$oOneField, 'ADD');
                        $field = 'ALTER TABLE ' . SQL_FIELD_NAME_SEPARATOR . $sTableName . SQL_FIELD_NAME_SEPARATOR . ' ADD '.$field;

                        if ($futurField !== $field) {
                            if ($bDumpSql) {
                                echo $field . "\n";
                            } else if ($oPdo->query($field) === false) {

                                echo "\n[ERROR SQL] " . $oPdo->errorInfo()[2] . " for the table " . $sTableName . "\n";
                                echo "\n" . $field . "\n";
                            }
                        }
                    } else {
                        $tableDesc[$sFieldName]['table_exists'] = true;
                        $field = 'ALTER TABLE ' . SQL_FIELD_NAME_SEPARATOR . $sTableName . SQL_FIELD_NAME_SEPARATOR . ' DROP COLUMN '.$sFieldName;
                        if ($bDumpSql) {
                            echo $field."\n";
                        } else  if ($oPdo->query($field) === false) {

                            echo "\n[ERROR SQL] ".$oPdo->errorInfo()[2]." for the table ".$sTableName."\n";
                            echo "\n".$field."\n";
                        }
                    }
                }

                if ($tableExists !== true) {
                    if (count($aPrimaryKey) > 0) {
                        $sQuery .= 'PRIMARY KEY(' . implode(',', $aPrimaryKey) . ') , ';
                    }

                    if (count($aIndex) > 0) {
                        $sQuery .= 'KEY(' . implode(',', $aIndex) . ') , ';
                    }

                    if (count($aUnique) > 0) {
                        $sQuery .= 'UNIQUE KEY ' . $aUnique[0] . ' (' . implode(',', $aUnique) . ') , ';
                    }

                    if (isset($oOneTable->index)) {

                        foreach ($oOneTable->index as $sIndexName => $aFields) {

                            $sQuery .= 'KEY ' . $sIndexName . ' (' . implode(',', $aFields) . ') , ';
                        }
                    }

                    if (isset($oOneTable->unique)) {

                        foreach ($oOneTable->unique as $sIndexName => $aFields) {

                            $sQuery .= 'KEY ' . $sIndexName . ' (' . implode(',', $aFields) . ') , ';
                        }
                    }

                    $sQuery = substr($sQuery, 0, -2);
                    $sQuery .= ')';

                    if (isset($oOneTable->engine)) {
                        $sQuery .= ' ENGINE=' . $oOneTable->engine . ' ';
                    }
                    if (isset($oOneTable->auto_increment)) {
                        $sQuery .= ' AUTO_INCREMENT=' . $oOneTable->auto_increment . ' ';
                    }
                    if (isset($oOneTable->default_charset)) {
                        $sQuery .= ' DEFAULT CHARSET=' . $oOneTable->default_charset . ' ';
                    }

                    if ($bDumpSql) {
                        echo $sQuery . "\n";
                    } else if ($oPdo->query($sQuery) === false) {

                        echo "\n[ERROR SQL] " . $oPdo->errorInfo()[2] . " for the table " . $sTableName . "\n";
                        echo "\n" . $sQuery . "\n";
                    }
                }


            }

            // Column not in the database but is in the config file

            foreach ($tableDesc as $key => $one) {

                if (!$one['table_exists']) {

                    $field = $this->createFieldByArray($sTableName, $sFieldName, $tableDesc[$sFieldName], 'ADD');

                    if ($bDumpSql) {
                        echo $field . "\n";
                    } else if ($oPdo->query($field) === false) {

                        echo "\n[ERROR SQL] " . $oPdo->errorInfo()[2] . " for the table " . $sTableName . "\n";
                        echo "\n" . $field . "\n";
                    }
                }
            }
        }
        //var_dump($oConnection->tables);
        /**
         * scaffolding of the entities
         */

        if ($bCreateEntity) {

            foreach ($oConnection->tables as $sTableName => $oOneTable) {

                $sContentFile = '<?php
	
/**
 * Entity to '.$sTableName.'
 *
 * @category  	\\'.CATEGORY.'
 * @package   	'.ENTITY_FINAL_NAME.'
 * @author    	'.AUTHOR.'
 * @copyright 	'.COPYRIGHT.'
 * @license   	'.LICENCE.'
 * @version   	Release: '.VERSION.'
 * @filesource	'.FILESOURCE.'
 * @link      	'.LINK.'
 * @since     	1.0
 */
namespace '.preg_replace('/^\\\\/', '', ENTITY_FINAL_NAME).';

use \Attila\core\Entity as Entity;
use \Attila\Orm as Orm;

/**
 * @ORM\Table(name="'.$sTableName.'")
 * @ORM\Entity(repositoryClass="'.str_replace('Entity', 'Model', preg_replace('/^\\\\/', '', ENTITY_FINAL_NAME)).'\\'.$sTableName.'")
 */
class '.$sTableName.' extends Entity 
{';

                foreach ($oOneTable->fields as $sFieldName => $oField) {
                    if (!isset($oField->type)) { var_dump($sTableName,$sFieldName, $oOneTable); }
                    if ($oField->type == 'enum' || $oField->type == 'char' || $oField->type == 'varchar' || $oField->type == 'text'
                        || $oField->type == 'date' || $oField->type == 'datetime' || $oField->type == 'time' || $oField->type == 'binary'
                        || $oField->type == 'varbinary' || $oField->type == 'blob' || $oField->type == 'tinyblob'
                        || $oField->type == 'tinytext' || $oField->type == 'mediumblob' || $oField->type == 'mediumtext'
                        || $oField->type == 'longblob' || $oField->type == 'longtext' || $oField->type == 'char varying'
                        || $oField->type == 'long varbinary' || $oField->type == 'long varchar' || $oField->type == 'long') {

                        $sType = 'string';
                    }
                    else if ($oField->type == 'int' || $oField->type == 'smallint' || $oField->type == 'tinyint'
                        || $oField->type == 'bigint' || $oField->type == 'mediumint' || $oField->type == 'timestamp'
                        || $oField->type == 'year' || $oField->type == 'integer' || $oField->type == 'int1' || $oField->type == 'int2'
                        || $oField->type == 'int3' || $oField->type == 'int4' || $oField->type == 'int8' || $oField->type == 'middleint') {

                        $sType = 'int';
                    }
                    else if ($oField->type == 'bit' || $oField->type == 'bool' || $oField->type == 'boolean') {

                        $sType = 'bool';
                    }
                    else if ($oField->type == 'float' || $oField->type == 'decimal' || $oField->type == 'double'
                        || $oField->type == 'precision' || $oField->type == 'real' || $oField->type == 'float4'
                        || $oField->type == 'float8' || $oField->type == 'numeric') {

                        $sType = 'float';
                    }
                    else if ($oField->type == 'set') {

                        $sType = 'array';
                    }

                    $optionsAnnotation = '';

                    if (($sType === 'int' || $sType === 'float') || isset($oField->default)) {

                        $optionsAnnotation = ', options={';

                        if ($sType === 'int' || $sType === 'float') {
                            $optionsAnnotation .= '"unsigned":';

                            if (isset($oField->unsigned) && $oField->unsigned) { $optionsAnnotation .= 'true,'; }
                            else { $optionsAnnotation .= 'false,'; }
                        }

                        if (isset($oField->default)) {
                            $optionsAnnotation .= ' "default":' . $oField->default . ',';
                        }

                        $optionsAnnotation = substr($optionsAnnotation, 0, -1);
                        $optionsAnnotation .= '}';
                    }

                    $sContentFile .= '
	/**
	 * @var '.$sType.'
	 *
     * @ORM\Column(name="'.$sFieldName.'", type="'.$oField->type.'"'.$optionsAnnotation.
                        ''.($oField->type === 'enum'?', columnDefinition="\''.implode('\',\'', $oField->values).'\'"':'').
                        ''.(isset($oField->value)?', length='.$oField->value:'').
                        ''.(isset($oField->precision)?', precision='.$oField->precision:'').
                        ''.(isset($oField->scale)?', scale='.$oField->scale:'').
                        ''.(isset($oField->key) && $oField->key == 'unique'?', unique=true':'').
                        ''.(isset($oField->null) && $oField->null === true?', nullable=true':', nullable=false').
                        ')
';

                    if (isset($oField->key) && $oField->key == 'primary') {

                        $sContentFile .= '	 * @ORM\Id'."\n";
                    }

                    if (isset($oField->autoincrement) && $oField->autoincrement === true) {

                        $sContentFile .= '	 * @ORM\GeneratedValue(strategy="AUTO")'."\n";
                    }

                    if (isset($oField->property)) {

                        //$sContentFile .= '	 * @map '.$oField->property.''."\n";
                        $entityFieldName = $oField->property;
                    } else {
                        $entityFieldName = $sFieldName;
                    }

                    $sContentFile .= '	 */
    private $'.$entityFieldName.''.(isset($oField->default)?' = '.$oField->default:'').';
	';
                    if (isset($oField->join)) {

                        if (!is_array($oField->join)) {

                            $oField->join = array($oField->join);
                            if (isset($oField->join_alias)) { $oField->join_alias = array($oField->join_alias); }
                            if (isset($oField->join_by_field)) { $oField->join_by_field = array($oField->join_by_field); }
                        }

                        for ($i = 0 ; $i < count($oField->join) ; $i++) {

                            if (isset($oField->join_alias[$i])) { $sJoinUsedName = $oField->join_alias[$i]; }
                            else { $sJoinUsedName = $oField->join[$i]; }

                            $sContentFile .= '
	/**
	 * @ORM\OneToMany(target="'.$sJoinUsedName.'", targetField="'.$oField->join_by_field[0].'", mappedBy="'.$sTableName.'", cascade={"remove", "persist"}, fetch="EAGER")
	 */
    private $'.$sJoinUsedName.' = null;

	';
                        }
                    }
                }

                foreach ($oOneTable->fields as $sFieldName => $oField) {
                    if (!isset($oField->type)) { var_dump($sTableName,$sFieldName, $oField); }
                    if ($oField->type == 'enum' || $oField->type == 'char' || $oField->type == 'varchar' || $oField->type == 'text'
                        || $oField->type == 'date' || $oField->type == 'datetime' || $oField->type == 'time' || $oField->type == 'binary'
                        || $oField->type == 'varbinary' || $oField->type == 'blob' || $oField->type == 'tinyblob'
                        || $oField->type == 'tinytext' || $oField->type == 'mediumblob' || $oField->type == 'mediumtext'
                        || $oField->type == 'longblob' || $oField->type == 'longtext' || $oField->type == 'char varying'
                        || $oField->type == 'long varbinary' || $oField->type == 'long varchar' || $oField->type == 'long') {

                        $sType = 'string';
                    }
                    else if ($oField->type == 'int' || $oField->type == 'smallint' || $oField->type == 'tinyint'
                        || $oField->type == 'bigint' || $oField->type == 'mediumint' || $oField->type == 'timestamp'
                        || $oField->type == 'year' || $oField->type == 'integer' || $oField->type == 'int1' || $oField->type == 'int2'
                        || $oField->type == 'int3' || $oField->type == 'int4' || $oField->type == 'int8'
                        || $oField->type == 'middleint') {

                        $sType = 'int';
                    }
                    else if ($oField->type == 'bit' || $oField->type == 'bool' || $oField->type == 'boolean') {

                        $sType = 'bool';
                    }
                    else if ($oField->type == 'float' || $oField->type == 'decimal' || $oField->type == 'double'
                        || $oField->type == 'precision' || $oField->type == 'real' || $oField->type == 'float4'
                        || $oField->type == 'float8' || $oField->type == 'numeric') {

                        $sType = 'float';
                    }
                    else if ($oField->type == 'set') {

                        $sType = 'array';
                    }

                    $sContentFile .= '
	/**
	 * @return '.$sType.'
	 */
	public function get_'.$sFieldName.'() '.(isset($oField->key) && $oField->key !== 'primary'?' : '.$sType:'').'
	{
		return $this->'.$sFieldName.';
	}

	/**
	 * @param  '.$sType.' $'.$sFieldName.' 
	 * @return '.ENTITY_FINAL_NAME.'\\'.$sTableName.'
	 */
	public function set_'.$sFieldName.'('.$sType.' $'.$sFieldName.') 
	{
		$this->'.$sFieldName.' = $'.$sFieldName.';
		return $this;
	}
	';
                    if (isset($oField->join)) {

                        /**
                         * you could add join_by_field when you have a field name different in the join
                         * @example		ON menu1.id = menu2.parent_id
                         *
                         * if the left field and the right field have the same name, you could ignore this param.
                         */

                        if (!is_array($oField->join)) {

                            $oField->join = array($oField->join);
                            if (isset($oField->join_alias)) { $oField->join_alias = array($oField->join_alias); }
                            if (isset($oField->join_by_field)) { $oField->join_by_field = array($oField->join_by_field); }
                        }

                        for ($i = 0 ; $i < count($oField->join) ; $i++) {

                            if (isset($oField->join_by_field[$i])) { $sJoinByField = $oField->join_by_field[$i]; }
                            else { $sJoinByField = $sFieldName; }

                            if (isset($oField->join_alias[$i])) { $sJoinUsedName = $oField->join_alias[$i]; }
                            else { $sJoinUsedName = $oField->join[$i]; }

                            $sContentFile .= '
	/**
	 * get '.$sJoinUsedName.'
	 *
	 * @param  array $aWhere
	 * @return ';

                            $sKey2 = '';
                            $iPrimaryKey = 0;

                            if (count($oField->join) == 1) {

                                if (isset($oConnection->tables->{$oField->join[0]}->fields->{$oField->join_by_field[0]}->key)) {

                                    $sKey2 = $oConnection->tables->{$oField->join[0]}->fields->{$oField->join_by_field[0]}->key;
                                    $iPrimaryKey = 0;

                                    foreach ($oConnection->tables->{$oField->join[0]}->fields as $iKey2 => $oField2) {

                                        if (isset($oField2->key) && $oField2->key == 'primary') {

                                            $iPrimaryKey++;
                                        }
                                    }
                                }
                            }

                            if ($sKey2 == 'primary' && $iPrimaryKey == 1) {

                                $sContentFile .= ENTITY_FINAL_NAME.'\\'.$sTableName;
                            }
                            else if (isset($oField->key) && ($oField->key == 'primary' || in_array('primary', $oField->key))) {

                                $sContentFile .= 'array';
                            }
                            else {

                                $sContentFile .= ENTITY_FINAL_NAME.'\\'.$sTableName;
                            }

                            $sContentFile .= '
	 */
	public function get_'.$sJoinUsedName.'(array $where = array())
	{
		if ($this->'.$sJoinUsedName.' === null) {

			$orm = new Orm;

			$orm->select(array(\'*\'))
			    ->from(\''.$oField->join[$i].'\');
												   
	        $where[\''.$sJoinByField.'\'] = $this->get_'.$sFieldName.'();
											
													  ';

                            $sContentFile .= '
            ';
                            if ($sKey2 == 'primary' && $iPrimaryKey == 1) {

                                $sContentFile .= '$aResult';
                            }
                            else if (isset($oField->key) && ($oField->key == 'primary' || in_array('primary', $oField->key))) {

                                $sContentFile .= '$this->'.$oField->join[$i].'';
                            }
                            else {

                                $sContentFile .= '$aResult';
                            }

                            $sContentFile .= ' = $orm->where($where)
						           ->load(false, \''.ENTITY_FINAL_NAME.'\\\\\');';

                            if ((!isset($oField->key) || (isset($oField->key) && $oField->key != 'primary'
                                        && (is_array($oField->key) && !in_array('primary', $oField->key))))
                                || ($sKey2 == 'primary' && $iPrimaryKey == 1)) {

                                $sContentFile .= "\n\n".'          if (count($aResult) > 0) { $this->'.$sJoinUsedName.' = $aResult[0]; }
          else { $this->'.$sJoinUsedName.' = array(); }';
                            }

                            $sContentFile .= '
        }

		return $this->'.$sJoinUsedName.';
	}
	
	/**
	 * set '.$sJoinUsedName.' 
	 *
	 * @param  '.ENTITY_NAMESPACE.'\\'.$oField->join[$i].'  $'.$sJoinUsedName.' '.$oField->join[$i].' entity
	 * 
	 * @return ';

                            if (isset($oField->key) && ($oField->key == 'primary' || (is_array($oField->key) && in_array('primary', $oField->key)))) {

                                $sContentFile .= 'array';
                            }
                            else {

                                $sContentFile .= ENTITY_NAMESPACE.'\\'.$sTableName;
                            }

                            $sContentFile .= '
	 */
	public function set_'.$sJoinUsedName.'(';

                            if (isset($oField->key) && ($oField->key == 'primary' || (is_array($oField->key) && in_array('primary', $oField->key)))) {

                                $sContentFile .= 'array';
                            }
                            else {

                                $sContentFile .= ENTITY_NAMESPACE.'\\'.$oField->join[$i];
                            }

                            $sContentFile .= ' $'.$sJoinUsedName.') : '.$sTableName.'
	{
		$this->'.$sJoinUsedName.' = $'.$sJoinUsedName.';
		return $this;
	}
';
                        }
                    }
                }

                $sContentFile .= '}';

                file_put_contents($sEntitiesPath.$sTableName.'.php', $sContentFile);

                if ($bCreateModelIfNotExists === false || ($bCreateModelIfNotExists === true
                        && !file_exists($sModelsPath.$sTableName.'.php'))) {

                    $sContentFile = '<?php
	
/**
 * Model to '.$sTableName.'
 *
 * @category  	\\'.CATEGORY.'
 * @package   	'.MODEL_NAMESPACE.'
 * @author    	'.AUTHOR.'
 * @copyright 	'.COPYRIGHT.'
 * @license   	'.LICENCE.'
 * @version   	Release: '.VERSION.'
 * @filesource	'.FILESOURCE.'
 * @link      	'.LINK.'
 * @since     	1.0
 */
namespace Venus\src\\'.$sPortal.'\Model;

use \Venus\core\Model as Model;
	
/**
 * Model to '.$sTableName.'
 *
 * @category  	\\'.CATEGORY.'
 * @package   	'.MODEL_NAMESPACE.'
 * @author    	'.AUTHOR.'
 * @copyright 	'.COPYRIGHT.'
 * @license   	'.LICENCE.'
 * @version   	Release: '.VERSION.'
 * @filesource	'.FILESOURCE.'
 * @link      	'.LINK.'
 * @since     	1.0
 */
class '.$sTableName.' extends Model 
{
}'."\n";

                    file_put_contents($sModelsPath.$sTableName.'.php', $sContentFile);
                }
            }
        }

        echo "\n\n";
        echo Bash::setBackground("                                                                            ", 'green');
        echo Bash::setBackground("          [OK] Success                                                      ", 'green');
        echo Bash::setBackground("                                                                            ", 'green');
        echo "\n\n";
    }

    /**
     * @param array $options
     */
    public function scaffolding(array $options = array())
    {
        /**
         * option -v [if you want the script tell you - dump of sql]
         */

        if (isset($options['v'])) { $this->setVerbose(true); }
        else { $this->setVerbose(false); }

        /**
         * option -p [portail]
         */

        if (isset($options['p'])) {

            //$sPortal = $aOptions['p'];
            //define('ENTITY_FINAL_NAME', preg_replace('/Batch/', $sPortal, ENTITY_NAMESPACE));
            $this->setPortal($options['p']);
        }
        else {

            echo 'Error: you must indicated the Entity Path';
            exit;
        }

        /**
         * option -g [indicate the Entities directory]
         */

        if (isset($options['g'])) { $this->setEntitiesPath($options['g']); }

        /**
         * option -h [indicate the Models directory]
         */

        if (isset($options['h'])) { $this->setModelsPath($options['h']); }

        /**
         * option -f [indicate the json file]
         */

        if (isset($options['f'])) { $this->setConfFile($options['f']); }

        /**
         * option -e [indicate the Entities namespace]
         */

        if (isset($options['e'])) { $this->setEntitiesNamespace($options['e']); }

        /**
         * option -m [indicate the Models namespace]
         */

        if (isset($options['m'])) { $this->setModelsNamespace($options['m']); }

        /**
         * phase 1 : construct the conf file from the entities
         */
        $this->createConfFileOfEntities();

        /**
         * phase 2 : construct the entities from the conf file
         */
        $this->createEntitiesOfConfFile();

        /**
         * phase 3 : construct the database
         */
        $this->createDatabase($this->getConfFile(), $this->getEntitiesPath());
    }

    /**
     * @param string $tableName
     * @param string $fieldName
     * @param array $tableContent
     * @param string $action
     * @return string
     */
    private function createFieldByArray(string $tableName, string $fieldName, array $tableContent, string $action) : string  {

        $field = 'ALTER TABLE ' . SQL_FIELD_NAME_SEPARATOR . $tableName . SQL_FIELD_NAME_SEPARATOR . ' '.$action.' ' . SQL_FIELD_NAME_SEPARATOR . $fieldName . SQL_FIELD_NAME_SEPARATOR . ' ' . $tableContent['type'];

        if (isset($tableContent['value'])) {

            $field .= '(' . $tableContent['value'] . ') ';
        }
        else if ($tableContent['type'] == 'int') {

            $field .= '(11) ';
        }

        if (isset($tableContent['unsigned']) && $tableContent['unsigned']) {

            $field .= ' UNSIGNED ';
        }

        if ($tableContent['null']) {
            $field .= ' NULL ';
        } else {
            $field .= ' NOT NULL ';
        }

        if (isset($tableContent['default'])) {
            $field .= ' DEFAULT "' . $tableContent['default'] . '" ';
        }


        if (isset($tableContent['extra']) && $tableContent['extra'] === 'auto_increment') {

            $field .= ' AUTO_INCREMENT ';
        }

        return $field;
    }

    /**
     * @param string $sqlJsonFile
     * @param string $entitiesPath
     */
    private function constructConfFileOfEntities(string $sqlJsonFile, string $entitiesPath) {

        $entitiesPath = substr($entitiesPath, 0, -1);
        $files = scandir($entitiesPath);
        $readerPhpdoc = new Reader;

        foreach (explode(';', $sqlJsonFile) as $one) {

            $jsonFileObject =  json_decode(file_get_contents($one));

            foreach ($files as $oneFile) {

                $tableName = str_replace('.php', '', $oneFile);

                if (filemtime($entitiesPath.DIRECTORY_SEPARATOR.$oneFile) > filemtime($one) && $oneFile !== '.' && $oneFile !== '..') {

                    $className = preg_replace('#^.+\\\\..\\\\bundles(\\\\.+)\.php$#', '\\Venus$1', str_replace(DIRECTORY_SEPARATOR, '\\', $entitiesPath.DIRECTORY_SEPARATOR.$oneFile));
                    $className =  str_replace('\\app', '', $className);

                    $reflectionClass  = new \ReflectionClass($className);
                    $reflectionProperties = $reflectionClass->getProperties();

                    foreach ($reflectionProperties as $oneProperty) {

                        $phpDocProperty = $readerPhpdoc->getPhpDocOfProperty($className, $oneProperty->name);
                        $result = new \stdClass();

                        if (isset($phpDocProperty['ORM\\Column'])
                            && isset($jsonFileObject->configuration->tables->{$tableName}->fields->{$phpDocProperty['ORM\\Column']['name']})){

                            if ($phpDocProperty['ORM\\Column']['type'] == "enum"
                                && isset($phpDocProperty['ORM\\Column']['columnDefinition'])) {

                                $result->values = explode(",", $phpDocProperty['ORM\\Column']['columnDefinition']);
                            }

                            if ($phpDocProperty['ORM\\Column']['type'] == "decimal"
                                && isset($phpDocProperty['ORM\\Column']['precision'])
                                && isset($phpDocProperty['ORM\\Column']['scale'])) {

                                $result->precision = $phpDocProperty['ORM\\Column']['precision'];
                                $result->scale = $phpDocProperty['ORM\\Column']['scale'];
                            }

                            $result->type = $phpDocProperty['ORM\\Column']['type'];

                            if (isset($phpDocProperty['ORM\\Column']['length'])) {
                                $result->value = $phpDocProperty['ORM\\Column']['length'];
                            }

                            if (isset($phpDocProperty['ORM\\Column']['unique']) && $phpDocProperty['ORM\\Column']['unique'] == true) {
                                $result->key = 'unique';
                            }

                            if (isset($phpDocProperty['ORM\\Column']['nullable']) && $phpDocProperty['ORM\\Column']['nullable'] === true) {
                                $result->null = true;
                            }
                            else  {
                                $result->null = false;
                            }

                            if (isset($phpDocProperty['ORM\\Column']['options'])
                                && isset($phpDocProperty['ORM\\Column']['options']->unsigned)) {

                                $result->unsigned = $phpDocProperty['ORM\\Column']['options']->unsigned;
                            }

                            if (isset($phpDocProperty['ORM\\Column']['options'])
                                && isset($phpDocProperty['ORM\\Column']['options']->default)) {

                                $result->default = $phpDocProperty['ORM\\Column']['options']->default;
                            }

                            $nameField = $phpDocProperty['ORM\\Column']['name'];
                        }

                        if (isset($phpDocProperty['ORM\\Id'])) {
                            $result->key = "primary";
                        }

                        if (isset($phpDocProperty['ORM\\GeneratedValue'])) {

                            $result->autoincrement = true;
                        }

                        if (isset($phpDocProperty['ORM\\OneToMany'])) {

                            $result = $jsonFileObject->configuration->tables->{$tableName}->fields->{$nameField};

                            $result->join = $phpDocProperty['ORM\\OneToMany']["target"];
                            $result->join_by_field = $phpDocProperty['ORM\\OneToMany']["targetField"];

                            if (strstr($phpDocProperty['ORM\\OneToMany']["cascade"], "remove")) {
                                $result->join_delete = 'cascade';
                            }
                            else if (strstr($phpDocProperty['ORM\\OneToMany']["cascade"], "insert")) {
                                $result->join_update = 'update';
                            }
                        }

                        $jsonFileObject->configuration->tables->{$tableName}->fields->{$nameField} = $result;
                    }

                    file_put_contents($one, json_encode($jsonFileObject, JSON_PRETTY_PRINT));
                }
            }
        }
    }

    /**
     *
     */
    private function createConfFileOfEntities()
    {
        $entitiesPath = substr($this->getEntitiesPath(), 0, -1);
        $files = scandir($entitiesPath);
        $readerPhpdoc = new Reader;

        foreach (explode(';', $this->getConfFile()) as $one) {

            $jsonFileObject =  json_decode(file_get_contents($one));

            foreach ($files as $oneFile) {

                $tableName = str_replace('.php', '', $oneFile);

                if (filemtime($entitiesPath.DIRECTORY_SEPARATOR.$oneFile) > filemtime($one) && $oneFile !== '.' && $oneFile !== '..') {

                    $className = $this->getEntitiesNamespace().'\\'.$tableName;
                    $reflectionClass  = new \ReflectionClass($className);
                    $reflectionProperties = $reflectionClass->getProperties();

                    foreach ($reflectionProperties as $oneProperty) {

                        $phpDocProperty = $readerPhpdoc->getPhpDocOfProperty($className, $oneProperty->name);
                        $result = new \stdClass();

                        if (isset($phpDocProperty['ORM\\Column'])
                            && isset($jsonFileObject->tables->{$tableName}->fields->{$phpDocProperty['ORM\\Column']['name']})){

                            if (isset($phpDocProperty['ORM\\Column']['columnDefinition'])) {

                                $result->columnDefinition = $phpDocProperty['ORM\\Column']['columnDefinition'];
                            }

                            if ($phpDocProperty['ORM\\Column']['type'] == "decimal"
                                && isset($phpDocProperty['ORM\\Column']['precision'])
                                && isset($phpDocProperty['ORM\\Column']['scale'])) {

                                $result->precision = $phpDocProperty['ORM\\Column']['precision'];
                                $result->scale = $phpDocProperty['ORM\\Column']['scale'];
                            }

                            $result->type = $phpDocProperty['ORM\\Column']['type'];

                            if (isset($phpDocProperty['ORM\\Column']['length'])) {
                                $result->length = $phpDocProperty['ORM\\Column']['length'];
                            }

                            if (isset($phpDocProperty['ORM\\Column']['unique']) && $phpDocProperty['ORM\\Column']['unique'] == true) {
                                $result->unique = 'unique';
                            }

                            if (isset($phpDocProperty['ORM\\Column']['nullable']) && $phpDocProperty['ORM\\Column']['nullable'] === true) {
                                $result->nullable = true;
                            } else  {
                                $result->nullable = false;
                            }

                            if (isset($phpDocProperty['ORM\\Column']['options'])) {

                                $result->options = json_decode($phpDocProperty['ORM\\Column']['options']);
                            }

                            $nameField = $phpDocProperty['ORM\\Column']['name'];
                        }

                        if (isset($phpDocProperty['ORM\\Id'])) { $result->key = "primary"; }

                        if (isset($phpDocProperty['ORM\\GeneratedValue'])) { $result->autoincrement = true; }

                        if (isset($phpDocProperty['ORM\\OneToMany'])) {

                            $result = $jsonFileObject->tables->{$tableName}->fields->{$nameField};

                            $result->target = $phpDocProperty['ORM\\OneToMany']["target"];
                            $result->targetField = $phpDocProperty['ORM\\OneToMany']["targetField"];

                            if (isset($phpDocProperty['ORM\\OneToMany']["cascade"])) {
                                $result->cascade = $phpDocProperty['ORM\\OneToMany']["cascade"];
                            }
                        }

                        $jsonFileObject->tables->{$tableName}->fields->{$nameField} = $result;
                    }

                    file_put_contents($one, json_encode($jsonFileObject, JSON_PRETTY_PRINT));
                }
            }
        }
    }

    /**
     *
     */
    private function createEntitiesOfConfFile()
    {
        $configuration = json_decode(file_get_contents($this->getConfFile()));

        $this->defaultConnection = $configuration->default_connection;

        /**
         * search all join and create many join in two sens
         * it's just to create entity -> add OneToMany in the conf file is useless
         */
        foreach ($configuration->tables as $tableName => $values) {

            if (!isset($configuration->tables->{$tableName}->OneToAll)) {
                $configuration->tables->{$tableName}->OneToAll = [];
            }

            foreach ($values->fields as $fieldName => $valuesField) {

                /**
                 * @ManyToOne(targetEntity="Cart", cascade={"all"}, fetch="EAGER")
                 */
                if (isset($valuesField->ManyToOne) && isset($valuesField->ManyToOne->targetEntity)) {

                    $tableDestName = $valuesField->ManyToOne->targetEntity;
                    $tableDestName = preg_replace_callback('/([A-Z])/', function($match) { return '_'.strtolower($match[1]); }, $tableDestName);

                    if (!isset($configuration->tables->{$tableDestName}->OneToAll)) {
                        $configuration->tables->{$tableDestName}->OneToAll = [];
                    }

                    if (!isset($configuration->tables->{$tableDestName}->OneToAll[$tableName]->OneToMany)) {
                        $configuration->tables->{$tableDestName}->OneToAll[$tableName] = new \stdClass();
                        $configuration->tables->{$tableDestName}->OneToAll[$tableName]->OneToMany = new \stdClass();
                        $configuration->tables->{$tableDestName}->OneToAll[$tableName]->JoinColumn = new \stdClass();
                    }

                    $entityName = preg_replace_callback('/_([a-z])/', function($match) { return strtoupper($match[1]); }, $tableName);
                    $configuration->tables->{$tableDestName}->OneToAll[$tableName]->OneToMany->targetEntity = $entityName;

                    $configuration->tables->{$tableDestName}->OneToAll[$tableName]->JoinColumn->name = $fieldName;
                    $configuration->tables->{$tableDestName}->OneToAll[$tableName]->JoinColumn->referencedColumnName = $fieldName;
                }

                /**
                 * @OneToOne(targetEntity="Customer")
                 */
                if (isset($valuesField->OneToOne) && isset($valuesField->OneToOne->targetEntity)) {

                    $tableDestName = $valuesField->OneToOne->targetEntity;
                    $tableDestName = preg_replace_callback('/([A-Z])/', function($match) { return '_'.strtolower($match[1]); }, $tableDestName);

                    if (!isset($configuration->tables->{$tableDestName}->OneToAll)) {
                        $configuration->tables->{$tableDestName}->OneToAll = [];
                    }

                    if (!isset($configuration->tables->{$tableDestName}->OneToAll[$tableNlolame]->OneToOne)) {
                        $configuration->tables->{$tableDestName}->OneToAll[$tableName] = new \stdClass();
                        $configuration->tables->{$tableDestName}->OneToAll[$tableName]->OneToOne = new \stdClass();
                        $configuration->tables->{$tableDestName}->OneToAll[$tableName]->JoinColumn = new \stdClass();
                    }

                    $entityName = preg_replace_callback('/_([a-z])/', function($match) { return strtoupper($match[1]); }, $tableName);
                    $configuration->tables->{$tableDestName}->OneToAll[$tableName]->OneToOne->targetEntity = $entityName;

                    $configuration->tables->{$tableDestName}->OneToAll[$tableName]->JoinColumn->name = $fieldName;
                    $configuration->tables->{$tableDestName}->OneToAll[$tableName]->JoinColumn->referencedColumnName = $fieldName;
                }

                /**
                 * @ManyToMany(targetEntity="Customer")
                 */
                if (isset($valuesField->ManyToMany) && isset($valuesField->ManyToMany->targetEntity)) {
                    if (isset($valuesField->JoinColumn)) {

                        $tableDestName = $valuesField->ManyToMany->targetEntity;
                        $tableDestName = preg_replace_callback('/([A-Z])/', function($match) { return '_'.strtolower($match[1]); }, $tableDestName);

                        if (!isset($configuration->tables->{$tableName.'_has_'.$tableDestName}) && !isset($configuration->tables->{$tableDestName.'_has_'.$tableName})) {
                            $configuration->tables->{$tableName.'_has_'.$tableDestName} = new \stdClass();
                        }
                        $configuration->tables->{$tableDestName}->OneToAll[$tableName]->JoinColumn->name;
                        $configuration->tables->{$tableDestName}->OneToAll[$tableName]->JoinColumn->referencedColumnName;
                    }
                }
            }
        }

        /**
         * list all tables
         */

        foreach ($configuration->tables as $tableName => $values) {


            $className = preg_replace_callback('/_([a-z])/', function($match) { return strtoupper($match[1]); }, $tableName);
            $contentMethodPartOfFile = '';

            $contentFile = '<?php
	
namespace '.$this->getEntitiesNamespace().';

use \Attila\core\Entity as Entity;
use \Attila\Orm as Orm;

/**
 * @ORM\Table(name="'.$tableName.'")
 * @ORM\Entity(repositoryClass="'.$this->getModelsNamespace().'\\'.$className.'")
 */
class '.$className.' extends Entity 
{';

            foreach ($values->fields as $fieldName => $valuesField) {

                $paramName = $fieldName;
                $paramName = preg_replace_callback('/_([a-z])/', function ($match) {
                    return strtoupper($match[1]);
                }, $paramName);

                /**
                 * create the type of the parameter
                 */
                if ($valuesField->type === 'tinyint' || $valuesField->type === 'smallint' || $valuesField->type === 'mediumint' || $valuesField->type === 'bigint') {
                    $paramType = 'int';
                } elseif ($valuesField->type == 'enum' || $valuesField->type == 'char' || $valuesField->type == 'varchar'
                    || $valuesField->type == 'text' || $valuesField->type == 'date' || $valuesField->type == 'datetime'
                    || $valuesField->type == 'time' || $valuesField->type == 'binary' || $valuesField->type == 'varbinary'
                    || $valuesField->type == 'blob' || $valuesField->type == 'tinyblob' || $valuesField->type == 'tinytext'
                    || $valuesField->type == 'mediumblob' || $valuesField->type == 'mediumtext' || $valuesField->type == 'longblob'
                    || $valuesField->type == 'longtext' || $valuesField->type == 'char varying' || $valuesField->type == 'long varbinary'
                    || $valuesField->type == 'long varchar' || $valuesField->type == 'long'
                ) {

                    $paramType = 'string';

                    if ($valuesField->type == 'char' || $valuesField->type == 'binary') {
                        $valuesField->options->fixed = true;
                    }
                } else {
                    $paramType = $valuesField->type;
                }

                $contentFile .= '
    /**
     * @var ' . $paramType . '
     *
     * @ORM\Column(name="' . $fieldName . '", type="' . $valuesField->type . '"' .
                    '' . ($valuesField->type === 'enum' ? ', columnDefinition="\'' . implode('\',\'', $valuesField->values) . '\'"' : '') .
                    '' . (isset($valuesField->length) ? ', length=' . $valuesField->length : '') .
                    '' . (isset($valuesField->precision) ? ', precision=' . $valuesField->precision : '') .
                    '' . (isset($valuesField->scale) ? ', scale=' . $valuesField->scale : '') .
                    '' . (isset($valuesField->options) ? ', options=' . json_encode($valuesField->options) : '') .
                    '' . (isset($valuesField->unique) && $valuesField->unique == 'unique' ? ', unique=true' : '') .
                    '' . (isset($valuesField->nullable) && $valuesField->nullable === true ? ', nullable=true' : ', nullable=false') .
                    ')
';

                if (isset($valuesField->key) && $valuesField->key == 'primary') {

                    $contentFile .= '     * @ORM\Id' . "\n";
                }

                if (isset($valuesField->autoincrement) && $valuesField->autoincrement === true) {

                    $contentFile .= '     * @ORM\GeneratedValue(strategy="AUTO")' . "\n";
                }

                $contentFile .= '     */
    private $' . $paramName . '' . (isset($valuesField->options) && isset($valuesField->options->default) ? ' = ' . $valuesField->options->default : '') . ';
	';

                //==============================================================
                // Just the classic method (simple field of table)
                //==============================================================

                $methodName = $paramName;
                $methodName{0} = strtoupper($methodName{0});
                $contentMethodPartOfFile .= '
    /**
     * @return '.$paramType.'
     */
    public function get'.$methodName.'()  : '.$paramType.'
    {
        return $this->'.$paramName.';
    }

    /**
     * @param  '.$paramType.' $'.$paramName.' 
     * @return '.$this->getEntitiesNamespace().'\\'.$className.'
     */
    public function set'.$methodName.'('.$paramType.' $'.$paramName.') 
    {
        $this->'.$paramName.' = $'.$paramName.';
        return $this;
    }
	';
                //==============================================================
                // Just the join field
                //==============================================================

                if (isset($valuesField->OneToOne) && isset($valuesField->JoinColumn)) {

                    $contentFile .= '
	/**
     * @var ' . $this->getEntitiesNamespace() . '\\' . $valuesField->OneToOne->targetEntity . '
     *
     * @ORM\OneToOne(targetEntity="' . $valuesField->OneToOne->targetEntity . '")
	 * @ORM\JoinColumn(name="' . $valuesField->JoinColumn->name . '", referencedColumnName="' . $valuesField->JoinColumn->referencedColumnName . '")
	 */
    private $' . $valuesField->OneToOne->targetEntity . ' = null;
';
                    $methodName = $valuesField->OneToOne->targetEntity;
                    $methodName{0} = strtoupper($methodName{0});

                    $tableDestName = $valuesField->OneToOne->targetEntity;
                    $tableDestName = preg_replace_callback('/([A-Z])/', function($match) { return '_'.strtolower($match[1]); }, $tableDestName);

                    $methodKeyName = $valuesField->JoinColumn->name;
                    $methodKeyName = preg_replace_callback('/_([a-z])/', function($match) { return strtoupper($match[1]); }, $methodKeyName);
                    $methodKeyName{0} = strtoupper($methodKeyName{0});

                    $contentMethodPartOfFile .= '
    /**
     * @param  array $where
     * @return '.$this->getEntitiesNamespace().'\\'.$valuesField->OneToOne->targetEntity.'
     */
    public function get'.$methodName.'(array $where = [])
    {
        if ($this->'.$valuesField->OneToOne->targetEntity.' === null) {

            $orm = new Orm;

            $orm->select(array(\'*\'))
                ->from(\''.$tableDestName.'\');
												   
            $where[\''.$valuesField->JoinColumn->referencedColumnName.'\'] = $this->get'.$methodKeyName.'();
	        
            $this->'.$valuesField->OneToOne->targetEntity.' = $orm->where($where)->load(false, \''.$this->getEntitiesNamespace().'\\\\\');
        }
        
        return $this->'.$valuesField->OneToOne->targetEntity.';
    }        
	
    /**
     * @param  '.$this->getEntitiesNamespace().'\\'.$valuesField->OneToOne->targetEntity.' $'.$valuesField->OneToOne->targetEntity.'
     * @return '.$this->getEntitiesNamespace().'\\'.$className.'
     */
    public function set'.$methodName.'('.$this->getEntitiesNamespace().'\\'.$valuesField->OneToOne->targetEntity.' $'.$valuesField->OneToOne->targetEntity.')
    {
        $this->'.$valuesField->OneToOne->targetEntity.' = $'.$valuesField->OneToOne->targetEntity.';
        return $this;
    }
';
                } elseif (isset($valuesField->ManyToOne) && isset($valuesField->JoinColumn)) {

                    $contentFile .= '
    /**
     * @var ' . $this->getEntitiesNamespace() . '\\' . $valuesField->ManyToOne->targetEntity . '
     *
     * @ORM\ManyToOne(targetEntity="' . $valuesField->ManyToOne->targetEntity . '")
     * @ORM\JoinColumn(name="' . $valuesField->JoinColumn->name . '", referencedColumnName="' . $valuesField->JoinColumn->referencedColumnName . '")
     */
    private $' . $valuesField->ManyToOne->targetEntity . ' = null;
';
                    $methodName = $valuesField->ManyToOne->targetEntity;
                    $methodName{0} = strtoupper($methodName{0});

                    $tableDestName = $valuesField->ManyToOne->targetEntity;
                    $tableDestName = preg_replace_callback('/([A-Z])/', function($match) { return '_'.strtolower($match[1]); }, $tableDestName);

                    $methodKeyName = $valuesField->JoinColumn->name;
                    $methodKeyName = preg_replace_callback('/_([a-z])/', function($match) { return strtoupper($match[1]); }, $methodKeyName);
                    $methodKeyName{0} = strtoupper($methodKeyName{0});

                    $contentMethodPartOfFile .= '
    /**
     * @param  array $where
     * @return '.$this->getEntitiesNamespace().'\\'.$valuesField->ManyToOne->targetEntity.'
     */
    public function get'.$methodName.'(array $where = [])
    {
        if ($this->'.$valuesField->ManyToOne->targetEntity.' === null) {

            $orm = new Orm;

            $orm->select(array(\'*\'))
                ->from(\''.$tableDestName.'\');
												   
            $where[\''.$valuesField->JoinColumn->referencedColumnName.'\'] = $this->get'.$methodKeyName.'();
	        
            $this->'.$valuesField->ManyToOne->targetEntity.' = $orm->where($where)->load(false, \''.$this->getEntitiesNamespace().'\\\\\');
        }
        
        return $this->'.$valuesField->ManyToOne->targetEntity.'[0];
    }        
	
    /**
     * @param  '.$this->getEntitiesNamespace().'\\'.$valuesField->ManyToOne->targetEntity.' $'.$valuesField->ManyToOne->targetEntity.'
     * @return '.$this->getEntitiesNamespace().'\\'.$className.'
     */
    public function set'.$methodName.'('.$this->getEntitiesNamespace().'\\'.$valuesField->ManyToOne->targetEntity.' $'.$valuesField->ManyToOne->targetEntity.')
    {
        $this->'.$valuesField->ManyToOne->targetEntity.' = $'.$valuesField->ManyToOne->targetEntity.';
        return $this;
    }
';
                }
            }

            /**
             * We don't write ManyToOne in the conf file (we devin it with the OneToMany)
             */
            foreach ($values->OneToAll as $joinType => $valuesField) {

                if (isset($valuesField->OneToMany) && isset($valuesField->JoinColumn)) {

                    $contentFile .= '
    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="'.$valuesField->OneToMany->targetEntity.'")
     * @ORM\JoinColumn(name="'.$valuesField->JoinColumn->name.'", referencedColumnName="'.$valuesField->JoinColumn->referencedColumnName.'")
     */
    private $'.$valuesField->OneToMany->targetEntity.' = null;
';

                    $methodName = $valuesField->OneToMany->targetEntity;
                    $methodName{0} = strtoupper($methodName{0});

                    $tableDestName = $valuesField->OneToMany->targetEntity;
                    $tableDestName = preg_replace_callback('/([A-Z])/', function($match) { return '_'.strtolower($match[1]); }, $tableDestName);

                    $methodKeyName = $valuesField->JoinColumn->name;
                    $methodKeyName = preg_replace_callback('/_([a-z])/', function($match) { return strtoupper($match[1]); }, $methodKeyName);
                    $methodKeyName{0} = strtoupper($methodKeyName{0});

                    $contentMethodPartOfFile .= '
    /**
     * @param  array $where
     * @return array
     */
    public function get'.$methodName.'(array $where = [])
    {
        if ($this->'.$valuesField->OneToMany->targetEntity.' === null) {

            $orm = new Orm;

            $orm->select(array(\'*\'))
                ->from(\''.$tableDestName.'\');
												   
            $where[\''.$valuesField->JoinColumn->referencedColumnName.'\'] = $this->get'.$methodKeyName.'();
	        
            $this->'.$valuesField->OneToMany->targetEntity.' = $orm->where($where)->load(false, \''.$this->getEntitiesNamespace().'\\\\\');
        }
        
        return $this->'.$valuesField->OneToMany->targetEntity.';
    }        
	
    /**
     * @param  '.$this->getEntitiesNamespace().'\\'.$valuesField->OneToMany->targetEntity.' $'.$valuesField->OneToMany->targetEntity.'
     * @return '.$this->getEntitiesNamespace().'\\'.$className.'
     */
    public function add'.$methodName.'('.$this->getEntitiesNamespace().'\\'.$valuesField->OneToMany->targetEntity.' $'.$valuesField->OneToMany->targetEntity.')
    {
        $this->'.$valuesField->OneToMany->targetEntity.'[] = $'.$valuesField->OneToMany->targetEntity.';
        return $this;
    }
';
                }
                elseif (isset($valuesField->ManyToMany) && isset($valuesField->JoinColumn)) {

                    $contentFile .= '
    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="'.$valuesField->ManyToMany->targetEntity.'")
     * @ORM\JoinColumn(name="'.$valuesField->JoinColumn->name.'", referencedColumnName="'.$valuesField->JoinColumn->referencedColumnName.'")
     */
    private $'.$valuesField->ManyToMany->targetEntity.' = null;
';
                }
            }

            var_dump($contentFile.$contentMethodPartOfFile);
        }
    }

    /**
     * @param string $sqlJsonFile
     * @param string $entitiesPath
     */
    private function createDatabase(string $sqlJsonFile, string $entitiesPath)
    {
        $configuration = json_decode(file_get_contents($sqlJsonFile));

        $this->defaultConnection = $configuration->default_connection;

        /**
         * list all connection
         */
        foreach ($configuration->database as $name => $values) {

            $containerDb = new DbContainer;

            $containerDb->setDbName($values->db)
                ->setHost($values->host)
                ->setName($values->db)
                ->setPassword($values->password)
                ->setType($values->type)
                ->setUser($values->user);

            $pdo = Db::connect($containerDb);
        }
    }

    /**
     * @return boolean
     */
    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    /**
     * @param boolean $verbose
     */
    public function setVerbose(bool $verbose)
    {
        $this->verbose = $verbose;
    }

    /**
     * @return string
     */
    public function getPortal(): string
    {
        return $this->portal;
    }

    /**
     * @param string $portal
     */
    public function setPortal(string $portal)
    {
        $this->portal = $portal;
    }

    /**
     * @return string
     */
    public function getEntitiesPath(): string
    {
        return $this->entitiesPath;
    }

    /**
     * @param string $entitiesPath
     */
    public function setEntitiesPath(string $entitiesPath)
    {
        $this->entitiesPath = $entitiesPath;
    }

    /**
     * @return string
     */
    public function getModelsPath(): string
    {
        return $this->modelsPath;
    }

    /**
     * @param string $modelsPath
     */
    public function setModelsPath(string $modelsPath)
    {
        $this->modelsPath = $modelsPath;
    }

    /**
     * @return string
     */
    public function getConfFile(): string
    {
        return $this->confFile;
    }

    /**
     * @param string $confFile
     */
    public function setConfFile(string $confFile)
    {
        $this->confFile = $confFile;
    }

    /**
     * @return string
     */
    public function getDefaultConnection() : string
    {
        return $this->defaultConnection;
    }

    /**
     * @param string $defaultConnection
     */
    public function setDefaultConnection(string $defaultConnection)
    {
        $this->defaultConnection = $defaultConnection;
    }

    /**
     * @return string
     */
    public function getEntitiesNamespace(): string
    {
        return $this->entitiesNamespace;
    }

    /**
     * @param string $entitiesNamespace
     */
    public function setEntitiesNamespace(string $entitiesNamespace)
    {
        $this->entitiesNamespace = $entitiesNamespace;
    }

    /**
     * @return string
     */
    public function getModelsNamespace(): string
    {
        return $this->modelsNamespace;
    }

    /**
     * @param string $modelsNamespace
     */
    public function setModelsNamespace(string $modelsNamespace)
    {
        $this->modelsNamespace = $modelsNamespace;
    }
}
