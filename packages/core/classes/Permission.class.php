<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU GPL 3 license <http://www.gnu.org/licenses/>
*/
namespace core;

use equal\orm\Model;

/**
 * @property alias      $name
 * @property string     $class_name     Full name of the entity to which the permission rule applies.
 * @property string     $domain         JSON value of the constraints domain (ex. ['creator', '=', '1'])
 * @property integer    $object_id      Identifier of the specific object on which the permission applies.
 * @property integer    $group_id       Targeted group, if permission applies to a group.
 * @property integer    $user_id        Targeted user, if permission applies to a single user.
 * @property integer    $rights         Rights binary mask (1: CREATE, 2: READ, 4: WRITE, 8 DELETE, 16: MANAGE)
 * @property string     $rights_txt
 */
class Permission extends Model {

    public static function getColumns() {
        return [
            'name' => [
                'type'              => 'alias',
                'alias'             => 'class_name'
            ],

            'class_name' => [
                'type'              => 'string',
                'description'       => 'Full name of the entity to which the permission rule applies.',
                'required'          => true
            ],

            // #deprecated (use `getRole()` for each specific Model)
            'domain' => [
                'type'              => 'string',
                'description'       => "JSON value of the constraints domain (ex. ['creator', '=', '1'])",
                'default'           => NULL
            ],

            'object_id' => [
                'type'              => 'integer',
                'description'       => "Identifier of the specific object on which the permission applies."
            ],

            'group_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\Group',
                'description'       => "Targeted group, if permission applies to a group.",
                'default'           => QN_DEFAULT_GROUP_ID
            ],

            'user_id' => [
                'type'              => 'many2one',
                'foreign_object'    => 'core\User',
                'description'       => "Targeted user, if permission applies to a single user."
            ],

            'rights' => [
                'type' 	            => 'integer',
                'description'       => "Rights binary mask (1: CREATE, 2: READ, 4: WRITE, 8 DELETE, 16: MANAGE)",
                'dependencies'      => ['rights_txt']
            ],

            // virtual field, used in list views
            'rights_txt' => [
                'type'              => 'computed',
                'result_type'       => 'string',
                'function'          => 'calcRightsTxt',
                'store'             => true
            ]
        ];
    }

    public static function calcRightsTxt($om, $ids, $lang) {
        $res = [];
        $values = $om->read(__CLASS__, $ids, ['rights'], $lang);
        foreach($ids as $id) {
            $rights_txt = [];
            $rights = $values[$id]['rights'];
            if($rights & QN_R_CREATE)   $rights_txt[] = 'create';
            if($rights & QN_R_READ)     $rights_txt[] = 'read';
            if($rights & QN_R_WRITE)    $rights_txt[] = 'write';
            if($rights & QN_R_DELETE)   $rights_txt[] = 'delete';
            if($rights & QN_R_MANAGE)   $rights_txt[] = 'manage';
            $res[$id] = implode(', ', $rights_txt);
        }
        return $res;
    }

}
