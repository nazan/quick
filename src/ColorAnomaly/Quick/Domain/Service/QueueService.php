<?php

/*
 * This software is a property of Color Anomaly.
 * Use of this software for commercial purposes is strictly
 * prohibited.
 */

namespace ColorAnomaly\Quick\Domain\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use ColorAnomaly\Quick\Domain\QueueException;
use ColorAnomaly\Quick\Domain\QueueOverflowException;
use ColorAnomaly\Quick\Domain\QueueInappropriateReadException;
use ColorAnomaly\Quick\Domain\Queue;
use ColorAnomaly\Quick\Domain\Actor;

/**
 * Description of QueueService
 *
 * @author Hussain Nazan Naeem <hussennaeem@gmail.com>
 */
class QueueService {

    protected $dm;
    protected $config;

    public function __construct(DocumentManager $dm, $config = array()) {
        $this->dm = $dm;
        $this->config = $config;
    }

    public function addQueue($name, $starting = null, $ending = null) { // $starting included and $ending excluded.
        $q = new Queue();
        $q->setName($name);

        $q->setLowerBound($starting);
        $q->setUpperBound($ending);

        $this->dm->persist($q);

        $this->dm->flush();

        return true;
    }

    public function getQueueOfActor($actorId) {
        return $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findOneBy(array('actors.id' => $actorId));
    }

    public function getUserContextFor($actorId) {
        //$queue = $this->dm->createQueryBuilder('ColorAnomaly\Quick\Domain\Queue')->field('actors.id')->in(array($actorId))->getQuery()->execute();

        $queue = $this->getQueueOfActor($actorId);

        if ($queue instanceof Queue) {
            $role = '';
            foreach ($queue->getActors() as $actor) {
                if ($actor->getId() == $actorId) {
                    $role = $actor->getRole();
                }
            }

            $uc = array(
                'id' => $actorId,
                'queue' => $queue,
                'role' => $role
            );
        } else {
            $uc = array('id' => $actorId, 'queue' => null, 'role' => null);
        }

        return $uc;
    }

    public function getQueueNames() {
        $queues = $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findAll();

        $data = array();
        foreach ($queues as $q) {
            $data[$q->getName()] = $q->getName();
        }

        return $data;
    }
    
    public function getQueue($queueId = null) {
        if(is_null($queueId)) {
            return $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findAll();
        }
        
        try {
            $queue = $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findOneBy(array('name' => $queueId));
        } catch(\Exception $excp) {
            throw new QueueInappropriateReadException();
        }

        return $queue;
    }

    public function registerActor($queueId, $actorId, $role, $label = '') {
        if (strlen($label) == 0) {
            $label = $actorId;
        }

        $queue = $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findOneBy(array('name' => $queueId));

        if ($queue instanceof Queue) {
            foreach ($queue->getActors() as $actor) {
                if ($actor->getId() != $actorId && $actor->getRole() == $role && $actor->getLabel() == $label) {
                    throw new QueueException("Another actor is already occupying 'Serve token' role with given label '$label' for queue '" . $queue->getName() . "'.");
                }
            }

            $this->unregisterActor($actorId);

            $actor = new Actor();

            $actor->setId($actorId);
            $actor->setRole($role);
            $actor->setLabel($label);
            $actor->setServingToken(0);
            $actor->setDequeuedAt(null);

            $queue->addActor($actor);

            $this->dm->flush();

            return true;
        }

        return false;
    }

    public function unregisterActor($actorId) {
        $queues = $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findBy(array('actors.id' => $actorId));

        foreach ($queues as $queue) {
            try {
                $queue->removeActor($actorId);
            } catch (QueueException $excp) {
                continue;
            }
        }

        if (!empty($queues)) {
            $this->dm->flush();
        }

        return true;
    }

    public function enqueueToken($queueId) {
        $queue = $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findOneBy(array('name' => $queueId));

        if ($queue instanceof Queue) {
            $token = $queue->enqueueToken();

            $this->dm->flush();

            return $token;
        } else {
            throw new QueueException("Queue not found - '$queueId'");
        }
    }

    public function getLastTokenDispensed($queueId) {
        $queue = $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findOneBy(array('name' => $queueId));

        if ($queue instanceof Queue) {
            if ($queue->getEnqueueLast() == $queue->getLowerBound()) {
                throw new QueueInappropriateReadException("Queue '{$queue->getName()}' has not been queued at all.");
            }

            return $queue->getEnqueueLast() - 1;
        } else {
            throw new QueueException("Queue not found - '$queueId'");
        }
    }

    public function getStartingToken($queueId) {
        $queue = $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findOneBy(array('name' => $queueId));

        if ($queue instanceof Queue) {
            return $queue->getLowerBound();
        } else {
            throw new QueueException("Queue not found - '$queueId'");
        }
    }

    public function dequeueToken($queueId, $actorId, $recall = false) {
        $queue = $this->dm->getRepository('ColorAnomaly\Quick\Domain\Queue')->findOneBy(array('name' => $queueId));

        if ($queue instanceof Queue) {
            $token = $queue->dequeueToken($actorId, $recall);

            $this->dm->flush();

            return $token;
        } else {
            throw new QueueException("Queue not found - '$queueId'");
        }
    }

    public function getCurrentTokenServed($actorId) {
        $queue = $this->getQueueOfActor($actorId);

        $token = $queue->getActor($actorId)->getServingToken();

        return $token;
    }

}
