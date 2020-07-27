<?php
/**
 * This file is part of the Zemit Framework.
 *
 * (c) Zemit Team <contact@zemit.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Zemit\Models;

use Phalcon\Validation\Validator\Between;
use Phalcon\Validation\Validator\Date;
use Phalcon\Validation\Validator\Numericality;
use Zemit\Locale;
use Zemit\Validation;

/**
 * Class Base
 *
 * @package Zemit\Models
 */
abstract class AbstractModel extends \Zemit\Mvc\Model
{
    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        parent::initialize();
        $this->setDefaultRelationships();
    }
    
    /**
     * Set the default relationships
     * @param array|null $relationships
     */
    public function setDefaultRelationships(?array $relationships = null) : void
    {
        $userClass = $this->getIdentity()->getUserClass() ?: User::class;
        
        $relationships ??= [
            [
                'type' => 'belongsTo',
                'property' => 'userId',
                'alias' => 'User',
                'class' => $userClass,
            ],
            [
                'type' => 'belongsTo',
                'property' => 'createdBy',
                'alias' => 'CreatedByUser',
                'class' => $userClass,
            ],
            [
                'type' => 'belongsTo',
                'property' => 'updatedBy',
                'alias' => 'UpdatedByUser',
                'class' => $userClass,
            ],
            [
                'type' => 'belongsTo',
                'property' => 'deletedBy',
                'alias' => 'DeletedByUser',
                'class' => $userClass,
            ],
        ];
        foreach ($relationships as $rel) {
            if (property_exists($this, $rel['property'])) {
                $this->{$rel['type']}($rel['property'], $rel['class'], 'id', ['alias' => $rel['alias']]);
            }
        }
    }
    
    /**
     * Translation shortcut
     * @return mixed
     */
    public function _() {
        return $this->getDI()->get('translate')->_(...func_get_args());
    }
    
    /**
     * Language support
     * - Allow to call $this->getName{Fr|En}
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function __call(string $method, array $arguments) {
        
        /** @var Locale $locale */
        $locale = $this->getDI()->get('locale');
        
        $lang = $locale->getLocale();
        
        if (mb_strrpos($method, ucfirst($lang)) !== mb_strlen($method) - mb_strlen($lang)) {
            $call = $method . ucfirst($lang);
            if (method_exists($this, $call)) {
                return $this->$call(...$arguments);
            }
        }
        
        return parent::__call($method, $arguments);
    }
    
    /**
     * Language support
     * Magic method to assign values to the the model
     * - Allow to set $this->name{Fr|En} from inexistant name property
     *
     * @param string $method
     *
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function __set(string $property, $value) {
        
        /** @var Locale $locale */
        $locale = $this->getDI()->get('locale');
        
        $lang = $locale->getLocale();
        
        if (mb_strrpos($property, ucfirst($lang)) !== mb_strlen($property) - 2) {
            $set = $property . ucfirst($lang);
            
            if (property_exists($this, $set)) {
                if (!$this->_possibleSetter($set, $value)) {
                    $this->writeAttribute($set, $value);
                }
                return;
            }
        }
        
        parent::__set($property, $value);
    }
    
    /**
     * Language support
     * @todo __isset
     * Magic method to get related records using the relation alias as a property
     * - Allow to get $this->name{Fr|En} from inexistant name property
     *
     * @param string $method
     *
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function __get(string $property) {
        
        /** @var Locale $locale */
        $locale = $this->getDI()->get('locale');
        
        $lang = $locale->getLocale();
        
        if (mb_strrpos($property, ucfirst($lang)) !== mb_strlen($property) - 2) {
            $set = $property . ucfirst($lang);
            
            if (property_exists($this, $set)) {
                return $this->readAttribute($set);
            }
        }
        
        return parent::__get($property);
    }
    
    /**
     * @param $validator
     *
     * @return mixed
     */
    public function genericValidation(?Validation $validator = null) {
        $validator ??= new Validation();
        
        // DELETED
        if (property_exists($this, 'deleted')) {
            $validator->add('deleted', new Between([ "minimum" => 0, "maximum" => 1, 'message' => $this->_('deletedNotBetween')]));
        }
        
        // CREATED
        if (property_exists($this, 'createdAt')) {
            $validator->add('createdAt', new Date(['format' => self::DATETIME_FORMAT, 'message' => $this->_('createdAtNotValid'), 'allowEmpty' => true]));
        }
        if (property_exists($this, 'createdBy')) {
            $validator->add('createdBy', new Numericality(['message' => $this->_('createdByNotValid'), 'allowEmpty' => true]));
        }
        
        // UPDATED
        if (property_exists($this, 'updatedAt')) {
            $validator->add('updatedAt', new Date(['format' => self::DATETIME_FORMAT, 'message' => $this->_('updatedAtNotValid'), 'allowEmpty' => true]));
        }
        if (property_exists($this, 'updatedBy')) {
            $validator->add('updatedBy', new Numericality(['message' => $this->_('updatedByNotValid'), 'allowEmpty' => true]));
        }
        
        // DELETED
        if (property_exists($this, 'deletedAt')) {
            $validator->add('deletedAt', new Date(['format' => self::DATETIME_FORMAT, 'message' => $this->_('deletedAtNotValid'), 'allowEmpty' => true]));
        }
        if (property_exists($this, 'deletedBy')) {
            $validator->add('deletedBy', new Numericality(['message' => $this->_('deletedByNotValid'), 'allowEmpty' => true]));
        }
        
        // RESTORED
        if (property_exists($this, 'restoredAt')) {
            $validator->add('restoredAt', new Date(['format' => self::DATETIME_FORMAT, 'message' => $this->_('restoredAtNotValid'), 'allowEmpty' => true]));
        }
        if (property_exists($this, 'restoredBy')) {
            $validator->add('restoredBy', new Numericality(['message' => $this->_('restoredByNotValid'), 'allowEmpty' => true]));
        }
        
        return $validator;
    }
}
