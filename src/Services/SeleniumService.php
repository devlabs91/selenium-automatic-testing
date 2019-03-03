<?php 

namespace App\Services;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\RemoteWebElement;

class SeleniumService {
    
    public $browsers;

    /** @var string */
    public $host;
    
    /** @var DesiredCapabilities */
    public $dc;
    
    /** @var RemoteWebDriver */
    public $driver;
    
    public function __construct( $host, $proxyConfig ) {
        $this->host = $host;
        $browserService = new IeService();
        $this->dc = $browserService->getDesiredCapabilities( $proxyConfig );
    }
    
    public function create() {
        $this->driver = RemoteWebDriver::create( $this->host, $this->dc, 3600000, 120000 );
        return $this->driver;
    }
    
    public function close() {
        if( $this->driver ) { $this->driver->close(); }
    }
    
    public function get( $url ) {
        $this->driver->get( $url );
        $this->driver->manage()->window()->maximize();
    }
    
    public function getCurrentURL() {
        return $this->driver->getCurrentURL();
    }
    
    public function findElementByXpath( $xPath ) {
        return $this->driver->findElement( WebDriverBy::xpath( $xPath ) );
    }

    public function clickOnElement( RemoteWebElement $element) {
        $element->click();
    }
    
    public function scrollToElement( RemoteWebElement $element) {
        $location = $element->getLocation();
        if ($location->getY() > 200) {
            $this->scrollTo( 0, $location->getY() - 50 ); // Make sure element is in the view but below the top navigation pane
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
