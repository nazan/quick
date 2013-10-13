<?php

/*
 * This software is a property of Color Anomaly.
 * Use of this software for commercial purposes is strictly
 * prohibited.
 */

namespace ColorAnomaly\Quick\Domain;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Description of Queue
 * 
 * @ODM\Document(collection="queues")
 * 
 * @author Hussain Nazan Naeem <hussennaeem@gmail.com>
 */
class Queue {

    public function __construct() {
        //$this->setLowerBound(1000);
        //$this->setUpperBound(1100);

        //$this->reset();

        $this->actors = array();
    }

    /**
     * @ODM\Id(strategy="NONE")
     */
    protected $name;

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    /**
     * @ODM\Int
     */
    protected $lowerBound;

    public function getLowerBound() {
        return $this->lowerBound;
    }

    public function setLowerBound($lower) {
        if (!is_null($lower) && is_numeric($lower) && (!isset($this->upperBound) || $this->upperBound > $lower)) {
            $this->lowerBound = $lower;
            
            if(isset($this->upperBound)) {
                $this->reset();
            }
        }
    }

    /**
     * @ODM\Int
     */
    protected $upperBound;

    public function getUpperBound() {
        return $this->upperBound;
    }

    public function setUpperBound($upper) {
        if (!is_null($upper) && is_numeric($upper) && isset($this->lowerBound) && $this->lowerBound < $upper) {
            $this->upperBound = $upper;
            
            $this->reset();
        }
    }

    /**
     * @ODM\Int
     */
    protected $enqueueLast;

    public function getEnqueueLast() {
        return $this->enqueueLast;
    }

    private function setEnqueueLast($bound) {
        $this->enqueueLast = $bound;
    }

    /**
     * @ODM\Int
     */
    protected $dequeueLast;

    public function getDequeueLast() {
        return $this->dequeueLast;
    }

    private function setDequeueLast($bound) {
        $this->dequeueLast = $bound;
    }

    private function enqueue() {
        if ($this->enqueueLast == $this->upperBound) {
            throw new QueueOverflowException("No more tokens left to dispense.");
        }

        $token = $this->enqueueLast++;

        return $token;
    }

    private function dequeue() {
        if ($this->isEmpty()) {
            throw new QueueEmptyException("No pending tokens.");
        }

        $token = $this->dequeueLast++;

        return $token;
    }

    public function isEmpty() {
        return $this->enqueueLast == $this->dequeueLast;
    }

    public function reset() {
        $this->enqueueLast = $this->dequeueLast = $this->lowerBound;
    }
    
    public function enqueueToken() {
        return $this->enqueue();
    }
    
    public function dequeueToken($actorId) {
        foreach ($this->actors as $k => $a) {
            if ($a->getId() == $actorId) {
                $token = $this->dequeue();
                $a->setServingToken($token);
                $a->setDequeuedAt(new \DateTime());
                
                return $token;
            }
        }
        
        throw new QueueException("Actor $actorId not found in queue '{$this->name}'.");
    }

    /**
     * @ODM\EmbedMany(targetDocument="ColorAnomaly\Quick\Domain\Actor")
     */
    protected $actors;

    public function getActors() {
        return $this->actors;
    }
    
    public function getActor($actorId) {
        foreach ($this->actors as $a) {
            if ($a->getId() == $actorId) {
                return $a;
            }
        }
        
        throw new QueueException(__CLASS__ . " does not have an associated document for given identity '" . $actorId . "' of type Actor");
    }

    public function addActor(Actor $actor) {
        foreach ($this->actors as $a) {
            if ($a->getId() == $actor->getId()) {
                throw new QueueException(__CLASS__ . " already has an associated document with identity '" . $actor->getId() . "' " . get_class($actor));
            }
        }

        $this->actors[] = $actor;
    }

    public function setActor(Actor $actor) {
        foreach ($this->actors as $k => $a) {
            if ($a->getId() == $actor->getId()) {
                $this->actors[$k] = $actor;
                return;
            }
        }

        throw new QueueException(__CLASS__ . " does not have an associated document for given identity '" . $actor->getId() . "' " . get_class($actor));
    }

    public function removeActor($actorId) {
        $keys = array();
        foreach ($this->actors as $k => $a) {
            if ($a->getId() == $actorId) {
                $keys[] = $k;
            }
        }

        foreach ($keys as $k) {
            $this->actors->remove($k);
        }

        if (empty($keys)) {
            throw new QueueException(__CLASS__ . " does not have an associated document for given identity '" . $actorId . "' of type Actor");
        }

        return true;
    }
    
    public function getDisplayInfo() {
        $actors = array();
    
        foreach($this->getActors() as $a) {
            if($a->getRole() == '/dequeue') {
                $at = $a->getDequeuedAt();

                if(!is_null($at)) {
                    $actors[$at->getTimestamp()] = array('label'=>$a->getLabel(), 'token'=>$a->getServingToken());
                }
            }
        }

        uksort($actors, function($a, $b) {
            if($a > $b) {
                return -1;
            } elseif($a < $b) {
                return 1;
            }

            return 0;
        });
        
        return array('queueId'=> $this->getName(), 'actors' => array_values($actors));
    }

}
