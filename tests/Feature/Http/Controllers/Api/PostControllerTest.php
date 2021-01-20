<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Post;

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
        *Estamos simulando que cualquier aplicación que se conecte a la ruta indicada e intente hacer un POST
        *para guardar los datos dentro del array para comprobar que se guardan correctamente */
        $response = $this->json('POST', '/api/posts', [
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
        $response = $this->json('POST', '/api/posts', [
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
        $post = Post::factory()->create();//Utilizaremos el factory para crear un post de prueba

        $response = $this->json('GET', "/api/posts/$post->id");//Intentaremos acceder a dicho post de prueba

        /**
         * Cuando se acceda al post de prueba quiero verificar que estoy obteniendo el id, titulo y el resto de columnas
         * y que ademas el titulo debe coincidir con el post que se creo
         */
        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
                ->assertJson(['title' => $post->title])
                ->assertStatus(201);
    }
}
