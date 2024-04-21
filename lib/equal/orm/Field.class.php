<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace equal\orm;

use equal\orm\UsageFactory;
use equal\orm\usages\Usage;

class Field {

    /**
     * Descriptor of the field.
     * In addition to properties from `Model::getColumns()`, `Field::descriptor` always as a `result_type` property.
     *
     * @var array
     */
    private $descriptor = [];

    /** @var Usage */
    private $usage = null;

    /**
     * Pseudo type of the Field instance.
     * @var string
     */
    private $type = null;

    /**
     * @param array $descriptor Associative array mapping field properties and their values.
     */
    public function __construct(array $descriptor) {
        if(isset($descriptor['type'])) {
            $this->type = $descriptor['type'];
        }
        $this->descriptor = $descriptor;
        // ensure local descriptor always has a result_type property
        if(!isset($descriptor['result_type'])) {
            $this->descriptor['result_type'] = $this->type;
        }
    }

    /**
     * Provides the usage string equivalent of the pseudo type of the Field instance.
     * This method maps `types` (implicit usage format) with explicit usage formats.
     */
    protected function getUsageString(): string {
        static $map = [
            'boolean'       => 'number/boolean',
            'integer'       => 'number/integer:9',
            'float'         => 'number/real:10.2',
            'string'        => 'text/plain:255',
            'text'          => 'text/plain:32000',
            'date'          => 'date/plain',
            'datetime'      => 'date/time',
            'time'          => 'time/plain',
            'binary'        => 'binary/plain:64000000',
            'many2one'      => 'number/integer:9',
            'array'         => 'array'
        ];
        $type = $this->type;
        if($this->type == 'computed' && isset($this->descriptor['result_type'])) {
            $type = $this->descriptor['result_type'];
        }
        return isset($map[$type])?$map[$type]:$type;
    }

    public function getUsage(): Usage {
        if(is_null($this->usage)) {
            // use usage string from the descriptor if present
            if(isset($this->descriptor['usage']) && strlen($this->descriptor['usage']) > 0) {
                $this->usage = UsageFactory::create($this->descriptor['usage']);
            }
            // otherwise, use the usage string of which the field type is an alias
            else {
                $this->usage = UsageFactory::create($this->getUsageString());
            }
        }
        return $this->usage;
    }

    /**
     * Retrieves the constraints that apply on the field, according to its usage (explicit or implicit).
     *
     * @return array
     */
    public function getConstraints(): array {
        // generate constraint based on type
        $result_type = $this->descriptor['result_type'];

        $constraints = [
            'invalid_type' => [
                'message'   => "Value is not of type {$result_type}.",
                'function'  =>  function($value) use($result_type) {
                    static $map = [
                        'bool'      => 'boolean',
                        'int'       => 'integer',
                        'float'     => 'double',
                        'text'      => 'string',
                        'date'      => 'integer',
                        'datetime'  => 'integer',
                        'file'      => 'string',
                        'binary'    => 'string',
                        'many2one'  => 'integer',
                        'one2many'  => 'array',
                        'many2many' => 'array'
                    ];
                    // fix types to match values returned by PHP `gettype()`
                    $mapped_type = $map[$result_type] ?? $result_type;
                    return (gettype($value) == $mapped_type);
                }
            ]
        ];

        // append constraints based on usage
        return array_merge($constraints, $this->getUsage()->getConstraints());
    }

    public function getDescriptor(): array {
        return $this->descriptor;
    }
}
