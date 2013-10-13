<?php

/*
 * Property of Color Anomaly.
 */

namespace ColorAnomaly\Quick\Application;

/**
 * Description of AuthenticationMiddleware.
 *
 * @author Hussain Nazan Naeem <hussennaeem@gmail.com>
 */
class UseCaseBasedRedirectMiddleware extends \Slim\Middleware {
    public function call() {
        $app = $this->app;
        
        $resourceURI = $app->request->getResourceUri();
        
        if(in_array($resourceURI, array('/enqueue', '/dequeue', '/display', '/'))) {
            $uc = $app->getUserContext();
            if(is_null($uc['queue'])) {
                $app->redirect('/register');
            } elseif($resourceURI != $uc['role']) {
                $app->redirect($uc['role']);
            }
        }
        
        $this->next->call();
        return;
    }

}
