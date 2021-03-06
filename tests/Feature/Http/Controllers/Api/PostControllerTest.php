<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Post;
use App\Models\User;

class PostControllerTest extends TestCase
{
    use RefreshDatabase; //Como se va a construir datos y se buscara en la base de datos, use esta clase
    /**
     * Test for the store method on PostController
     *
     * @return void
     */
    public function test_store()
    {
        /**
         * Como el último test modifico el acceso sin credenciales a todas la rutas
         * debo crear un usuario para poder probar correctamente todas las demas pruebas
         * y modificar el acceso a la ruta para que el logueo sea mediante token 
         * pasando el parametro api al metodo actingAs
         */
        $user = User::factory()->create();
        /**
        *Estamos simulando que cualquier aplicación que se conecte a la ruta indicada e intente hacer un POST
        *para guardar los datos dentro del array para comprobar que se guardan correctamente */
        $response = $this->actingAs($user, 'api')->json('POST', '/api/posts', [
            'title' => 'El post de prueba'
        ]);

        /**
         * La primera confirmación (assert) será que nuestro sistema retorne, en una estructura Json,
         * los datos dentro del array
         * 
         * La segunda comprobación será comprobar que existe el objeto 'title'
         * 
         * La tercera comprobación será la respuesta HTTP que esperamos obtener cuando se guarde correctamente
         */
        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
                ->assertJson(['title' => 'El post de prueba'])
                ->assertStatus(201);
        /**
         * Confirmamos que en efecto existe el dato publicado en la base de datos
         */
        $this->assertDatabaseHas('posts', ['title' => 'El post de prueba']);

    }

    public function test_validate_title()
    {
        /**
         * Como el último test modifico el acceso sin credenciales a todas la rutas
         * debo crear un usuario para poder probar correctamente todas las demas pruebas
         * y modificar el acceso a la ruta para que el logueo sea mediante token 
         * pasando el parametro api al metodo actingAs
         */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->json('POST', '/api/posts', [
            'title' => ''
        ]);
        /**
         * Estamos intentando guardar un post sin titulo por lo que esperamos el error 422
         * y comprobar que estamos recibiendo un Json que especifica que el titulo no está incluido
         */
        $response->assertStatus(422) //Significa que la solciitud está bien hecha pero fue imposible completarla
                ->assertJsonValidationErrors('title');
    }

    public function test_show()
    {
        /**
         * Como el último test modifico el acceso sin credenciales a todas la rutas
         * debo crear un usuario para poder probar correctamente todas las demas pruebas
         * y modificar el acceso a la ruta para que el logueo sea mediante token 
         * pasando el parametro api al metodo actingAs
         */
        $user = User::factory()->create();

        $post = Post::factory()->create();//Utilizaremos el factory para crear un post de prueba

        $response = $this->actingAs($user, 'api')->json('GET', "/api/posts/$post->id");//Intentaremos acceder a dicho post de prueba

        /**
         * Cuando se acceda al post de prueba quiero verificar que estoy obteniendo el id, titulo y el resto de columnas
         * y que ademas el titulo debe coincidir con el post que se creo
         */
        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
                ->assertJson(['title' => $post->title])
                ->assertStatus(200);
    }
    /**
     * Test for 404 response on non-existing post
     * 
     * @return void 
     */
    public function test_404_show()
    {
        /**
         * Como el último test modifico el acceso sin credenciales a todas la rutas
         * debo crear un usuario para poder probar correctamente todas las demas pruebas
         * y modificar el acceso a la ruta para que el logueo sea mediante token 
         * pasando el parametro api al metodo actingAs
         */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->json('GET', "/api/posts/1000");

        $response->assertStatus(404);
    }
    /**
     * Test post creation and then post updating
     */
    public function test_update()
    {
        /**
         * Como el último test modifico el acceso sin credenciales a todas la rutas
         * debo crear un usuario para poder probar correctamente todas las demas pruebas
         * y modificar el acceso a la ruta para que el logueo sea mediante token 
         * pasando el parametro api al metodo actingAs
         */
        $user = User::factory()->create();
        $post = Post::factory()->create(); //Se crea un post aleatorio

        $response = $this->actingAs($user, 'api')->json('PUT', "/api/posts/$post->id", [
            'title' => 'nuevo'
        ]);//Actualizamos el post creado con el metodo PUT y le damos un nuevo título

        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])//Comprobamos existencia de id, título, fechas
                ->assertJson(['title' => 'nuevo'])//que en realidad tenga el nuevo title
                ->assertStatus(200); //OK
        
        $this->assertDatabaseHas('posts', ['title' => 'nuevo']);//Comprobamos existencia en la DB

    }
    /**
     * Test post deletion
     * 
     * @return void
     */
    public function test_delete()
    {
        /**
         * Como el último test modifico el acceso sin credenciales a todas la rutas
         * debo crear un usuario para poder probar correctamente todas las demas pruebas
         * y modificar el acceso a la ruta para que el logueo sea mediante token 
         * pasando el parametro api al metodo actingAs
         */
        $user = User::factory()->create();
        $post = Post::factory()->create(); //Se crea un post aleatorio

        $response = $this->actingAs($user, 'api')->json('DELETE', "/api/posts/$post->id");//borramos post creado

        $response->assertSee(null)//Que haya inexistencia en la respuesta
                ->assertStatus(204); //Sin contenido
        
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);//Comprobamos inexistencia en la DB

    }

    /**
     * Test index feature
     * 
     * @return void
     */
    public function test_index()
    {
        /**
         * Como el último test modifico el acceso sin credenciales a todas la rutas
         * debo crear un usuario para poder probar correctamente todas las demas pruebas
         * y modificar el acceso a la ruta para que el logueo sea mediante token 
         * pasando el parametro api al metodo actingAs
         */
        $user = User::factory()->create();
        $post = Post::factory(5)->create(); //Se crean 5 posts aleatorio

        $response = $this->actingAs($user, 'api')->json('GET', '/api/posts/');//Accedemos a la ruta donde se sirven los posts creados

        $response->assertJsonStructure([//Verificamos que obtenemos una estructura JSON
            'data' => [
                '*' => ['id', 'title', 'created_at', 'updated_at']
            ]//Dicha estructura debe contener muchos datos (*) con id, title, created_at y updated_at
        ])->assertStatus(200);
    }
    /**
     * Test credentials on a guest
     */
    public function test_guest()
    {
        $this->json('GET', '/api/posts/')->assertStatus(401);//code status 401 = no autorizado
        $this->json('POST', '/api/posts/')->assertStatus(401);//code status 401 = no autorizado
        $this->json('GET', '/api/posts/1000')->assertStatus(401);//code status 401 = no autorizado
        $this->json('PUT', '/api/posts/1000')->assertStatus(401);//code status 401 = no autorizado
        $this->json('DELETE', '/api/posts/1000')->assertStatus(401);//code status 401 = no autorizado
    }
}
