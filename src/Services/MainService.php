<?php 

namespace App\Services;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Yaml\Yaml;
use App\Models\Config;
use App\Models\ConfigClone;
use App\Models\Options;

class MainService {
    
    /** @var TestsuiteService */
    public $testsuiteServie;
    
    /** @var Config */
    public $vbConfig;
    
    /** @var VboxmanageService */
    public $vboxmanageService;
    
    public function __construct( ManagerRegistry $doctrine, $fileName ) {
        
        $this->testsuiteServie = new TestsuiteService( $doctrine );
        
        $content = file_get_contents( getcwd()."/".$fileName );
        $this->vbConfig = new Config( Yaml::parse($content) );
        $this->vboxmanageService = new VboxmanageService( $this->vbConfig );
        
        return $this;
    }
    
    public function initBase( ) { 
        
        if(!$this->vboxmanageService->deleteSnapshot( $this->vbConfig->getSourceName(), $this->vbConfig->getSourceSnapshotName() ) ) {
            throw new \Exception( 'Could not remove VM Snapshot "'.$this->vbConfig->getSourceSnapshotName().'"', 200 );
        }
        
        $this->vboxmanageService->respawnCloneNetworks( $this->vbConfig );
        
        $initConfig = $this->vbConfig->getSourceInit();
        $this->vboxmanageService->startVm( $this->vbConfig->getSourceName(), $initConfig['start'] );
        
        $this->testsuiteServie->start( $initConfig['testsuite'] );
        
    }
    
    public function runService( Options $options ) {
        
        if( ! $this->vboxmanageService->takeSnapshot( $this->vbConfig->getSourceName( ), $this->vbConfig->getSourceSnapshotName() ) ) {
            throw new \Exception( 'Source VM Snapshot "'.$this->vbConfig->getSourceSnapshotName().'" not found', 404 );
        }
        
        if( $options->getStart() ) {
            $this->vboxmanageService->startClones( $this->vbConfig->getSourceName(), $options );
        } else if( $options->getStop() ) {
            $this->vboxmanageService->stopClones( $options );
        } else if( $options->getRemove() ) {
            $this->vboxmanageService->removeClones( $options );
        } else if( $options->getRespawn() ) {
            $this->vboxmanageService->respawnClones( $this->vbConfig->getSourceName(), $options );
        } else if( $options->getSpawn() ) {
            $this->vboxmanageService->spawnClones( $this->vbConfig->getSourceName(), $options );
        }
        
        if( $options->getStartService() ) {
            $this->toogleStartServices( $options, true );
        } else if( $options->getStopService() ) {
            $this->toogleStartServices( $options, false );
        } else if( $options->getExitService() ) {
            $this->exitServices( $options);
        }
        
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
    
}