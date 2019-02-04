<?php 

namespace App\Models;

class ConfigClone {

    private $name;
    private $networks;
    private $start;
    private $testsuite;
    
    public function __construct( $name = null, $networks = null, $start = null, $testsuit = null ) {
        $this->name = $name;
        $this->networks = $networks;
        $this->start = $start;
        $this->testsuite = $testsuit;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getNetworks() {
        return $this->networks;
    }

    public function getStart() {
        return $this->start;
    }
    
    public function getTestsuite() {
        return $this->testsuite;
    }
    
    public function deserialize( $data ) {
        $this->name = $data['name'];
        $this->networks = $data['network'];
        $this->start = $data['start'];
        if(key_exists('testsuite', $data)) {
            $this->testsuite = $data['testsuite'];
        }
    }
    
}
