<?php

$router = new Router(function(Router $router) {
	
});
$router
->get('home', function(){
	return 'Homepage';
})
->context(['prefix' => 'account'], function() use ($router) {
	$router
		->get('login/{username}', function($userName){
			return "Login for $userName";
		})
		->post('login/{username', function($userName){

		})
		->context(['prefix' => 'oauth'], function() use ($router) {

		});
});