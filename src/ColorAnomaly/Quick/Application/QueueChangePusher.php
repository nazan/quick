<?php

namespace ColorAnomaly\Quick\Application;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\Wamp\Topic;

class QueueChangePusher implements WampServerInterface {
    
    public function __construct() {
    }

    /**
     * A lookup of all the topics clients have subscribed to
     */
    protected $subscribedTopics = array();

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        // When a visitor subscribes to a topic link the Topic object in a  lookup array
        if (!array_key_exists($topic->getId(), $this->subscribedTopics)) {
            $this->subscribedTopics[$topic->getId()] = $topic;
        }
        
        echo "{$topic->getId()} subscriber count -> {$topic->count()}" . PHP_EOL;
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {
        
    }

    public function onOpen(ConnectionInterface $conn) {
        
    }

    public function onClose(ConnectionInterface $conn) {
        
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        
    }
    
    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function onQueueChange($json) {
        $queueData = json_decode($json, true);
        
        if(isset($this->subscribedTopics['any'])) {
            if($this->subscribedTopics['any']->count() > 0) {
                echo "Broadcasting to -> 'any'" . PHP_EOL;
                $this->subscribedTopics['any']->broadcast($queueData);
            } else {
                unset($this->subscribedTopics['any']);
            }
        }

        // If the lookup topic object isn't set there is no one to publish to
        if (!array_key_exists($queueData['queueId'], $this->subscribedTopics)) {
            return;
        }

        $topic = $this->subscribedTopics[$queueData['queueId']];
        
        if($topic->count() > 0) {
            echo "Broadcasting to -> '{$queueData['queueId']}'" . PHP_EOL;
            // re-send the data to all the clients subscribed to that category
            $topic->broadcast($queueData);
        } else {
            unset($this->subscribedTopics[$queueData['queueId']]);
        }
    }

}
