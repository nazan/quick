<?php

/*
 * This software is a property of Color Anomaly.
 * Use of this software for commercial purposes is strictly
 * prohibited.
 */

namespace ColorAnomaly\Quick\Domain;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Description of Actor
 * 
 * @ODM\EmbeddedDocument
 *
 * @author Hussain Nazan Naeem <hussennaeem@gmail.com>
 */
class Actor {
    public function __construct() {
    }
    
    /**
     * This field may be an IP address or anything else.
     * 
     * @ODM\String
     */
    protected $id;
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function getId() {
        return $this->id;
    }
    
    /**
     * @ODM\String
     */
    protected $role;
    
    public function setRole($role) {
        $this->role = $role;
    }
    
    public function getRole() {
        return $this->role;
    }
    
    /**
     * @ODM\String
     */
    protected $label;
    
    public function setLabel($label) {
        $this->label = $label;
    }
    
    public function getLabel() {
        return $this->label;
    }
    
    /**
     * @ODM\Int
     */
    protected $servingToken;
    
    public function setServingToken($servingToken) {
        $this->servingToken = $servingToken;
    }
    
    public function getServingToken() {
        if($this->servingToken == 0) {
            throw new QueueInappropriateReadException("Actor {$this->id} has not served any token yet.");
        }
        
        return $this->servingToken;
    }
    
    /**
     * @ODM\Field(type="date")
     */
    protected $dequeuedAt;
    
    public function setDequeuedAt($dequeuedAt) {
        $this->dequeuedAt = $dequeuedAt;
    }
    
    public function getDequeuedAt() {
        return $this->dequeuedAt;
    }
}
