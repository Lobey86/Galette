<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Fields categories handling
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
 * @since     Available since 0.7dev - 2009-03-28
 */

require_once 'adherent.class.php';

/**
 * Fields categories class for galette
 *
 * @category  Classes
 * @name      FieldsCategories
 * @package   Galette
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2012 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-03-28
 */

class FieldsCategories
{
    private $_id;
    private $_category;
    const TABLE = 'fields_categories';
    const PK = 'id_field_category';

    private $_labels= array();

    const ADH_CATEGORY_IDENTITY = 1;
    const ADH_CATEGORY_GALETTE = 2;
    const ADH_CATEGORY_CONTACT = 3;

    private static $_defaults = array(
        array(
            'id'         => 1,
            'table_name' => Adherent::TABLE,
            'category'   => 'Identity:',
            'position'   => 1
        ),
        array(
            'id'         => 2,
            'table_name' => Adherent::TABLE,
            'category'   => 'Galette-related data:',
            'position'   => 2
        ),
        array(
            'id'         => 3,
            'table_name' => Adherent::TABLE,
            'category'   => 'Contact information:',
            'position'   => 3
        )
    );

    /**
    * Default constructor
    */
    function __construct()
    {
    }

    /**
    * Get list of categories
    *
    * @return array
    */
    public static function getList()
    {
        global $zdb, $log;

        try {
            $select = new Zend_Db_Select($zdb->db);
            $select->from(PREFIX_DB . self::TABLE)
                ->order('position');
            return $select->query()->fetchAll();
        } catch (Exception $e) {
            /** TODO */
            $log->log(
                '[' . get_class($this) . '] Cannot get fields categories list | ' .
                $e->getMessage(),
                PEAR_LOG_WARNING
            );
            $log->log(
                'Query was: ' . $select->__toString() . ' ' . $e->__toString(),
                PEAR_LOG_ERR
            );
            return false;
        }
    }

    /**
     * Retrieve category label
     *
     * @param integer $id Id
     *
     * @return string
     */
    public function getLabel($id)
    {
        global $zdb, $log;

        if ( !isset($this->_labels[$id]) ) {
            try {
                $select = new Zend_Db_Select($zdb->db);
                $select->from(PREFIX_DB . self::TABLE)
                    ->where(self::PK . ' = ?', $id);

                $res = $select->query()->fetch();
                $this->_labels[$id] = $res->category;
                return $res->category;
            } catch ( Exception $e ) {
                $log->log(
                    'Something went wrong trying to get label for category ' . $id,
                    PEAR_LOG_ERR
                );
                $log->log(
                    'Query was: ' . $select->__toString(),
                    PEAR_LOG_ERR
                );
            }
        }
    }

    /**
    * Set default fields categories at install time
    *
    * @return boolean|Exception
    */
    public function installInit()
    {
        global $zdb, $log;

        try {
            //first, we drop all values
            $zdb->db->delete(PREFIX_DB . self::TABLE);

            $stmt = $zdb->db->prepare(
                'INSERT INTO ' . PREFIX_DB . self::TABLE .
                ' (' . self::PK . ', table_name, category, position) ' .
                'VALUES(:id, :table_name, :category, :position)'
            );

            foreach ( self::$_defaults as $d ) {
                $stmt->bindParam(':id', $d['id']);
                $stmt->bindParam(':table_name', $d['table_name']);
                $stmt->bindParam(':category', $d['category']);
                $stmt->bindParam(':position', $d['position']);
                $stmt->execute();
            }

            $log->log(
                'Default fields configurations were successfully stored into database.',
                PEAR_LOG_INFO
            );
            return true;
        } catch (Exception $e) {
            $log->log(
                'Unable to initialize default fields configuration.' .
                $e->getMessage(),
                PEAR_LOG_WARNING
            );
            return $e;
        }
    }

    /**
    * GETTERS
    */

    /**
    * SETTERS
    */
}
?>
