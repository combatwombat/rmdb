<?php


$app = require __DIR__ . '/../lib/RTF.php';

$app->container->set('config', '\RTF\Config');
$app->container->set('helper', '\RTF\Helper');

$app->container->set("db", function($container) {
    return new \RTF\DB($container->config('db/db'), $container->config('db/user'), $container->config('db/pass'), $container->config('db/host'), $container->config('db/charset'));
});


$app->cli('download', 'IMDbImport@download');
$app->cli('extract', 'IMDbImport@extract');
$app->cli('import', 'IMDbImport@import');


$app->cli('test', function() {
    print_r("db: " . $this->config("db/db") . "\n");
});

$app->run();


/*
$app->container->set("auth", '\RTF\Auth');


// simple closure
$app->get("/", function() {

    //$this->auth();

    //print_r($this->db->get('timeslips', 1));

    //$res = $this->auth->hasAccess();



    ?>


    <form action="/login" method="post">
        <input type="text" name="user"><br>
        <input type="text" name="pass"><br>
        <input type="submit" value="Login">
    </form>

    <a href="/logout">Logout</a>

<?php


});


$app->get('/logout', function() {
    $this->auth->logout();
});

$app->post('/login', function() {
   $res = $this->auth->login($_POST['user'], $_POST['pass']);
   echo "logged in: ";
   var_dump($res);
});

// controller with action
$app->get("/customers", 'CustomersController@list');

// or one class per action
$app->get("/profile", 'ProfileAction');

*/


