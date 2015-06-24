# nestedset-php

Using Nested Sets Pattern to Model Tree Structure in PHP

### Installation

To install the SDK, you will need to be using [Composer](http://getcomposer.org/) 
in your project. 
If you aren't using Composer yet, it's really simple! Here's how to install 
composer and nestedset-php.

```PHP
# Install Composer
curl -sS https://getcomposer.org/installer | php

# Add nestedset-php as a dependency
php composer.phar require ben-nsng/nestedset-php:*
``` 

### Usage

```php

$pdo = new PDO('mysql:host=localhost;dbname=tree', 'tree', 'tree');
$treeModel = new NestedSet($pdo);
$treeModel->changeTable('tree'); // default table is tree
```

### API

#### Instance methods:

```php

// init table for first creating table
$treeModel->addRoot();

// add new node to root node, return node id
$treeModel->addNode($label);

// add new node into parent node return node id
$treeModel->addNode($label, $parent_id);

// return database statement object
$nodes = $treeModel->selectAll();
// array of nodes (stdclass)
$nodes->result();

// move existing node into parent node
$treeModel->addChild($node_id, $parent_id);

// move existing node before next node
$treeModel->addBefore($node_id, $next_id);

// move existing node after last node
$treeModel->addBefore($node_id, $last_id);

// delete existing node, including nodes inside node
$treeModel->deleteNode($node_id)
```

### Related Links/Resources

* Inspired by [MySQL stored procedures to manage hierarchical trees in a MySQL database] (http://moinne.com/blog/ronald/mysql/manage-hierarchical-data-with-mysql-stored-procedures)
* [Nested Set Model] (http://en.wikipedia.org/wiki/Nested_set_model)
* [Managing Hierarchical Data in MySQL] (http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/)
* [Storing Hierarchical Data in a Database] (http://www.sitepoint.com/hierarchical-data-database/)

### Authors

* Ben Ng: [https://github.com/ben-nsng](https://github.com/ben-nsng)
