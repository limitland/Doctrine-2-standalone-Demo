Doctrine2 ORM standalone Demo
=============================
Developing for a web application using Doctrine 2 without Symfony.
###### Date: 2014-04, Tags: PHP, Doctine 2, Twig, Howto


Working with symfony 2 is a huge time saver for me. But sometimes with smaller applications I do not want to use the whole framework,
rather a few modules only. That is in particular the template engine [Twig](http://twig.sensiolabs.org/) and the 
[Doctrine](http://doctrine-project.org/) DBAL and ORM. Especially the object relation mapper 
makes it very easy to handle the data in the database. 

Both projects are ditributed with the Symfony 2 standard edition, but can be used standalone as well. This article demonstrates how to 
set up a web application like that. 


Installations
-------------


This webapp assumes a LAMP environment with a minimal PHP version of `5.3.3`. The installation of the modules will be done by the 
dependency management tool [Composer](https://getcomposer.org/). Composer requires th PHP module `PHAR`, which can be installed 
as a package `php5-phar` (distribution flavored names). 

Additionally the configuration syntax `Yaml` is being used, which you might need to compile using `pecl install yaml` and
enable in the `php.ini`:

    ; extension=yaml.dll on windiows
    extension=yaml.so


Project Setup
-------------


Some basic webapp projects directories need to be created in a location accessible by the webserver.

    mkdir doctrine2-standalone
    cd doctrine2-standalone
    mkdir -p app/config controller model/Entity model/EntityProxy views htdocs

Then download and configure composer, which downloads and installs the modules. 

    curl -sS https://getcomposer.org/installer | php
    echo "{}" > composer.json
    php composer.phar require php>=5.3.3 twig/twig:1.*@dev doctrine/dbal:2.5.* doctrine/orm:2.5.*

With that the `vendor` directory is created and within that the file `autoload.php`. That is the Composer autoloader, 
which will be used to load the modules/libraries.  


Developing the Application
--------------------------


First a configuration file `app/config/parameters.yml` needs to be created. This holds the basic application and database 
access configuration. 

`app/config/parameters.yml`

    locale: 'de_DE'
    
    site:
      name: 'Doctrine 2 standalone Demo'
    
    database:
      dbname: 'demodb'
      user: 'root'
      password: 'password'
      host: 'localhost'
      driver: 'pdo_mysql'
      charset: 'utf8'
      driverOptions:
        1002: 'SET NAMES utf8'

After that a .htaccess file is created in the htdocs directory (the webserver document root), courtesy copy from a Symfony 2 distribution. 
This masks the `index.php` file. 

`htdocs/.htaccess`

    # index.php rewriting, taken from symfony2.
    DirectoryIndex index.php
    <IfModule mod_rewrite.c>
        RewriteEngine On
    
        RewriteCond %{REQUEST_URI}::$1 ^(/.+)/(.*)::\2$
        RewriteRule ^(.*) - [E=BASE:%1]
    
        RewriteCond %{ENV:REDIRECT_STATUS} ^$
        RewriteRule ^index\.php(/(.*)|$) %{ENV:BASE}/$2 [R=301,L]
    
        # If the requested filename exists, simply serve it.
        # We only want to let Apache serve files and not directories.
        RewriteCond %{REQUEST_FILENAME} -f
        RewriteRule .? - [L]
    
        # Rewrite all other queries to the front controller.
        RewriteRule .? %{ENV:BASE}/index.php [L]
    </IfModule>
    
    <IfModule !mod_rewrite.c>
        <IfModule mod_alias.c>
            # When mod_rewrite is not available, we instruct a temporary redirect of
            # the startpage to the front controller explicitly so that the website
            # and the generated links can still be used.
            RedirectMatch 302 ^/$ /index.php/
            # RedirectTemp cannot be used instead
        </IfModule>
    </IfModule>

After that an index.php file needs to be created in the document root, in this case a simple reference to the `FrontendController`.

`htdocs/index.php`

    <?php
    
    require_once __DIR__ . '/../controller/FrontendController.php';


This Frontend Controller inherits the class `Application`, which prepares the basic Requests and Responses. Furthermore it simply
passes the configuration to `index.html.twig` and renders and returns theview. 

`controller/FrontendController.php`

    <?php
    
    require_once __DIR__ . '/../app/app.php';
    
    class FrontendController extends Application
    {
    
        public function handleRequests( array $request )
        {
            $view = '';
            
            $viewdata = array(
                'config' => $this->config
            );
            
            $view = $this->twig->render('index.html.twig', $viewdata);
            
            return $view;
        }
    }
    
    $app = new FrontendController();
    $view = $app->handleRequest();
    $app->handleResponse( $view );

This is the Application class:

`app/app.php`

    <?php
    
    define('DS', DIRECTORY_SEPARATOR);
    
    class Application
    {
        protected $config;
        protected $twig;
        protected $em;
        protected $conn;
        
        public function __construct()
        {
            require_once 'bootstrap.php';
            
            $config['base'] = $_SERVER['BASE'];
            
            $this->config = $config;
            $this->twig = $twig;
            $this->conn = $conn;
            $this->em = $em;
        }
    
        /**
         * Handle the client request.
         * 
         * @return unknown
         */
        public function handleRequest()
        {
            // sanitize the request uri
            $uri = $_SERVER['REQUEST_URI'];
            $uri = str_replace($this->config['base'], '', $uri);
            $uri = $this->trimpath($uri);
    
            $request = array();
            if( strlen($uri) > 0 ) {
              $request = explode('/', $uri);
            }
    
            $view = $this->handleRequests( $request );
            return $view;
        }
    
        /**
         * Process the response.
         * 
         * @param unknown $view
         */
        public function handleResponse( $view )
        {
            echo $view;
        }
    
        /**
         * Trim a path from leading or trailing spaces, dots, slashes and backslashes.
         */
        public function trimpath( $path )
        {
            return trim($path, '.\/ ');
        }
        
    }

The application uses the file `bootstrap.php`, which initializes the Twig and Doctrine libraries. The
Objects created within, especially the Doctrine `EntityManager`, are passed to local variables and are thus
accessable and usable by the controller. 

In  `bootstrap.php` the library namespaces are loaded with the file `autoload.php`, which was build by the Composer. Subsequently 
the application configuration gets loaded, Twig and Doctrine DBAL and ORM initialized, the Doctrie EntityManager instantiated and 
in the end the Doctrine ClassLoader called, which registers the application Entity Classes and Namespaces. The object mapping 
is done by annotations in this cas, the chapter [2. Installation and Configuration](http://doctrine-orm.readthedocs.org/en/latest/reference/configuration.html) 
in the Doctrine documentation demonstrates the other ways.

`app/bootstrap.php`

    <?php
    
    use Doctrine\Common\Cache\ArrayCache as Cache;
    
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Read application Configuration
    $config = yaml_parse_file(__DIR__ . '/../app/config/parameters.yml');
    setlocale(LC_ALL, $config['locale']);
    
    // Twig
    $loader = new Twig_Loader_Filesystem(__DIR__ . '/../views');
    $twig = new Twig_Environment($loader);
    
    // Doctrine DBAL
    $dbalconfig = new Doctrine\DBAL\Configuration();
    $conn = Doctrine\DBAL\DriverManager::getConnection($config['database'], $dbalconfig);
    
    // Doctrine ORM
    $ormconfig = new Doctrine\ORM\Configuration();
    $cache = new Cache();
    $ormconfig->setQueryCacheImpl($cache);
    $ormconfig->setProxyDir(__DIR__ . '/../model/EntityProxy');
    $ormconfig->setProxyNamespace('EntityProxy');
    $ormconfig->setAutoGenerateProxyClasses(true);
     
    // ORM mapping by Annotation
    Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
            __DIR__ . '/../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
    $driver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
        new Doctrine\Common\Annotations\AnnotationReader(),
        array(__DIR__ . '/../model/Entity')
    );
    $ormconfig->setMetadataDriverImpl($driver);
    $ormconfig->setMetadataCacheImpl($cache);
     
    // EntityManager
    $em = Doctrine\ORM\EntityManager::create($config['database'],$ormconfig);
    
    // The Doctrine Classloader
    require __DIR__ . '/../vendor/doctrine/common/lib/Doctrine/Common/ClassLoader.php';
    $classLoader = new Doctrine\Common\ClassLoader('Entity', __DIR__ . '/../model');
    $classLoader->register();


The files `index.html.twig` and `layout.html.twig` enable the view, so the basic application (without Doctrine) is ready for testing. 


`views/index.html.twig`

    {% extends "layout.html.twig" %}
    {% block content %}
    <div id="content">
        <h1>Hello Twig!</h1>
    </div>
    {% endblock %}

`views/layout.html.twig`

    <!DOCTYPE html>
    <html lang="en">
      <head>
        <meta charset="utf-8">
        <meta http-equiv="content-type" content="text/html; charset=UTF-8; IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">
        <title>{% block title %}{{ config.site.name }}{% endblock %}</title>
        <base href="{{ config.base }}">
    </head>
      <body>
        <div class="container">
    {% block content %}{% endblock %}
        </div> <!-- /container -->
      </body>
    </html>


The ORM-Console
---------------

Until now the application uses Twig "only", not the Doctrine components. Their advantages are not the database abstraction and
object mapping only, but especially the commandline console is a very powerful tool. The ORM console gets used with PHP from
the shell commandline and is able to keep the database in sync with the Entity Classes. 

The console (app/console in Symfony 2) is implemented as the script `cli.php`. This initializes the libraries using `bootstrap.php`
and then the console using the EntityManager. 

`app/cli.php`

    <?php
    
    use Symfony\Component\Console\Helper\HelperSet,
        Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper,
        Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper,
        Doctrine\ORM\Tools\Console\ConsoleRunner;
    
    require_once __DIR__ . '/bootstrap.php';
     
    $helperSet = new HelperSet(array(
        'em' => new EntityManagerHelper($em),
        'conn' => new ConnectionHelper($em->getConnection())
    ));
    
    ConsoleRunner::run($helperSet);

Run this from a shell and the help overview of the ORM-console is returned.

    php app/cli.php
    Doctrine Command Line Interface version 2.5.0
    
    Usage:
     command [options] [arguments]
    
    Options:
     --help (-h)           Display this help message
     --quiet (-q)          Do not output any message
     --verbose (-v|vv|vvv) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
     --version (-V)        Display this application version
     --ansi                Force ANSI output
     --no-ansi             Disable ANSI output
     --no-interaction (-n) Do not ask any interactive question
    
    Available commands:
     help                             Displays help for a command
     list                             Lists commands
    dbal
     dbal:import                      Import SQL file(s) directly to Database.
     dbal:run-sql                     Executes arbitrary SQL directly from the command line.
    orm
     orm:clear-cache:metadata         Clear all metadata cache of the various cache drivers.
     orm:clear-cache:query            Clear all query cache of the various cache drivers.
     orm:clear-cache:result           Clear all result cache of the various cache drivers.
     orm:convert-d1-schema            Converts Doctrine 1.X schema into a Doctrine 2.X schema.
     orm:convert-mapping              Convert mapping information between supported formats.
     orm:convert:d1-schema            Converts Doctrine 1.X schema into a Doctrine 2.X schema.
     orm:convert:mapping              Convert mapping information between supported formats.
     orm:ensure-production-settings   Verify that Doctrine is properly configured for a production environment.
     orm:generate-entities            Generate entity classes and method stubs from your mapping information.
     orm:generate-proxies             Generates proxy classes for entity classes.
     orm:generate-repositories        Generate repository classes from your mapping information.
     orm:generate:entities            Generate entity classes and method stubs from your mapping information.
     orm:generate:proxies             Generates proxy classes for entity classes.
     orm:generate:repositories        Generate repository classes from your mapping information.
     orm:info                         Show basic information about all mapped entities
     orm:mapping:describe             Display information about mapped objects
     orm:run-dql                      Executes arbitrary DQL directly from the command line.
     orm:schema-tool:create           Processes the schema and either create it directly on EntityManager Storage Connection or generate the SQL output.
     orm:schema-tool:drop             Drop the complete database schema of EntityManager Storage Connection or generate the corresponding SQL output.
     orm:schema-tool:update           Executes (or dumps) the SQL needed to update the database schema to match the current mapping metadata.
     orm:validate-schema              Validate the mapping files.


The console can be used to keep the database in sync with the model Entities. But we need to create a database first. The
access configuration can be stored in the file `~/.my.cnf`

    cat ~/.my.cnf
    [mysql]
    user = root
    password = password
    
    mysql -e "create database demodb"

The demo application is to display famous quotes. So two model Entities are required: `Author` and `Quote`. These classes are 
created in the directory `model/Entity`. The annotation reflect the database properties. 

`model/Entity/Author.php`

    <?php
    
    namespace Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    
    /**
     * Author
     *
     * @ORM\Table(name="authors")
     * @ORM\Entity
     */
    class Author
    {
        /**
         * @var integer
         *
         * @ORM\Column(name="id", type="integer")
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        private $id;
    
        /**
         * @ORM\OneToMany(targetEntity="Quote", mappedBy="author")
         */
        private $quotes;
    
        /**
         * @ORM\Column(name="name", type="string", length=255)
         */
        private $name;
    }

`model/Entity/Quote.php`

    <?php
    
    namespace Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    
    /**
     * Quote
     *
     * @ORM\Table(name="quotes")
     * @ORM\Entity
     */
    class Quote
    {
        /**
         * @var integer
         *
         * @ORM\Column(name="id", type="integer")
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        private $id;
    
        /**
         * @ORM\ManyToOne(targetEntity="Author", inversedBy="quotes")
         * @ORM\JoinColumn(name="id_acount", referencedColumnName="id", onDelete="CASCADE")
         */
        private $author;
    
        /**
         * @ORM\Column(name="text", type="string", length=255)
         */
        private $text;
    }

The required getter- and setter-methods are generated by the ORM console. 

    php app/cli.php orm:generate:entities model
    Processing entity "Entity\Author"
    Processing entity "Entity\Quote"
    
    Entity classes generated to "/mnt/Data/www/doctrine2-standalone-demo/model"

Afterwards the two files look the following:

`model/Entity/Author.php`

    <?php
    
    namespace Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    
    /**
     * Author
     *
     * @ORM\Table(name="authors")
     * @ORM\Entity
     */
    class Author
    {
        /**
         * @var integer
         *
         * @ORM\Column(name="id", type="integer")
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        private $id;
    
        /**
         * @ORM\OneToMany(targetEntity="Quote", mappedBy="author")
         */
        private $quotes;
    
        /**
         * @ORM\Column(name="name", type="string", length=255)
         */
        private $name;
        /**
         * Constructor
         */
        public function __construct()
        {
            $this->quotes = new \Doctrine\Common\Collections\ArrayCollection();
        }
    
        /**
         * Get id
         *
         * @return integer
         */
        public function getId()
        {
            return $this->id;
        }
    
        /**
         * Set name
         *
         * @param string $name
         *
         * @return Author
         */
        public function setName($name)
        {
            $this->name = $name;
    
            return $this;
        }
    
        /**
         * Get name
         *
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }
    
        /**
         * Add quote
         *
         * @param \Entity\Quote $quote
         *
         * @return Author
         */
        public function addQuote(\Entity\Quote $quote)
        {
            $this->quotes[] = $quote;
    
            return $this;
        }
    
        /**
         * Remove quote
         *
         * @param \Entity\Quote $quote
         */
        public function removeQuote(\Entity\Quote $quote)
        {
            $this->quotes->removeElement($quote);
        }
    
        /**
         * Get quotes
         *
         * @return \Doctrine\Common\Collections\Collection
         */
        public function getQuotes()
        {
            return $this->quotes;
        }
    }

`model/Entity/Quote.php`

    <?php
    
    namespace Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    
    /**
     * Quote
     *
     * @ORM\Table(name="quotes")
     * @ORM\Entity
     */
    class Quote
    {
        /**
         * @var integer
         *
         * @ORM\Column(name="id", type="integer")
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        private $id;
    
        /**
         * @ORM\ManyToOne(targetEntity="Author", inversedBy="quotes")
         * @ORM\JoinColumn(name="id_acount", referencedColumnName="id", onDelete="CASCADE")
         */
        private $author;
    
        /**
         * @ORM\Column(name="text", type="string", length=255)
         */
        private $text;
    
        /**
         * Get id
         *
         * @return integer
         */
        public function getId()
        {
            return $this->id;
        }
    
        /**
         * Set text
         *
         * @param string $text
         *
         * @return Quote
         */
        public function setText($text)
        {
            $this->text = $text;
    
            return $this;
        }
    
        /**
         * Get text
         *
         * @return string
         */
        public function getText()
        {
            return $this->text;
        }
    
        /**
         * Set author
         *
         * @param \Entity\Author $author
         *
         * @return Quote
         */
        public function setAuthor(\Entity\Author $author = null)
        {
            $this->author = $author;
    
            return $this;
        }
    
        /**
         * Get author
         *
         * @return \Entity\Author
         */
        public function getAuthor()
        {
            return $this->author;
        }
    }

Now the ORM console is able to synchronize the database:

    php app/cli.php orm:schema-tool:create --dump-sql
    CREATE TABLE authors (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
    CREATE TABLE quotes (id INT AUTO_INCREMENT NOT NULL, id_acount INT DEFAULT NULL, INDEX IDX_A1B588C517A67BCE (id_acount), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
    ALTER TABLE quotes ADD CONSTRAINT FK_A1B588C517A67BCE FOREIGN KEY (id_acount) REFERENCES authors (id) ON DELETE CASCADE;
    
    php app/cli.php orm:schema-tool:update --force
    Updating database schema...
    Database schema updated successfully! "3" queries were executed

Some data can be inserted into the database using the shell:

    mysql demodb -e "INSERT INTO authors VALUES 
    (0,'Robin Williams'),
    (0,'Lionel Richie'),
    (0,'Leonard Nimoy'),
    (0,'Bruce Willis')"
    
    mysql demodb -e "INSERT INTO quotes VALUES 
    (0,1,'Hanno Nanno!'),
    (0,2,'Hello!'),
    (0,3,'LLAP!'),
    (0,4,'Yippie-Ka-Yeah!'),
    (0,1,'Gooood Morning!')"

So now the Entities can be loaded in the controller using Doctrine ORM and passed to the view. The FrontendController and the
view now look like this: 

`app/FrontendController.php`

    <?php
    
    require_once __DIR__ . '/../app/app.php';
    
    class FrontendController extends Application
    {
        public function handleRequests( array $request )
        {
            $view = '';
            
            $viewdata = array(
                'config' => $this->config
            );
            
            $viewdata['authors'] = $this->em->getRepository('Entity\Author')->findAll();
                
            $view = $this->twig->render('index.html.twig', $viewdata);
            
            return $view;
        }
    }
    
    $app = new FrontendController();
    $view = $app->handleRequest();
    $app->handleResponse( $view );

`views/index.html.twig`

    {% extends "layout.html.twig" %}
    {% block content %}
    
    <div id="content">
        <h1>Famous Quotes</h1>
        <table cellpadding="10px">
          <thead>
            <tr>
              <th>Author</th>
              <th>Quote</th>
            </tr>
          </thead>
          {% for author in authors %}
            <tr>
              <td>{{ author.name }}</td>
              <td>
                {% for quote in author.quotes %}
                  <em>{{ quote.text }}</em><br>
                {% endfor %}
              </td>
            </tr>
          {% endfor %}
        </table>
    </div>
        
    {% endblock %}

And the result:

### Famous Quotes

    *Author*        *Quote*
    Robin Williams  Hanno Nanno!
                    Gooood Morning!
    Lionel Richie   Hello!
    Leonard Nimoy   LLAP!
    Bruce Willis  Yippie-Ka-Yeah!


Summary
-------


Once the application and the database gets more complicated - with a lot of 1:n and n:m relations - this apllication
will sooner or later lack performance. The way the database queries are made here, it is far from optimized. There are 
5 additional database queries hidden to load the actual quotes, but the way it is done here demonstrates the flexibility 
of the object relation mapper. Also security is an issue with the routes, the requests, responses and occasionally form
inputs. This is already handeled in frameworks like Symfony 2. 

Apart from that it's a blast working with these modules in this rather stripped-down way. 
