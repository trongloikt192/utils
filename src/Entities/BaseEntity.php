<?php
/**
 * Created by PhpStorm.
 * User: loi
 * Date: 07-Oct-18
 * Time: 03:22 PM
 */

namespace trongloikt192\Utils\Entities;


class BaseEntity
{
    /**
     * BaseEntity constructor.
     * @param array $properties
     */
    public function __construct(Array $properties=array())
    {
        foreach($properties as $key => $value){
            $this->{$key} = $value;
        }
    }
}