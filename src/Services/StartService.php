<?php 

namespace App\Services;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Symfony\Component\Yaml\Yaml;
use App\Entity\Log;
use Doctrine\Common\Persistence\ManagerRegistry;
use Devlabs91\Daemonrunner\Services\DaemonService;

class StartService {
    
    public $host;
    public $startPage;
    
    /** @var RemoteWebDriver */
    public $driver;
    
    public $servers;
    public $elements;
    
    public $views;
    
    public $proxy;
    
    /** @var ManagerRegistry */
    public $doctrine;
    
    public function __construct( ManagerRegistry $doctrine, $fileName ) {
        
        DaemonService::iamalive( 'Service construct' );
        
        $this->doctrine = $doctrine;
        
        $content = file_get_contents( getcwd()."/".$fileName );
        $value = Yaml::parse($content);

        $this->host = 'http://'.$value['start']['servers'][0].':4444/wd/hub';
        $this->startPage = $value['start']['page'];
        $this->servers = $value['start']['servers'];
        $this->elements = $value['start']['elements'];
        
        if(key_exists('proxy', $value['start'])) {
            $this->proxy = $value['start']['proxy'];
        }

        if(key_exists('views', $value['start'])) {
            $this->views = $value['start']['views'];
        }
        
        return $this;
    }

    public function init() {
        
        $dc = DesiredCapabilities::internetExplorer();
        $dc->setCapability('ie.ensureCleanSession', true);
        $dc->setCapability('initialBrowserUrl', 'about:blank');
        $dc->setCapability('timeouts', [
            'implicit' => 0, 'pageLoad' => 600000, 'script' => 60000
        ]);
        if($this->proxy) {
            $proxy = [
                'proxyType' => 'manual'
            ];
            if(key_exists('type', $this->proxy) && $this->proxy['type']=='socks') {
                $proxy['socksProxy'] = $this->proxy['ip'].':'.$this->proxy['port'];
            } else {
                $proxy['httpProxy'] = $this->proxy['ip'].':'.$this->proxy['port'];
                $proxy['sslProxy'] = $this->proxy['ip'].':'.$this->proxy['port'];
            }
            $dc->setCapability('proxy', $proxy );
        }
        while(1) {
            try {
                $this->driver = RemoteWebDriver::create( $this->host, $dc, 3600000, 120000 );
            } catch ( \Exception $e ) {
                DaemonService::iamalive( $e->getMessage() );
            }
            if($this->driver) { break; }
            sleep(2);
        }
    }
    
    public function getStartPage() {
        $this->driver->get( $this->startPage );
        $this->driver->manage()->window()->maximize();
    }

    public function runService() {
        DaemonService::iamalive( 'runService' );
        
        $this->init();
        $this->getStartPage();
        
        $error = 0;$views = null;$visited = 0;
        if( $this->views ) {
            $views = rand( $this->views['min'], $this->views['max'] );
        }
        while(1) {
            DaemonService::iamalive( 'runService while' );
            try {
                $element = $this->findElementByXpath( $this->elements[0] );
                if($element) {
                    $this->logPage( $this->driver->getCurrentURL(), $views, $visited );
                    $this->scrollToElement( $element );
                    try {
                        if($views && $visited>$views) {
                            $views = rand( $this->views['min'], $this->views['max'] );$visited=0;
                            if( $this->driver ) { $this->driver->close(); }
                            $this->init();
                            $this->getStartPage();
                        } else {
                            $element->click();$visited++;
                        }
                        $error = 0;
                    } catch (\Throwable $e) {
                        echo($e->getMessage().PHP_EOL);
                    }
                } else {
                    echo("Element not found.");
                    if( $this->driver ) { $this->driver->close(); }
                    $this->init();
                    $this->getStartPage();
                }
                sleep( rand(5,10) );
            } catch (\Throwable $e) {
                echo($e->getMessage().PHP_EOL);
                $error++;
                if($error==3) {
                    if( $this->driver ) { $this->driver->close(); }
                    $this->init();
                    $this->getStartPage();
                }
                sleep( rand(5,10) );
            }
        }
        $this->driver->close();
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
    
    public function findElementByXpath( $xPath ) {
        return $this->driver->findElement( WebDriverBy::xpath( $xPath ) );
    }
    
    public function scrollToElement( RemoteWebElement $element) {
        $location = $element->getLocation();
        if ($location->getY() > 200) {
            $this->scrollTo( 0, $location->getY() - 100 ); // Make sure element is in the view but below the top navigation pane
        }
    }
    
    public function scrollTo( $xPosition = 0, $yPosition = 0) {
        for($y = 0; $y <= $yPosition ; $y+=(rand(1,20)*3) ) {
            $string = sprintf('window.scrollTo(%d, %d)', $xPosition, $y );
            $this->driver->executeScript( $string );
            usleep( rand(1,50) );
            $stop = rand(1,100); if(in_array($stop, [ 15, 25, 50, 85, 90])) { sleep(1); }
        }
    }
    
}