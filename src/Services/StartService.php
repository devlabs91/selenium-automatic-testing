<?php 

namespace App\Services;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\RemoteWebElement;

class StartService {
    
    public $host;
    public $startPage;
    
    /** @var RemoteWebDriver */
    public $driver;
    
    public function __construct( $serverIp, $startPage ) {
        
        $this->host = 'http://'.$serverIp.':4444/wd/hub';
        $this->startPage = $startPage;
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
        $this->getStartPage();
    }
    
    public function scrollToView( RemoteWebDriver $driver, WebDriverBy $selector ) {
        $element = $driver->findElement( $selector );
        $this->scrollIntoToView($driver, $element);
        return $element;
    }
    
    public function scrollIntoToView(RemoteWebDriver $driver, RemoteWebElement $element) {
        $location = $element->getLocation();
        if ($location->getY() > 200) {
            $this->ScrollTo($driver, 0, $location->getY() - 100); // Make sure element is in the view but below the top navigation pane
        }
    }
    
    public function scrollTo( RemoteWebDriver $driver, $xPosition = 0, $yPosition = 0) {
        for($y = 0; $y <= $yPosition ; $y+=(rand(1,20)*3) ) {
            $string = sprintf('window.scrollTo(%d, %d)', $xPosition, $y );
            $driver->executeScript( $string );
            usleep( rand(1,50) );
            $stop = rand(1,100); if(in_array($stop, [ 15, 25, 50, 85, 90])) { sleep(1); }
        }
    }
    
}