<?php 

namespace App\Services;

use Symfony\Component\Yaml\Yaml;
use App\Entity\Log;
use Doctrine\Common\Persistence\ManagerRegistry;
use Devlabs91\Daemonrunner\Services\DaemonService;

class StartService {
    
    /** @var ManagerRegistry */
    public $doctrine;
    
    public $fileName;
    public $startPage;
    
    public $servers;
    public $elements;
    
    public $views;
    
    /** @var SeleniumService */
    public $selenium;
    
    public function __construct( ManagerRegistry $doctrine, $fileName ) {
        
        DaemonService::iamalive( $fileName . ' - ' . 'Service construct' );
        
        $this->doctrine = $doctrine;
        $this->fileName = $fileName;
        
        $content = file_get_contents( getcwd()."/".$fileName );
        $value = Yaml::parse($content);

        $this->startPage = $value['start']['page'];
        $this->servers = $value['start']['servers'];
        $this->elements = $value['start']['elements'];
        
        if(key_exists('views', $value['start'])) { $this->views = $value['start']['views']; }
        
        $proxy = null;
        if(key_exists('proxy', $value['start'])) { $proxy = $value['start']['proxy']; }
        $this->selenium = new SeleniumService( 'http://'.$value['start']['servers'][0].':4444/wd/hub', $proxy );
        
        return $this;
    }

    public function init() {
        
        while(1) {
            $driver = null;
            try {
                $driver = $this->selenium->create();
            } catch ( \Exception $e ) {
                DaemonService::iamalive( $this->fileName .' - initException - '. $e->getMessage() );
            }
            if($driver) { break; }
            sleep(2);
        }
    }
    
    public function getStartPage() {
        $this->selenium->get( $this->startPage );
    }

    public function runService() {
        DaemonService::iamalive( $this->fileName . ' - runService' );
        
        $this->init();
        $this->getStartPage();
        
        $error = 0;$views = null;$visited = 0;
        if( $this->views ) {
            $views = rand( $this->views['min'], $this->views['max'] );
        }
        while(1) {
            try {
                $element = $this->selenium->findElementByXpath( $this->elements[0] );
                if($element) {
                    DaemonService::iamalive( $this->fileName . ' - '.$visited . ' - ' . $views . ' - ' . $this->selenium->getCurrentURL() );
                    $this->logPage( $this->selenium->getCurrentURL(), $views, $visited );
                    $this->selenium->scrollToElement( $element );
                    try {
                        if($views && $visited>$views) {
                            $views = rand( $this->views['min'], $this->views['max'] );$visited=0;
                            $this->restartSelenium();
                        } else {
                            $this->selenium->clickOnElement($element);$visited++;
                        }
                        $error = 0;
                    } catch (\Throwable $e) {
                        echo($e->getMessage().PHP_EOL);
                    }
                } else {
                    DaemonService::iamalive( $this->fileName . ' - '.$visited . ' - ' . $views . ' - ' . 'Element not found.' );
                    $this->restartSelenium();
                }
                sleep( rand(5,10) );
            } catch (\Throwable $e) {
                DaemonService::iamalive( $this->fileName . ' - '.$visited . ' - ' . $views . ' - ' . $e->getMessage() );
                $error++;
                if($error>=3) {
                    $this->restartSelenium();$error = 0;
                }
                sleep( rand(5,10) );
            }
            if( DaemonService::exitService( $this->fileName ) ) {
                break;
            }
        }
        try {
            $this->selenium->close();
        } catch (\Throwable $e) {
            DaemonService::iamalive( $this->fileName . ' - ' . $e->getMessage() );
        }
        DaemonService::confirmExitService( $this->fileName );
    }
    
    public function restartSelenium() {
        $this->selenium->close();
        $this->init();
        $this->getStartPage();
    }
    
    public function logPage( $url, $views, $visited ) {
        $log = new Log();
        $log->setCreatedAt( new \DateTime() );
        $log->setPage( $url );
        $log->setHost( $this->servers[0] );
        $log->setIp( $this->servers[0] );
        $log->setViews( $views );
        $log->setVisited( $visited );
        $this->doctrine->getManager()->persist( $log );
        $this->doctrine->getManager()->flush();
    }
    
}