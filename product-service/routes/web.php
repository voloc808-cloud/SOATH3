<?php
$router->get('/', function () { return response()->json(['service'=>'product-service']); });
$router->group(['middleware'=>'auth.remote'], function () use ($router) {
 $router->get('/products', 'ProductController@index');
 $router->get('/products/{id:[0-9]+}', 'ProductController@show');
 $router->post('/products', 'ProductController@store');
 $router->put('/products/{id:[0-9]+}', 'ProductController@update');
 $router->delete('/products/{id:[0-9]+}', 'ProductController@destroy');
});
