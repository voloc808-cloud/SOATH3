<?php
namespace Tests;

use Illuminate\Http\Client\Factory;

class ProductTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        $this->artisan('migrate');
        config(['services.auth.verify_url' => 'http://auth.test/user']);
        $client = new Factory();
        $client->fake(['auth.test/*' => Factory::response(['id' => 1], 200)]);
        $this->app->instance(Factory::class, $client);
    }

    public function testCrudAndValidation(): void
    {
        $headers = ['Authorization' => 'Bearer test-token'];
        $data = ['name' => 'Keyboard', 'description' => 'USB', 'price' => '120.50', 'quantity' => 4];
        $this->json('POST', '/products', $data, $headers)->seeStatusCode(201)->seeJson(['price' => '120.50']);
        $id = json_decode($this->response->getContent(), true)['data']['id'];
        $this->json('GET', '/products', [], $headers)->seeStatusCode(200)->seeJson(['name' => 'Keyboard']);
        $this->json('GET', '/products/'.$id, [], $headers)->seeStatusCode(200);
        $data['quantity'] = 9;
        $this->json('PUT', '/products/'.$id, $data, $headers)->seeStatusCode(200)->seeJson(['quantity' => 9]);
        $data['quantity'] = -1;
        $this->json('POST', '/products', $data, $headers)->seeStatusCode(422);
        $this->json('DELETE', '/products/'.$id, [], $headers)->seeStatusCode(204);
        $this->json('GET', '/products/'.$id, [], $headers)->seeStatusCode(404);
    }

    public function testMissingAndRejectedToken(): void
    {
        $this->json('GET', '/products')->seeStatusCode(401);
        $client = new Factory();
        $client->fake(['auth.test/*' => Factory::response([], 401)]);
        $this->app->instance(Factory::class, $client);
        $this->json('GET', '/products', [], ['Authorization' => 'Bearer invalid'])->seeStatusCode(401);
    }

    public function testAuthFailureClosesAccess(): void
    {
        $client = new Factory();
        $client->fake(['auth.test/*' => Factory::response([], 500)]);
        $this->app->instance(Factory::class, $client);
        $this->json('GET', '/products', [], ['Authorization' => 'Bearer token'])->seeStatusCode(503);
    }
}
