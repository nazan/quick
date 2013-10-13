<?php

/*
 * This software is a property of Color Anomaly.
 * Use of this software for commercial purposes is strictly
 * prohibited.
 */

namespace ColorAnomaly\Quick\Domain;

/**
 *
 * @author Hussain Nazan Naeem <hussennaeem@gmail.com>
 */
interface IntTokenQueueIface {
    public function enqueue();
    public function dequeue();
    public function isEmpty();
    public function reset();
}
