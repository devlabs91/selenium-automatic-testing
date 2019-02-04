<?php 

namespace App\Services;
use Devlabs91\Daemonrunner\Services\DaemonService;
use Doctrine\Common\Persistence\ManagerRegistry;

class TestsuiteService {
    
    /** @var ManagerRegistry */
    public $doctrine;
    
    public function __construct( ManagerRegistry $doctrine ) {
        $this->doctrine = $doctrine;
    }
    
    public function start( $filename) {
        $service = new StartService( $this->doctrine, $filename );
        if( DaemonService::isRunning( $filename ) ) { DaemonService::stop( $filename ); }
        DaemonService::start( $filename, $service );
    }
    
    public function stop( $filename ) {
        DaemonService::stop( $filename );
    }
    
    public function exit( $filename ) {
        DaemonService::requestExitService( $filename );
        while(1) {
            if( !DaemonService::isRunning($filename) ) { break; }
            sleep(1);
        }
    }
    
}