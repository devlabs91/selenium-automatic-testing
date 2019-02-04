<?php 

namespace App\Models;

class Config {

    private $source;
    private $spawn;
    
    /** @var ConfigClones */
    private $clones;
    
    private $networks;
    
    public function __construct( $data ) {
        $this->deserialize( $data );
    }
    
    public function getSource() {
        return $this->source;
    }

    public function getSourceName() {
        return $this->source['name'];
    }

    public function getSourceSnapshotName() {
        return $this->source['snapshot']['name'];
    }
    
    public function getSpawn() {
        return $this->spawn;
    }

    public function getConfigClones() {
        return $this->clones->getConfigClones();
    }
    
    public function hasConfigClone( $name ) {
        foreach($this->getConfigClones() AS $clone) {
            if( $clone->getName() == $name ) { return true; }
        }
        return false;
    }

    public function getConfigClone( $name ) {
        foreach($this->getConfigClones() AS $clone) {
            if( $clone->getName() == $name ) { return $clone; }
        }
        return null;
    }
    
    public function getNetworks() {
        return $this->networks;
    }
    
    public function deserialize( $data ) {
        $this->source = $data['source'];
        $this->spawn = $data['spawn'];
        $this->clones = new ConfigClones();
        $this->clones->deserialize( $data['clones'] );
        $this->networks = $data['networks'];        
    }
    
}
