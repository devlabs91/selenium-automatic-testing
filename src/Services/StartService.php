<?php 

namespace App\Services;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Symfony\Component\Yaml\Yaml;

class StartService {
    
    public $host;
    public $startPage;
    
    /** @var RemoteWebDriver */
    public $driver;
    
    public $servers;
    public $elements;
    
    public function __construct( $fileName ) {
        
        $content = file_get_contents( getcwd()."/".$fileName );
        $value = Yaml::parse($content);

        $this->host = 'http://'.$value['start']['servers'][0].':4444/wd/hub';
        $this->startPage = $value['start']['page'];
        $this->servers = $value['start']['servers'];
        $this->elements = $value['start']['elements'];
        
        return $this;
    }

    public function init() {
        $dc = DesiredCapabilities::internetExplorer();
        $dc->setCapability('ie.ensureCleanSession', true);
        $dc->setCapability('initialBrowserUrl', 'about:blank');
        $this->driver = RemoteWebDriver::create( $this->host, $dc, 3600000 );
    }
    
    public function getStartPage() {
        $this->driver->get( $this->startPage );
        $this->driver->manage()->window()->maximize();
    }

    public function runService() {
        $this->init();
        $this->getStartPage();
        
        $error = 0;
        while(1) {
            try {
                $element = $this->findElementByXpath( $this->elements[0] );
                if($element) {
                    $this->scrollToElement( $element );
                    try {
                        $element->click();
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