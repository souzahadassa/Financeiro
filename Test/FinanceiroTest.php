<?php

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class FinanceiroTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        
        $this->app = require __DIR__ . '/../src/app.php';
    }

    private function criarRequest(string $method, string $path, array $body = [], array $query = []): \Psr\Http\Message\ServerRequestInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $path);
        
        if (!empty($body)) {
            $request = $request->withHeader('Content-Type', 'application/json');
            $request->getBody()->write(json_encode($body));
            $request->getBody()->rewind();
        }
        
        if (!empty($query)) {
            $request = $request->withQueryParams($query);
        }

        return $request;
    }

    public function testStatusRetornaOk(): void
    {
        $request = $this->criarRequest('GET', '/status');
        $response = $this->app->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $corpo = json_decode((string) $response->getBody(), true);
        $this->assertEquals('ok', $corpo['status']);
    }

    public function testCriarCategoriaComSucesso(): void
    {
        $request = $this->criarRequest('POST', '/categorias', [
            'nome' => 'Investimentos',
            'tipo' => 'receita'
        ]);
        
        $response = $this->app->handle($request);
        $this->assertEquals(201, $response->getStatusCode());
        
        $corpo = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Investimentos', $corpo['nome']);
        $this->assertEquals(3, $corpo['id']); 
    }

    public function testCriarCategoriaDuplicadaRetornaErro(): void
    {
        
        $request = $this->criarRequest('POST', '/categorias', [
            'nome' => 'Salário',
            'tipo' => 'receita'
        ]);
        
        $response = $this->app->handle($request);
        $this->assertEquals(422, $response->getStatusCode());
        
        $corpo = json_decode((string) $response->getBody(), true);
        $this->assertContains('já existe categoria com esse nome', $corpo['erros']);
    }

  
    public function testLancamentoComTipoIncompativelRetornaErro(): void
    {
       
        $request = $this->criarRequest('POST', '/lancamentos', [
            'descricao' => 'Compra errada',
            'valor' => 100,
            'data' => '2025-05-10',
            'tipo' => 'despesa',
            'categoria_id' => 1
        ]);
        
        $response = $this->app->handle($request);
        $this->assertEquals(422, $response->getStatusCode());
        
        $corpo = json_decode((string) $response->getBody(), true);
        $this->assertContains('tipo do lançamento incompatível com a categoria', $corpo['erros']);
    }


    public function testCriarLancamentoComSucesso(): void
    {
        $request = $this->criarRequest('POST', '/lancamentos', [
            'descricao' => 'Freelance',
            'valor' => 1500.50,
            'data' => '2025-05-15',
            'tipo' => 'receita',
            'categoria_id' => 1 
        ]);
        
        $response = $this->app->handle($request);
        $this->assertEquals(201, $response->getStatusCode());
        
        $corpo = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Freelance', $corpo['descricao']);
        $this->assertEquals(1500.50, $corpo['valor']);
    }

   
    public function testFiltrarLancamentosPorTipo(): void
    {
      
        $request = $this->criarRequest('GET', '/lancamentos', [], ['tipo' => 'despesa']);
        $response = $this->app->handle($request);
        
        $corpo = json_decode((string) $response->getBody(), true);
        
        $this->assertCount(1, $corpo); // Só deve retornar o "Mercado"
        $this->assertEquals('Mercado', $corpo[0]['descricao']);
    }


    public function testResumoSaldo(): void
    {
        $request = $this->criarRequest('GET', '/resumo/saldo');
        $response = $this->app->handle($request);
        
        $corpo = json_decode((string) $response->getBody(), true);
        
    
        $this->assertEquals(5000.00, $corpo['total_receitas']);
        $this->assertEquals(350.00, $corpo['total_despesas']);
        $this->assertEquals(4650.00, $corpo['saldo']);
    }

    public function testDeletarCategoriaComLancamentoRetornaErro(): void
    {
    
        $request = $this->criarRequest('DELETE', '/categorias/2');
        $response = $this->app->handle($request);
        
        $this->assertEquals(422, $response->getStatusCode());
        
        $corpo = json_decode((string) $response->getBody(), true);
        $this->assertEquals('Categoria possui lançamentos vinculados', $corpo['erro']);
    }
}