<?php 

namespace App\Models;

class Options {

    private $clones;
    private $start;
    private $stop;
    private $remove;
    private $spawn;
    private $initBase;
    private $respawn;
    private $startService;
    private $stopService;
    private $exitService;
    
    public function getClones() {
        return $this->clones;
    }
    
    public function selectedClone( $name ) {
        if( $this->getClones()=='all' || $this->getClones() == $name ) {
            return true;
        }
        return false;
    }
    
    public function getStart() {
        return $this->start;
    }

    public function getStop() {
        return $this->stop;
    }
    
    public function getRemove() {
        return $this->remove;
    }
    
    public function getSpawn() {
        return $this->spawn;
    }
    
    public function getInitBase() {
        return $this->initBase;
    }
    
    public function getRespawn() {
        return $this->respawn;
    }
    
    public function getStartService() {
        return $this->startService;
    }

    public function getStopService() {
        return $this->stopService;
    }
    
    public function getExitService() {
        return $this->exitService;
    }
    
    public function deserialize( $options ) {
        $this->clones = $options['clones'];
        $this->start = $options['start'];
        $this->stop = $options['stop'];
        $this->remove = $options['remove'];
        $this->spawn = $options['spawn'];
        $this->initBase = $options['init-base'];
        $this->respawn = $options['respawn'];
        $this->startService = $options['start-service'];
        $this->stopService = $options['stop-service'];
        $this->exitService = $options['exit-service'];
        
        return $this;
    }
    
}