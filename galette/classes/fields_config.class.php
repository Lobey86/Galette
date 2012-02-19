<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Fields config handling
 *
 * PHP version 5
 *
 * Copyright © 2009-2012 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Classes
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2012 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-03-26
 */


/** @ignore */
require_once 'adherent.class.php';
require_once 'fields_categories.class.php';

/**
 * Fields config class for galette :
 * defines fields mandatory, order and visibility
 *
 * @category  Classes
 * @name      FieldsConfig
 * @package   Galette
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2012 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-03-26
 */
class FieldsConfig
{
    const HIDDEN = 0;
    const VISIBLE = 1;
    const ADMIN = 2;

    const TYPE_STR = 0;
    const TYPE_HIDDEN = 1;
    const TYPE_BOOL = 2;
    const TYPE_INT = 3;
    const TYPE_DEC = 4;
    const TYPE_DATE = 5;
    const TYPE_TXT = 6;
    const TYPE_PASS = 7;
    const TYPE_EMAIL = 8;
    const TYPE_URL = 9;

    private $_all_required;
    private $_all_visibles;
    private $_all_labels;
    //private $error = array();
    private $_categorized_fields = array();
    private $_table;
    private $_defaults = null;
    private $_all_categories;

    private $_form_elements = array();

    const TABLE = 'fields_config';

    /**
    * Default constructor
    *
    * @param string $table    the table for which to get fields configuration
    * @param array  $defaults default values
    */
    function __construct($table, $defaults)
    {
        $this->_table = $table;
        $this->_defaults = $defaults;
        $this->_checkUpdate();
    }

    /**
    * Checks if the required table should be updated
    * since it has not yet happened or the table
    * has been modified.
    *
    * @param boolean $try Just check, when called from $this->init()
    *
    * @return void
    */
    private function _checkUpdate($try = true)
    {
        global $zdb, $log;
        $class = get_class($this);

        try {
            $select = new Zend_Db_Select($zdb->db);
            $select->from(PREFIX_DB . self::TABLE)
                ->where('table_name = ?', $this->_table)
                ->order(array(FieldsCategories::PK, 'position ASC'));

            $result = $select->query()->fetchAll();
            if ( count($result) == 0 && $try ) {
                $this->init();
            } else {
                $this->_categorized_fields = null;
                foreach ( $result as $k ) {
                    $f = array(
                        'field_id'  =>  $k->field_id,
                        'label'     =>  $this->_defaults[$k->field_id]['label'],
                        'category'  =>  $this->_defaults[$k->field_id]['category'],
                        'visible'   =>  $k->visible,
                        'required'  =>  $k->required
                    );
                    $this->_categorized_fields[$k->id_field_category][] = $f;

                    //array of all required fields
                    if ( $k->required == 1 ) {
                        $this->_all_required[$k->field_id] = $k->required;
                    }

                    //array of all fields visibility
                    $this->_all_visibles[$k->field_id] = $k->visible;

                    //maybe we can delete these ones in the future
                    $this->_all_labels[$k->field_id]
                        = $this->_defaults[$k->field_id]['label'];
                    $this->_all_categories[$k->field_id]
                        = $this->_defaults[$k->field_id]['category'];
                    $this->all_positions[$k->field_id] = $k->position;
                }

                $meta = Adherent::getDbFields();

                if ( count($meta) != count($result) ) {
                    $log->log(
                        '[' . $class . '] Count for `' . $this->_table .
                        '` columns does not match records. Is : ' .
                        count($result) . ' and should be ' .
                        count($meta) . '. Reinit.',
                        PEAR_LOG_INFO
                    );
                    $this->init(true);
                }
            }
        } catch (Exception $e) {
            $log->log(
                '[' . $class . '] An error occured while checking update for ' .
                'fields configuration for table `' . $this->_table . '`. ' .
                $e->getMessage(),
                PEAR_LOG_ERR
            );
            $log->log(
                'Query was: ' . $select->__toString() . ' ' . $e->__toString(),
                PEAR_LOG_ERR
            );
            throw $e;
        }
    }

    /**
    * Init data into config table.
    *
    * @param boolean $reinit true if we must first delete all config data for
    * current table.
    * This should occurs when table has been updated. For the first
    * initialisation, value should be false. Defaults to false.
    *
    * @return void
    */
    public function init($reinit=false)
    {
        global $zdb, $log;
        $class = get_class($this);
        $t = new FieldsCategories();
        $init = $t->installInit();

        $log->log(
            '[' . $class . '] Initializing fields configuration for table `' .
            PREFIX_DB . $this->_table . '`',
            PEAR_LOG_DEBUG
        );
        if ( $reinit ) {
            $log->log(
                '[' . $class . '] Reinit mode, we delete config content for ' .
                'table `' . PREFIX_DB . $this->_table . '`',
                PEAR_LOG_DEBUG
            );
            //Delete all entries for current table. Existing entries are
            //already stored, new ones will be added :)
            try {
                $zdb->db->delete(
                    PREFIX_DB . self::TABLE,
                    $zdb->db->quoteInto('table_name = ?', PREFIX_DB . $this->_table)
                );
            } catch (Exception $e) {
                $log->log(
                    'Unable to delete fields configuration for reinitialization' .
                    $e->getMessage(),
                    PEAR_LOG_WARNING
                );
                return false;
            }
        }

        try {
            $fields = array_keys($this->_defaults);

            $stmt = $zdb->db->prepare(
                'INSERT INTO ' . PREFIX_DB . self::TABLE .
                ' (table_name, field_id, required, visible, position, ' .
                FieldsCategories::PK .
                ') VALUES(:table_name, :field_id, :required, :visible, :position, ' .
                ':category)'
            );

            $params = array();
            foreach ( $fields as $key ) {
                $params = array(
                    ':field_id'    => $key,
                    ':table_name'  => $this->_table,
                    ':required'    => (
                                        ($reinit) ?
                                            array_key_exists(
                                                $key,
                                                $this->_all_required
                                            ) :
                                            $this->_defaults[$key]['required'] ?
                                                true :
                                                'false'
                                      ),
                    ':visible'     => (
                                        ($reinit) ?
                                            array_key_exists(
                                                $key,
                                                $this->all_visible
                                            ) :
                                            $this->_defaults[$key]['visible'] ?
                                                true :
                                                'false'
                                      ),
                    ':position'    => (
                                        ($reinit) ?
                                            $this->all_positions[$key] :
                                            $this->_defaults[$key]['position']
                                      ),
                    ':category'    => (
                                        ($reinit) ?
                                            $this->_all_categories[$key] :
                                            $this->_defaults[$key]['category']
                                      ),
                );
                $stmt->execute($params);
            }
            $log->log(
                '[' . $class . '] Initialisation seems successfull, we reload ' .
                'the object',
                PEAR_LOG_DEBUG
            );
            $log->log(
                str_replace(
                    '%s',
                    PREFIX_DB . $this->_table,
                    '[' . $class . '] Fields configuration for table %s '.
                    'initialized successfully.'
                ),
                PEAR_LOG_INFO
            );
            $this->_checkUpdate(false);
        } catch (Exception $e) {
            /** FIXME */
            $log->log(
                '[' . $class . '] An error occured trying to initialize fields ' .
                'configuration for table `' . PREFIX_DB . $this->_table . '`.' .
                $e->getMessage(),
                PEAR_LOG_ERR
            );
            throw $e;
        }
    }

    /**
     * Retrieve form elements
     *
     * @return array
     */
    public function getFormElements()
    {
        global $zdb, $log, $login;

        if ( !count($this->_form_elements) > 0 ) {
            try {
                foreach ( array_keys($this->_categorized_fields) as $c ) {
                    $cat = (object) array(
                        'id' => '',
                        'label' => '',
                        'elements' => array()
                    );
                    $cat->id = $c;
                    $cat->label = FieldsCategories::getLabel($c);

                    $elements = $this->_categorized_fields[$c];
                    $cat->elements = array();
                    foreach ( $elements as $elt ) {
                        $o = (object)$elt;

                        if ( !($o->visible == self::ADMIN && !$login->isAdmin()) ) {
                            //retrieve table fields
                            $infos = $zdb->db->describeTable(
                                PREFIX_DB . $this->_table
                            );
                            if ( $o->visible == 0 ) {
                                $o->type = self::TYPE_HIDDEN;
                            } else if (preg_match('/date/', $o->field_id) ) {
                                $o->type = self::TYPE_DATE;
                            } else if (preg_match('/bool/', $o->field_id) ) {
                                $o->type = self::TYPE_BOOL;
                            } else {
                                $o->type = self::TYPE_STR;
                            }
                            $o->max_length = $infos[$o->field_id]['LENGTH'];
                            $o->default = $infos[$o->field_id]['DEFAULT'];
                            $o->datatype = $infos[$o->field_id]['DATA_TYPE'];

                            $cat->elements[] = $o;
                        }
                    }

                    if ( count($cat) > 0 ) {
                        $this->_form_elements[] = $cat;
                    }
                }
            } catch ( Exception $e ) {
                $log->log(
                    'An error occured getting form elements',
                    PEAR_LOG_ERR
                );
            }
        }
        return $this->_form_elements;
    }

    /**
    * Get required fields
    *
    * @return array of all required fields. Field names = keys
    */
    public function getRequired()
    {
        return $this->_all_required;
    }

    /**
     * Retrieve a specific label
     *
     * @param string $k Key
     *
     * @return string
     */
    public function getLabel($k)
    {
        return $this->_all_labels[$k];
    }

    /*public function getLabels(){ return $this->_all_labels; }*/
    /*public function getCategories(){ return $this->_all_categories; }*/
    /*public function getPositions(){ return $this->all_positions; }*/
    /*public function getPosition($field){ return $this->all_positions[$field]; }*/

    /**
    * Get visible fields
    *
    * @return array of all visibles fields
    */
    public function getVisibilities()
    {
        return $this->_all_visibles;
    }

    /**
    * Get visibility for specified field
    *
    * @param string $field The requested field
    *
    * @return boolean
    */
    public function getVisibility($field)
    {
        return $this->_all_visibles[$field];
    }

    /**
    * Get all fields with their categories
    *
    * @return array
    */
    public function getCategorizedFields()
    {
        return $this->_categorized_fields;
    }

    /**
    * Get all fields
    *
    * @return array
    */
    public function getFields()
    {
        return $this->fields;
    }

    /** FIXME: should return _store result */
    /**
    * Set fields
    *
    * @param array $fields categorized fields array
    *
    * @return void
    */
    public function setFields($fields)
    {
        $this->_categorized_fields = $fields;
        $this->_store();
    }

    /**
    * Store config in database
    *
    * @return boolean
    */
    private function _store()
    {
        global $zdb, $log;
        $class = get_class($this);

        try {
            $zdb->db->beginTransaction();
            $stmt = $zdb->db->prepare(
                'UPDATE ' . PREFIX_DB . self::TABLE . ' SET ' .
                $zdb->db->quoteIdentifier('required') . ' =  :required' .
                ', ' .  $zdb->db->quoteIdentifier('visible') . ' =  :visible' .
                ', ' .  $zdb->db->quoteIdentifier('position') . ' =  :position' .
                ', ' .  $zdb->db->quoteIdentifier(FieldsCategories::PK) . ' =  :category' .
                ' WHERE ' . $zdb->db->quoteInto('table_name = ?', $this->_table) .
                ' AND ' .  $zdb->db->quoteIdentifier('field_id') . ' = :field_id'
            );

            $params = array();
            foreach ( $this->_categorized_fields as $cat ) {
                foreach ( $cat as $pos=>$field ) {
                    $stmt->bindValue(':field_id', $field['field_id']);
                    $stmt->bindValue(':required', $field['required']);
                    $stmt->bindValue(':visible', $field['visible'], PDO::PARAM_INT);
                    $stmt->bindValue(':position', $pos);
                    $stmt->bindValue(':category', $field['category']);

                    $stmt->execute();
                }
            }

            $zdb->db->commit();

            $log->log(
                str_replace(
                    '%s',
                    $this->_table,
                    '[' . $class . '] Fields configuration for table %s stored ' .
                    'successfully.'
                ),
                PEAR_LOG_INFO
            );

            return true;
        } catch (Exception $e) {
            $zdb->db->rollBack();
            $log->log(
                '[' . $class . '] An error occured while storing fields ' .
                'configuration for table `' . $this->_table . '`.' .
                ' | ' . $e->getMessage(),
                PEAR_LOG_WARNING
            );
            $log->log(
                $e->__toString(),
                PEAR_LOG_ERR
            );
            return false;
        }
    }
}
?>
