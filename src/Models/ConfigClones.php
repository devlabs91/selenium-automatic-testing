<?php 

namespace App\Models;

class ConfigClones {

    /** @var ConfigClone[] */
    private $vmClones;
    
    public function __construct( array $configClones = [] ) {
        $this->setConfigClones( $configClones );
    }
    
    private function addConfigCloneByKey( $key, ConfigClone $vmClone ) {
        $this->vmClones[$key] = $vmClone;
    }
    
    private function setConfigClones( array $configClones ) {
        $this->configClones = [];
    }
    
    public function getConfigCloneByKey( $key ) {
        if(array_key_exists($key, $this->vmClones)) {
            return $this->vmClones[$key];
        }
        return null;
    }
    
    public function hasConfigCloneByKey( $key ) {
        if(array_key_exists($key, $this->vmClones)) {
            return true;
        }
        return false;
    }
    
    public function getConfigClones() {
        return $this->vmClones;
    }
    
    public function deserialize( $data ) {
        foreach($data AS $d) {
            $configClone = new ConfigClone();$configClone->deserialize( $d );
            $this->addConfigCloneByKey( $configClone->getName(), $configClone );
        }
    }
    
}
