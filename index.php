<?php 
ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1); 
require 'vendor/autoload.php'; 
require_once("conekta-php/lib/Conekta.php");

Twig_Autoloader::register();  
$app = new \Slim\Slim();  
$loader = new Twig_Loader_Filesystem('templates');  
$twig = new Twig_Environment($loader, array(  /*'cache' => 'cache',*/ ));  
//BASE URL
//define('BASE_URL', 'http://localhost:8080/oce/');  //Sobreescribir por la ruta de su proyecto.
define('BASE_URL', 'https://operadoracaribeensueno.com/');

\Stripe\Stripe::setApiKey("sk_test_P1oGgmJYwlSXyJHk8r4KddzX");
\Conekta\Conekta::setApiKey("key_vgQnyx6HJ8Rt6aLzidrEUA");
\Conekta\Conekta::setApiVersion("2.0.0");


$data=array(
	'BASE_URL' => constant('BASE_URL'),
);
$app->get('/', function() use ($twig,$data) { 
	$data["my_title"] = "home";
    $cars= require_once('content/cars.php');
    $data['car_groups'] = $cars;
    //$data['car_groups'] = array_chunk($cars, 3);
    echo $twig->render('home.html',$data);  
});

$app->get('/nosotros', function() use ($twig,$data){  
    $data["my_title"] = "about";
    echo $twig->render('nosotros.html',$data);  
});

$app->get('/autos', function() use ($twig,$data){  
    $data["my_title"] = "about";
    $cars= require_once('content/cars.php');
    $data['car_groups'] = $cars;
    echo $twig->render('autos.html',$data);  
});

$app->get('/destinos', function() use ($twig,$data){  
    $data["my_title"] = "about";
    $data['destinos'] = require_once('content/destinos.php');
    
    echo $twig->render('destinos.html',$data);  
});

$app->get('/contacto', function() use ($twig,$data){  
    $data["my_title"] = "about";
    echo $twig->render('contacto.html',$data);  
});

$app->get('/pago', function() use ($twig,$data){  
    $slug = $_GET['slug'];
    $car_groups = require_once('content/cars.php');
    foreach ($car_groups as $car_group) {
        foreach ($car_group['cars'] as $key => $car) {
            if($key === $slug){
                $data["car"] = $car;    
            }
            
        }
    }


    echo $twig->render('pago.html',$data);  
});

$app->post('/bancomer', function(){
    
    
    $codigo_com = "4132355";
    $moneda = "484";
    $tipo_operacion = "0";
    $clave_secreta = "4op1er3ad2or3ac5ar5i";
    $data_string = $_POST['Ds_Merchant_Amount'].$_POST['Ds_Merchant_Order'].$codigo_com.$moneda.$tipo_operacion.$clave_secreta;
    echo sha1($data_string);


});

$app->post('/stripe', function(){
    try {
        $token = $_POST['token'];
        $amount = $_POST['amount'];
        $email = $_POST['email'];
        $description = $_POST['description'];
        $referencia = $_POST['referencia'];
        $charge = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => 'mxn',
            'source' => $token,
            'receipt_email' => $email,
            'description' => $description,
            'metadata' => ['order_id' => $referencia],
        ]);

    echo json_encode($charge);
    // Use Stripe's library to make requests...
    } catch (Exception $e) {
        $body = $e->getJsonBody();
        echo json_encode($body);

      // Something else happened, completely unrelated to Stripe
    }

});
$app->post('/conekta', function(){
    try {
        // $token = "tok_test_mastercard_4444";
        // $amount = 1000;
        // $name = "Jon";
        // $email = "jon@iddeasmkt.com";
        // $description = "desc";
        // $referencia = 0000;

        $token = $_POST['token'];
        $amount = $_POST['amount'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $description = $_POST['description'];
        $referencia = $_POST['referencia'];

        $customer = \Conekta\Customer::create(
        array(
          "name" => $name,
          "email" => $email,
          "payment_sources" => array(
            array(
                "type" => "card",
                "token_id" => $token
            )
          )//payment_sources
        )//customer
        );
        
        try{
        $order = \Conekta\Order::create(
        array(
          "line_items" => array(
            array(
              "name" => $description,
              "unit_price" => $amount,
              "quantity" => 1
            )//first line_item
          ), //line_items
          "currency" => "MXN",
          "customer_info" => array(
            "customer_id" => $customer->id
          ), //customer_info
          "metadata" => array("reference" => $referencia),
          "charges" => array(
              array(
                  "payment_method" => array(
                          "type" => "default"
                  ) //payment_method - use customer's default - a card
                    //to charge a card, different from the default,
                    //you can indicate the card's source_id as shown in the Retry Card Section
              ) //first charge
          ) //charges
        )//order
        );
        echo $order;

        } catch (\Conekta\ProcessingError $error){
        echo json_encode($error->getMessage());
        } catch (\Conekta\ParameterValidationError $error){
        echo json_encode($error->getMessage());
        } catch (\Conekta\Handler $error){
        echo json_encode($error->getMessage());
        }
        

    }
    catch (\Conekta\ProcessingError $error){
      echo json_encode($error->getMessage());
    } catch (\Conekta\ParameterValidationError $error){
      echo json_encode($error->getMessage());
    } catch (\Conekta\Handler $error){
      echo json_encode($error->getMessage());
    }
    catch (Exception $e) {
        echo json_encode($e->getMessage);
    }

    //echo json_encode($customer);
    

});


$app->post('/form', function(){ 

    $mail             = new PHPMailer(); // defaults to using php "mail()"

    $body             = 'Nuevo contacto via web <br><br> Nombre: '.$_POST['nombre'].'<br> Nacionalidad: '.$_POST['nacionalidad'].'<br> Email: '.$_POST['email'].'<br> Telefono:'.$_POST['telefono'].' <br> Mensaje:'.$_POST['textarea'];

    $mail->AddReplyTo("website@operadoracaribeensueno.com","operadoracaribeensueno");

    $mail->SetFrom('website@operadoracaribeensueno.com', 'operadoracaribeensueno');


    $address = "operaciones@operadoracaribeensueno.com";
    $mail->AddAddress($address, "Operaciones");

    $mail->Subject    = "Nuevo contacto via web";

    $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

    $mail->MsgHTML($body);

    if(!$mail->Send()) {
      echo json_encode("Error");
    } else {
      echo json_encode("Success");
    }
    


});



    
    
$app->run();  