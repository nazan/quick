#Quick - A simple queue implementation.

Installation instructions
-------------------------

1. Set up vhost in Apache, point to public folder as document root. Remember to restart web server.

2. MongoDB 10gen - create database and set the config/application.ini file accordingly.

3. Install 0MQ and the php bindings for 0MQ.
    1. 0MQ can be installed via apt-get if you are on Ubuntu. Instructions at http://zeromq.org/distro:debian (2.2 series recommended)
    2. 0MQ PHP Bindings installation instructions can be found at http://zeromq.org/bindings:php
    
4. Create following directories if they do not exist (paths given relative to application root)

    odm/Hydrators  
    odm/Proxies  
    logs  
    templates/cache  

5. cd to application root and enter the following commands in terminal. replace 'user:group' with owning user of the web server
    
    chown -R user:group odm  
    chown -R user:group logs  
    chown -R user:group template/cache  

6. Install composer (system wide installation is preferred). Instructions here http://getcomposer.org/doc/00-intro.md#globally
    
7. cd to application root and run the following command at the terminal.

    composer install

8. Set up the web socket server.

  1. Make sure the script bin/qc-pusher-server.php is executable.
  2. Run it at a terminal. Note that Websocket server must be run at the same machine as Apache web server is running.

      ./bin/qc-pusher-server.php

9. Go to browser and create a new queue by entering the following URL.
    
    http://your-virtual-host-name/add-queue/queue-name/starting-number/ending-number

      'queue-name' = any name without spaces  
      'starting-number' = any integer less than 'ending-number'  
      'ending-number' = any integer greater than 'starting-number'  

10. Now give it a go. Start at http://your-virtual-host-name/register

11. Please e-mail me at hussennaeem@gmail.com if you run into any trouble while installing. Usage instructions are not complete yet so please feel free to inquire about that as well.