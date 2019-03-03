<?php 

namespace App\Services;

use App\Models\Config;
use Devlabs91\Vboxmanage\Services\VmService;
use App\Models\ConfigClone;
use App\Models\Options;

class VboxmanageService {
    
    /** @var Config */
    public $vbConfig;
    
    /** @var VmService */
    public $vmService;
    
    public function __construct( Config $vbConfig ) {
        $this->vbConfig = $vbConfig;
        $this->vmService = new VmService();
        if( !$this->vmService->hasVm( $this->vbConfig->getSourceName() ) ) {
            throw new \Exception( 'Source VM "'.$this->vbConfig->getSourceName().'" not found', 404 );
        }
        return $this;
    }
    
    public function takeSnapshot( $sourceName, $sourceSnapshotName ) {
        if( !$this->vmService->hasSnapshot( $this->vmService->getVm( $sourceName ), $sourceSnapshotName ) ) {
            $this->vmService->takeSnapshot( $this->vmService->getVm( $sourceName ), $sourceSnapshotName );
            if( !$this->vmService->hasSnapshot( $this->vmService->getVm( $sourceName ), $sourceSnapshotName ) ) {
                return false;
            }
        }
        return true;
    }
    
    public function deleteSnapshot( $sourceName, $sourceSnapshotName ) {
        if( $this->vmService->hasSnapshot( $this->vmService->getVm( $sourceName ), $sourceSnapshotName ) ) {
            $this->vmService->deleteSnapshot( $this->vmService->getVm( $sourceName ) , $this->vmService->getSnapshot( $this->vmService->getVm( $sourceName ), $sourceSnapshotName ) );
            if( $this->vmService->hasSnapshot( $this->vmService->getVm( $sourceName ), $sourceSnapshotName ) ) {
                return false;
            }
        }
        return true;
    }
    
    public function respawnCloneNetworks( Config $vbConfig ) {
        foreach( $vbConfig->getSourceNetwork() AS $nic => $config ) {
            $this->respawnCloneNetwork( $nic, $config );
            if( $config['attached'] == 'hostonly' ) {
                $this->vmService->configHostonlyVm( $this->vmService->getVm( $vbConfig->getSourceName() ), $nic, $config['name'] );
            }
        }
    }
    
    public function startVm( $sourceName, $start ) {
        $this->vmService->startVm( $this->vmService->getVm( $sourceName ), $start );
    }
    
    public function findClone( $name ) {
        if( ! $this->vbConfig->hasConfigClone( $name ) ) {
            throw new \Exception( 'Clone not found by name "'.$name.'"', 404 );
        }
        return $this->vbConfig->getConfigClone( $name );
    }

    public function startClones( $name, Options $options ) {
        if( $options->getClones()=='all') {
            $this->respawnAllNetworks( $this->vbConfig->getNetworks() );
        }
        foreach( $this->vbConfig->getConfigClones() AS $clone ) {
            if( ($options->getClones()=='all' && in_array( $clone->getName(), $this->vbConfig->getSpawn() ) ) || $options->getClones() == $clone->getName() ) {
                if(!$clone) {
                    throw new \Exception( 'Could not find configuration for clone by name "'.$options->getClones().'"', 404 );
                }
                $this->startClone( $name, $clone );
            }
        }
    }
    
    public function startClone( $name, ConfigClone $clone ) {
        if( ! $this->vmService->hasVm( $clone->getName() ) ) { $this->respawnClone( $name, $clone ); }
        $this->vmService->startVm( $this->vmService->getVm( $clone->getName() ), $clone->getStart() );
    }
    
    public function stopClones( Options $options ) { 
        foreach( $this->vbConfig->getConfigClones() AS $clone ) {
            if( $options->getClones() == 'all' || $options->getClones() == $clone->getName() ) {
                if( $this->vmService->hasVm( $clone->getName() ) ) {
                    $this->stopClone( $clone );
                }
            }
        }
    }

    public function stopClone( ConfigClone $clone ) {
        $this->vmService->stopVm( $this->vmService->getVm( $clone->getName() ) );
    }
    
    public function removeClones( Options $options ) {
        foreach($this->vbConfig->getConfigClones() AS $clone) {
            if($options->getClones() == 'all' || $options->getClones() == $clone->getName() ) {
                if( $this->vmService->hasVm( $clone->getName() ) ) {
                    $this->stopClone( $clone );
                    sleep(5);
                    $this->removeClone( $clone );
                }
            }
        }
    }
    
    public function removeClone( ConfigClone $clone ) {
        $this->vmService->removeVm( $this->vmService->getVm( $clone->getName() ) );
        if( $this->vmService->hasVm( $clone->getName() ) ) {
            throw new \Exception( 'Could not remove clone by name "'.$clone->getName().'"', 200 );
        }
    }
    
    public function spawnClones( $name, Options $options ) {
        if( $options->getClones()=='all') {
            $this->respawnAllNetworks( $this->vbConfig->getNetworks() );
        }
        foreach($this->vbConfig->getSpawn() AS $cloneName) {
            $clone = $this->findClone( $cloneName );
            if( $clone && ( $options->getClones() == 'all' || $options->getClones() == $clone->getName() ) ) {
                $this->spawnClone( $name, $clone );
            }
        }
    }
    
    public function spawnClone( $name, $clone ) {
        $this->respawnClone( $name, $clone );
    }
    
    public function respawnClones( $name, Options $options ) {
        foreach($this->vbConfig->getSpawn() AS $cloneName) {
            $clone = $this->findClone( $cloneName );
            if( $clone && ( $options->getClones()=='all' || $options->getClones() == $clone->getName() ) ) { 
                $this->respawnClone( $name, $clone);
            }
        }
    }
    
    public function respawnClone( $name, ConfigClone $clone ) {
        
        if( $this->vmService->hasVm( $clone->getName() ) ) {
            $this->stopClone( $clone ); // Stop Clone before try to remove
            sleep(5);$this->removeClone( $clone );
        }
        
        foreach( $clone->getNetworks() AS $nic => $network ) {
            $this->respawnCloneNetwork( $nic, $network );
        }
        
        $this->vmService->cloneVm( $this->vmService->getVm( $name ), $this->vmService->getSnapshot( $this->vmService->getVm( $name ), $this->vbConfig->getSourceSnapshotName() ), $clone->getName() );
        if( !$this->vmService->hasVm( $clone->getName() ) ) {
            throw new \Exception( 'Clone "'.$clone->getName().'" was not found to config', 404 );
        }
        $this->configVm( $clone );
    }
    
    public function respawnCloneNetwork( $nic, $cloneNetwork ) {
        foreach( $this->vbConfig->getNetworks() AS $network ) {
            if( $cloneNetwork['name'] == $network['name'] ) {
                $this->vmService->removeNetwork($network);
                $this->vmService->createNetwork($network);
            }
        }
    }

    public function respawnAllNetworks( $networks ) {
        foreach( $networks AS $network ) { // Remove all networks
            $this->vmService->removeNetwork($network);
        }
        foreach( $networks AS $network ) { // Create all networks
            $this->vmService->createNetwork($network);
        }
    }
    
    public function configVm( ConfigClone $clone ) {
        foreach( $clone->getNetworks() AS $nic => $config ) {
            if( $config['attached'] == 'hostonly' ) {
                $this->vmService->configHostonlyVm( $this->vmService->getVm( $clone->getName() ), $nic, $config['name'] );
            }
        }
    }
    
}