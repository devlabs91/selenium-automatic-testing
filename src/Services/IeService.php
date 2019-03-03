<?php 

namespace App\Services;

use Facebook\WebDriver\Remote\DesiredCapabilities;

class IeService {
    
    public function getDesiredCapabilities( $proxyConfig = null ) {
        
        $dc = DesiredCapabilities::internetExplorer();
        $dc->setCapability('ie.ensureCleanSession', true);
        $dc->setCapability('initialBrowserUrl', 'about:blank');
        $dc->setCapability('timeouts', [
            'implicit' => 0, 'pageLoad' => 600000, 'script' => 60000
        ]);
        if( $proxyConfig ) {
            $proxy = [
                'proxyType' => 'manual'
            ];
            $proxyIpPort = $proxyConfig['ip'].':'.$proxyConfig['port'];
            if(key_exists('type', $proxyConfig) && $proxyConfig['type']=='socks') {
                $proxy['socksProxy'] = $proxyIpPort;
            } else {
                $proxy['httpProxy'] = $proxyIpPort;
                $proxy['sslProxy'] = $proxyIpPort;
            }
            $dc->setCapability('proxy', $proxy );
        }
        return $dc;
    }
    
}