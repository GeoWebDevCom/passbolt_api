<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
namespace App\View\Helper;

use Cake\Utility\Inflector;
use Cake\View\Helper;

class LegacyApiHelper extends Helper
{
    /**
     * Return entity name
     *
     * @param object $entity Cake\ORM\Entity
     * @return bool|string
     */
    public static function getEntityName($entity)
    {
        // example: App\Model\User becomes User
        return substr(get_class($entity), strrpos(get_class($entity), '\\') + 1);
    }

    /**
     * Format a model name
     *
     * @param string $name the model name
     * @return string new name
     */
    public static function formatModelName($name)
    {
        $new_name = null;
        if (strpos($name, '_') !== false) {
            // example: groups_users becomes GroupUser
            $names = explode('_', $name);
            foreach ($names as $n) {
                $new_name .= self::formatModelName($n);
            }
        } else {
            // example: groups becomes Group
            $new_name = ucfirst(Inflector::singularize($name));
        }

        return $new_name;
    }

    /**
     * Format an entity to an array
     *
     * @param object $entity Cake\ORM\Entity
     * @param string $name see getEntityName
     * @return array new entity
     */
    public static function formatEntity($entity, $name)
    {
        $result = [];
        foreach ($entity->visibleProperties() as $property) {
            $value = $entity->get($property);
            if (is_string($value) || is_bool($value) || is_numeric($value) || is_null($value)) {
                // example: id
                $result[$name][$property] = $value;
            } elseif (is_object($value) && get_class($value) === 'Cake\I18n\FrozenTime') {
                // example: modified
                $result[$name][$property] = $value->toDateTimeString();
            } elseif (is_object($value) && get_parent_class($value) === 'Cake\ORM\Entity') {
                // example: gpgkey
                $name = self::formatModelName($property);
                $result[$name] = self::formatEntity($value, $name)[$name];
            } elseif (is_object($value) && get_class($value) === 'Cake\ORM\Entity') {
                // example: scafolded model
                $name = self::formatModelName($property);
                $result[$name] = self::formatEntity($value, $name)[$name];
            } elseif (is_array($value)) {
                // example: groups_users
                $name = self::formatModelName($property);
                foreach ($value as $i => $entity2) {
                    $result[$name][$i] = self::formatEntity($entity2, $name)[$name];
                }
            }
        }

        return $result;
    }

    /**
     * Format a result set to an array
     *
     * @param object $resultSet Cake\ORM\Query
     * @return array new result set
     */
    public static function formatResultSet($resultSet)
    {
        $i = 0;
        $results = [];
        foreach ($resultSet as $entity) {
            $name = self::getEntityName($entity);
            $results[$i] = self::formatEntity($entity, $name);
            $i++;
        }

        return $results;
    }
}