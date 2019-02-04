Selenium Automatic Testing
==========================

This is a sandbox starter-kit for automatic testing using Selenium.

resources/files/
    Selenium/
        geckodriver.exe
        IEDriverServer.exe
        MicrosoftWebDriver.exe
        selenium-server-standalone-3.8.1.jar
    start.bat

Prepare to create a iso disk image by downloading Java JDK 11.0.2 & Firefox Setup 65.0.

How to create a iso disk image if needed on osx :

    Use Diskutil to create the Master.cdr of the folder with all the files, then run the following :
        hdiutil makehybrid -iso -joliet -o Master.iso Master.cdr

Setup
-----

Quick setup guide for running Selenium on Windows 10 in virtualbox.

1. Download and install latest version of virtualbox for your OS.
2. Download and the Windows 10 iso available from microsoft.
2.1. Create a virtual machine named 'Windows 10 Master'.
2.2. Create a host only interface, add a DHCP server to the interface, and make sure to set 'Windows 10 Master' to use this interface only.
2.3. Install Windows 10, make sure to create account "User", without any password.
    
    8.6G    /Users/user/VirtualBox VMs/Windows 10 Master
    
2.4. Once installed create a clone that we will use as base called 'Windows 10 Base'.

    2.1M    /Users/user/VirtualBox VMs/Windows 10 Base

4. Attach the 'resources/iso/selenium.iso'
4.1. Install Java JDK 11.0.2 from the iso.
4.2. Optional, install Firefox Setup 65.0 from the iso.
5. Copy the Selenium folder in the iso to the Desktop.
5. Disable UAC
6. Disable Powersaving (Screen Off).
7. Run 'shell:startup', and copy the start.bat from the iso into the startup folder.
8. Add ENVs as follows :

    ClassPath C:\Program Files\Java\jdk-11.0.2\lib
    
    Add to Path :
        C:\Program Files\Java\jdk-11.0.2\bin
        C:\Users\User\Desktop\Selenium

9. Restart once more, and open internet explorer, update settings, the firewall when requested, then shut it down.
 
    2.2G    /Users/user/VirtualBox VMs/Windows 10 Base

Now, your VM should be ready and auto start Selenium. Note the IP of the Server, once it start up, and you can proceede to test.

Database
--------

Configure a mysql database called : selenium
make sure to use collation: utf8mb4_unicode_ci

and a user with name : selenium
and password : selenium

it should be conectable using the string in the .env file.

DATABASE_URL=mysql://selenium:selenium@127.0.0.1:3306/selenium

Initialize the database by :

./bin/console doctrine:schema:update --force

Update files with any changes needed :

    resources/config/config.dist.yml
    resources/config/testsuite.dist.clone1.yml
    
Then run :

./bin/console app:vboxmanage resources/config/config.dist.yml --spawn
./bin/console app:vboxmanage resources/config/config.dist.yml --start=all

This should launch the app, and open https://www.bing.com in IE, you should see a log in your database.

Proxy Server
------------

Add config for proxy under :

start:
    servers:
        - '192.168.56.100'
    proxy:
        ip: '192.168.56.1'
        port: '8080'

Advanced Tips
-------------

Limit Virtualbox internet access when setup by adding a network adapter (Global Tools), with a DHCP server. Use this network for the instance you are creating, this will allow you to install Windows, without internet access, it will reduce size of the Instance you are creating, by not downloading any updates.

Once you are setup, clone the instance, and never start the original instance again, then use a proxy for internet access, this way you can delete the clone once you are done with it. This reduces usage of disk space.

Original instance will use around 8GB of disk space, and each clone uses roughly 3 to 4 GB of disk space for a couple of hours worth of work.
