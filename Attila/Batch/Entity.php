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

        $this->constructConfFileOfEntities($options, $sEntitiesPath);

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
 * @package   	'.ENTITY_NAMESPACE.'
 * @author    	'.AUTHOR.'
 * @copyright 	'.COPYRIGHT.'
 * @license   	'.LICENCE.'
 * @version   	Release: '.VERSION.'
 * @filesource	'.FILESOURCE.'
 * @link      	'.LINK.'
 * @since     	1.0
 */
namespace '.preg_replace('/^\\\\/', '', ENTITY_NAMESPACE).';

use \Attila\core\Entity as Entity;
use \Attila\Orm as Orm;

/**
 * @ORM\Table(name="'.$sTableName.'")
 * @ORM\Entity(repositoryClass="'.str_replace('Entity', 'Model', preg_replace('/^\\\\/', '', ENTITY_NAMESPACE)).'\\'.$sTableName.'")
 */
class '.$sTableName.' extends Entity 
{';

                foreach ($oOneTable->fields as $sFieldName => $oField) {

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
	 * @return '.ENTITY_NAMESPACE.'\\'.$sTableName.'
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

                                $sContentFile .= ENTITY_NAMESPACE.'\\'.$sTableName;
                            }
                            else if (isset($oField->key) && ($oField->key == 'primary' || in_array('primary', $oField->key))) {

                                $sContentFile .= 'array';
                            }
                            else {

                                $sContentFile .= ENTITY_NAMESPACE.'\\'.$sTableName;
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
						           ->load(false, \''.ENTITY_NAMESPACE.'\\\\\');';

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
     * @param string $tableName
     * @param string $fieldName
     * @param array $tableContent
     * @param string $action
     * @return string
     */
    private function createFieldByArray(string $tableName, string $fieldName, array $tableContent, string $action) : string  {

        $field = 'ALTER TABLE ' . SQL_FIELD_NAME_SEPARATOR . $tableName . SQL_FIELD_NAME_SEPARATOR . ' '.$action.' ' . SQL_FIELD_NAME_SEPARATOR . $fieldName . SQL_FIELD_NAME_SEPARATOR . ' ' . $tableContent['type'];

        if ($tableContent['value']) {

            $field .= '(' . $tableContent['value'] . ') ';
        }

        if ($tableContent['unsigned']) {

            $field .= ' UNSIGNED ';
        }

        if ($tableContent['null']) {
            $field .= ' NULL ';
        } else {
            $field .= ' NOT NULL ';
        }

        if ($tableContent['default']) {
            $field .= ' DEFAULT "' . $tableContent['default'] . '" ';
        }


        if ($tableContent['extra'] === 'auto_increment') {

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
}
