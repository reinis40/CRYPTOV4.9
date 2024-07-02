<?php

require 'vendor/autoload.php';
require 'api/ApiInterface.php';
require 'api/CoinMarketCapApi.php';
require 'api/CoingeckoApi.php';
require 'storage/TransactionLogger.php';
require 'CryptoManager.php';
require 'User.php';
require 'CryptoController.php';

use App\Controller\CryptoController;
use App\Service\CryptoManager;
use FastRoute\RouteCollector;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$loader = new FilesystemLoader(__DIR__ . '/views');
$twig = new Environment($loader, [
      'cache' => false,
]);

$dbFile = 'storage/database.sqlite';
$pdo = new PDO('sqlite:' . $dbFile);
$user = new User($pdo);
$user->id = 1;
$logger = new TransactionLogger($dbFile);
$api = new CoinMarketCapApi("ccb58a8c-61b0-4c84-8289-5e562a8476a1");

$cryptoManager = new CryptoManager($api, $pdo, $user, $logger);

$cryptoController = new CryptoController( $cryptoManager,$twig);

$dispatcher = FastRoute\simpleDispatcher(function(RouteCollector $r) {
    $r->addRoute('GET', '/top', [CryptoController::class, 'getTopCryptos']);
    $r->addRoute('GET', '/search', [CryptoController::class, 'searchCryptos']);
    $r->addRoute('GET', '/buy', [CryptoController::class, 'buyCrypto']);
    $r->addRoute('GET', '/sell', [CryptoController::class, 'sellCrypto']);
    $r->addRoute('GET', '/wallet', [CryptoController::class, 'showWallet']);
    $r->addRoute('GET', '/transactions', [CryptoController::class, 'showTransactions']);
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo "404 Not Found";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo "405 Method Not Allowed";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        [$controller, $method] = $handler;
        echo $cryptoController->$method();
        break;
}
//echo "Login:\n";
//$username = readline("Username: ");
//$password = readline("Password: ");
//if (!$user->login($username, $password)) {
//    exit("Invalid username or password.\n");
//}
//while (true) {
//    echo "\n1. List of crypto\n2. Buy\n3. Sell\n4. View wallet\n5. View logs\n6. Exit\n";
//    $input = readline("Select an option: ");
//
//    switch ($input) {
//        case 1:
//            $cryptoManager->showCrypto();
//            break;
//        case 2:
//            $symbol = readline("Enter cryptocurrency symbol: ");
//            $amountEUR = readline("Enter amount in EUR to buy: ");
//            $cryptoManager->buyCrypto(strtoupper($symbol), (float)$amountEUR);
//            break;
//        case 3:
//            $symbol = readline("Enter cryptocurrency symbol: ");
//            $cryptoManager->sellCrypto(strtoupper($symbol));
//            break;
//        case 4:
//            $cryptoManager->showWallet();
//            break;
//        case 5:
//            $logger->showTransactions();
//            break;
//        case 6:
//            exit("Goodbye!\n");
//        default:
//            echo "Invalid option, please try again.\n";
//    }
//}




