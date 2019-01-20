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

Create a start.yaml file with minimum configuration :

start:
    servers:
        - '192.168.1.119'
    page: 'https://www.bing.com/search?q=selenium'
    elements: 
        - '//*[@id="b_results"]/li[13]/nav/ul/li[7]/a'
        - '//*[@id="b_results"]/li[14]/nav/ul/li[8]/a'

./bin/console app:start start.yaml

This should launch the app, and open https://www.bing.com in IE.
