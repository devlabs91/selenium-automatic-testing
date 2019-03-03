<?php 

namespace App\Services;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Yaml\Yaml;
use App\Models\Config;
use Devlabs91\Vboxmanage\Services\VmService;
use App\Models\ConfigClone;
use App\Models\Options;

class VboxmanageService {
    
    /** @var ManagerRegistry */
    public $doctrine;
    
    /** @var Config */
    public $vbConfig;
    
    /** @var VmService */
    public $vmService;
    
    /** @var TestsuiteService */
    public $testsuiteServie;
    
    public function __construct( ManagerRegistry $doctrine, $fileName ) {
        
        $this->doctrine = $doctrine;
        
        $content = file_get_contents( getcwd()."/".$fileName );
        $this->vbConfig = new Config( Yaml::parse($content) );
        
        $this->vmService = new VmService();

        $this->testsuiteServie = new TestsuiteService($doctrine);
        
        if( !$this->vmService->hasVm( $this->vbConfig->getSourceName() ) ) {
            throw new \Exception( 'Source VM "'.$this->vbConfig->getSourceName().'" not found', 404 );
        }
        
        return $this;
    }
    
    public function initBase( ) { 
        
        if( $this->vmService->hasSnapshot( $this->vmService->getVm( $this->vbConfig->getSourceName() ), $this->vbConfig->getSourceSnapshotName() ) ) { 
            $this->vmService->deleteSnapshot( $this->vmService->getVm( $this->vbConfig->getSourceName() ) , $this->vmService->getSnapshot( $this->vmService->getVm( $this->vbConfig->getSourceName( ) ), $this->vbConfig->getSourceSnapshotName() ) );
            if( $this->vmService->hasSnapshot( $this->vmService->getVm( $this->vbConfig->getSourceName() ), $this->vbConfig->getSourceSnapshotName() ) ) {
                throw new \Exception( 'Could not remove VM Snapshot "'.$this->vbConfig->getSourceSnapshotName().'"', 200 );
            }
        }
        
        foreach( $this->vbConfig->getSourceNetwork() AS $nic => $config ) {
            $this->respawnCloneNetwork( $nic, $config );
            if( $config['attached'] == 'hostonly' ) {
                $this->vmService->configHostonlyVm( $this->vmService->getVm( $this->vbConfig->getSourceName() ), $nic, $config['name'] );
            }
        }

        $initConfig = $this->vbConfig->getSourceInit();
        $this->vmService->startVm( $this->vmService->getVm( $this->vbConfig->getSourceName() ), $initConfig['start'] );
        $this->testsuiteServie->start( $initConfig['testsuite'] );
        
    }
    
    public function runService( Options $options ) {

        if( !$this->vmService->hasSnapshot( $this->vmService->getVm( $this->vbConfig->getSourceName( ) ), $this->vbConfig->getSourceSnapshotName() ) ) {
            $this->vmService->takeSnapshot( $this->vmService->getVm( $this->vbConfig->getSourceName( ) ), $this->vbConfig->getSourceSnapshotName() );
            if( !$this->vmService->hasSnapshot( $this->vmService->getVm( $this->vbConfig->getSourceName( ) ), $this->vbConfig->getSourceSnapshotName() ) ) {
                throw new \Exception( 'Source VM Snapshot "'.$this->vbConfig->getSourceSnapshotName().'" not found', 404 );
            }
        }
        
        if( $options->getStart() ) {
            $this->startClones( $this->vbConfig->getSourceName(), $options );
        } else if( $options->getStop() ) {
            $this->stopClones( $options );
        } else if( $options->getRemove() ) {
            $this->removeClones( $options );
        } else if( $options->getRespawn() ) {
            $this->respawnClones( $this->vbConfig->getSourceName(), $options );
        } else if( $options->getSpawn() ) {
            $this->spawnClones( $this->vbConfig->getSourceName(), $options );
        }
        
        if( $options->getStartService() ) {
            $this->toogleStartServices( $options, true );
        } else if( $options->getStopService() ) {
            $this->toogleStartServices( $options, false );
        } else if( $options->getExitService() ) {
            $this->exitServices( $options);
        }
            
        
    }
    
    public function findClone( $name ) {
        if( ! $this->vbConfig->hasConfigClone( $name ) ) {
            throw new \Exception( 'Clone not found by name "'.$name.'"', 404 );
        }
        return $this->vbConfig->getConfigClone( $name );
    }

    public function exitServices( Options $options ) {
        foreach( $this->vbConfig->getConfigClones() AS $clone ) {
            if( $options->selectedClone( $clone->getName() ) ) { $this->exitService( $clone ); }
        }
    }
    
    public function exitService( ConfigClone $clone ) {
        $this->testsuiteServie->exit( $clone->getTestsuite() );
        echo("Exited ".$clone->getTestsuite().PHP_EOL);
    }
    
    public function toogleStartServices( Options $options, $toogle ) {
        foreach( $this->vbConfig->getConfigClones() AS $clone ) {
            if( $options->selectedClone( $clone->getName() ) ) { $this->toogleStartService( $clone, $toogle ); }
        }
    }
    
    public function toogleStartService( ConfigClone $clone, $toogle ) {
        if( $toogle ) {
            $this->testsuiteServie->start( $clone->getTestsuite() );
        } else {
            $this->testsuiteServie->stop( $clone->getTestsuite() );
        }
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
        $this->testsuiteServie->stop( $clone->getTestsuite() );
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