# selenium-automatic-testing
Selenium Automatic Testing

This is a sandbox starter-kit for automatic testing using Selenium.

resources/
    IEDriverServer.exe
    selenium-server-standalone-3.8.1.jar

Quick setup guide for running Selenium on Windows 10 in virtualbox.

1. Download and install latest version of virtualbox for your OS.
2. Download and install the Windows 10 iso available from microsoft (Create account "User", without any password).
3. Download resources/* into a folder on the Desktop called "Selenium".
4. Download and install Java JDK 11.0.2.
5. Disable UAC
6. Disable Powersaving (Screen Off).
7. Run 'shell:startup', and copy the start.bat into the startup folder.

Now, your VM should be ready and auto start Selenium. Note the IP of the Server, once it start up, and you can proceede to test.

Configure a mysql database called : selenium
make sure to use collation: utf8mb4_unicode_ci

and a user with name : selenium
and password : selenium

it should be conectable using the string in the .env file.

DATABASE_URL=mysql://selenium:selenium@127.0.0.1:3306/selenium

Initialize the database by :

./bin/console doctrine:schema:update --force

Create a start.yaml file with minimum configuration :

start:
    servers:
        - '192.168.56.100'
    page: 'https://www.bing.com/search?q=selenium'
    elements: 
        - '//*[@id="b_results"]/li[13]/nav/ul/li[7]/a'
        - '//*[@id="b_results"]/li[14]/nav/ul/li[8]/a'

./bin/console app:start start.yaml

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
