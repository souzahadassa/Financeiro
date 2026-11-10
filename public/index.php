<?php
// public/index.php
require dirname(__DIR__) . '/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$json = function (Response $r, $data, int $status = 200): Response {
    $r->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
    return $r->withHeader('Content-Type', 'application/json')->withStatus($status);
};

$categorias = [
    1 => ['id' => 1, 'nome' => 'Salário',     'tipo' => 'receita'],
    2 => ['id' => 2, 'nome' => 'Alimentação', 'tipo' => 'despesa'],
];
$lancamentos = [
    1 => ['id' => 1, 'descricao' => 'Salário maio', 'valor' => 5000.00, 'data' => '2025-05-01', 'tipo' => 'receita', 'categoria_id' => 1],
    2 => ['id' => 2, 'descricao' => 'Mercado',      'valor' => 350.00,  'data' => '2025-05-03', 'tipo' => 'despesa', 'categoria_id' => 2],
];
$proxCat = 3;
$proxLan = 3;

$app->get('/status', fn (Request $req, Response $res) => $json($res, ['status' => 'ok']));

$app->post('/categorias', function (Request $req, Response $res)
    use (&$categorias, &$proxCat, $json) {
    $d = (array) $req->getParsedBody();
    $nome = trim($d['nome'] ?? '');
    $tipo = $d['tipo'] ?? '';
    $erros = [];

    if ($nome === '')                                  $erros[] = 'nome é obrigatório';
    if (!in_array($tipo, ['receita', 'despesa'], true)) $erros[] = 'tipo deve ser receita ou despesa';

    foreach ($categorias as $c) {
        if (mb_strtolower($c['nome']) === mb_strtolower($nome)) {
            $erros[] = 'já existe categoria com esse nome';
            break;
        }
    }
    if ($erros) return $json($res, ['erros' => $erros], 422);

    $id = $proxCat++;
    $categorias[$id] = ['id' => $id, 'nome' => $nome, 'tipo' => $tipo];
    return $json($res, $categorias[$id], 201);
});

$app->get('/categorias', fn (Request $req, Response $res)
    => $json($res, array_values($categorias)));


$app->get('/categorias/{id}', function (Request $req, Response $res, array $args)
    use ($categorias, $json) {
    $id = (int) $args['id'];
    if (!isset($categorias[$id]))
        return $json($res, ['erro' => 'Categoria não encontrada'], 404);
    return $json($res, $categorias[$id]);
});

$app->put('/categorias/{id}', function (Request $req, Response $res, array $args)
    use (&$categorias, $json) {
    $id = (int) $args['id'];
    if (!isset($categorias[$id]))
        return $json($res, ['erro' => 'Categoria não encontrada'], 404);

    $d = (array) $req->getParsedBody();
    $nome = trim($d['nome'] ?? $categorias[$id]['nome']);
    $tipo = $d['tipo'] ?? $categorias[$id]['tipo'];
    $erros = [];

    if ($nome === '')                                   $erros[] = 'nome é obrigatório';
    if (!in_array($tipo, ['receita', 'despesa'], true)) $erros[] = 'tipo inválido';
    foreach ($categorias as $cid => $c) {
        if ($cid !== $id && mb_strtolower($c['nome']) === mb_strtolower($nome))
            $erros[] = 'já existe categoria com esse nome';
    }
    if ($erros) return $json($res, ['erros' => $erros], 422);

    $categorias[$id] = ['id' => $id, 'nome' => $nome, 'tipo' => $tipo];
    return $json($res, $categorias[$id]);
});

$app->delete('/categorias/{id}', function (Request $req, Response $res, array $args)
    use (&$categorias, $lancamentos, $json) {
    $id = (int) $args['id'];
    if (!isset($categorias[$id]))
        return $json($res, ['erro' => 'Categoria não encontrada'], 404);

    foreach ($lancamentos as $l) {
        if ($l['categoria_id'] === $id)
            return $json($res, ['erro' => 'Categoria possui lançamentos vinculados'], 422);
    }
    unset($categorias[$id]);
    return $res->withStatus(204);
});

$validarLancamento = function (array $d, array $categorias): array {
    $erros = [];
    if (empty($d['descricao']))                          $erros[] = 'descrição é obrigatória';
    if (!isset($d['valor']) || (float)$d['valor'] <= 0)  $erros[] = 'valor deve ser maior que zero';
    if (empty($d['data']) || !strtotime($d['data']))     $erros[] = 'data inválida';
    if (!in_array($d['tipo'] ?? '', ['receita','despesa'], true)) $erros[] = 'tipo inválido';

    $catId = (int) ($d['categoria_id'] ?? 0);
    if (!isset($categorias[$catId])) {
        $erros[] = 'categoria inexistente';
    } elseif ($categorias[$catId]['tipo'] !== ($d['tipo'] ?? '')) {
        $erros[] = 'tipo do lançamento incompatível com a categoria';
    }
    return $erros;
};

// RF06 – Cadastrar
$app->post('/lancamentos', function (Request $req, Response $res)
    use (&$lancamentos, &$proxLan, $categorias, $validarLancamento, $json) {
    $d = (array) $req->getParsedBody();
    if ($erros = $validarLancamento($d, $categorias))
        return $json($res, ['erros' => $erros], 422);

    $id = $proxLan++;
    $lancamentos[$id] = [
        'id'           => $id,
        'descricao'    => $d['descricao'],
        'valor'        => (float) $d['valor'],
        'data'         => $d['data'],
        'tipo'         => $d['tipo'],
        'categoria_id' => (int) $d['categoria_id'],
    ];
    return $json($res, $lancamentos[$id], 201);
});

$app->get('/lancamentos', function (Request $req, Response $res)
    use ($lancamentos, $json) {
    $q = $req->getQueryParams();
    $lista = array_values($lancamentos);

    if (!empty($q['data_inicial'])) $lista = array_filter($lista, fn($l) => $l['data'] >= $q['data_inicial']);
    if (!empty($q['data_final']))   $lista = array_filter($lista, fn($l) => $l['data'] <= $q['data_final']);
    if (!empty($q['categoria_id'])) $lista = array_filter($lista, fn($l) => $l['categoria_id'] === (int)$q['categoria_id']);
    if (!empty($q['tipo']))         $lista = array_filter($lista, fn($l) => $l['tipo'] === $q['tipo']);

    return $json($res, array_values($lista));
});

// RF08 – Consultar por id (corrigido)
$app->get('/lancamentos/{id}', function (Request $req, Response $res, array $args)
    use ($lancamentos, $json) {
    $id = (int) $args['id'];
    if (!isset($lancamentos[$id]))
        return $json($res, ['erro' => 'Lançamento não encontrado'], 404);
    return $json($res, $lancamentos[$id]);
});

$app->put('/lancamentos/{id}', function (Request $req, Response $res, array $args)
    use (&$lancamentos, $categorias, $validarLancamento, $json) {
    $id = (int) $args['id'];
    if (!isset($lancamentos[$id]))
        return $json($res, ['erro' => 'Lançamento não encontrado'], 404);

    $d = array_merge($lancamentos[$id], (array) $req->getParsedBody());
    if ($erros = $validarLancamento($d, $categorias))
        return $json($res, ['erros' => $erros], 422);

    $lancamentos[$id] = [
        'id'           => $id,
        'descricao'    => $d['descricao'],
        'valor'        => (float) $d['valor'],
        'data'         => $d['data'],
        'tipo'         => $d['tipo'],
        'categoria_id' => (int) $d['categoria_id'],
    ];
    return $json($res, $lancamentos[$id]);
});

$app->delete('/lancamentos/{id}', function (Request $req, Response $res, array $args)
    use (&$lancamentos, $json) {
    $id = (int) $args['id'];
    if (!isset($lancamentos[$id]))
        return $json($res, ['erro' => 'Lançamento não encontrado'], 404);
    unset($lancamentos[$id]);
    return $res->withStatus(204);
});

$app->get('/resumo/saldo', function (Request $req, Response $res)
    use ($lancamentos, $json) {
    $receitas = 0.0; $despesas = 0.0;
    foreach ($lancamentos as $l) {
        if ($l['tipo'] === 'receita') $receitas += $l['valor'];
        else                          $despesas += $l['valor'];
    }
    return $json($res, [
        'total_receitas' => $receitas,
        'total_despesas' => $despesas,
        'saldo'          => $receitas - $despesas,
    ]);
});

$app->get('/resumo/periodo', function (Request $req, Response $res)
    use ($lancamentos, $json) {
    $q = $req->getQueryParams();
    if (empty($q['data_inicial']) || empty($q['data_final']))
        return $json($res, ['erros' => ['data_inicial e data_final são obrigatórias']], 422);

    $receitas = 0.0; $despesas = 0.0;
    foreach ($lancamentos as $l) {
        if ($l['data'] < $q['data_inicial'] || $l['data'] > $q['data_final']) continue;
        if ($l['tipo'] === 'receita') $receitas += $l['valor'];
        else                          $despesas += $l['valor'];
    }
    return $json($res, [
        'periodo'        => ['inicio' => $q['data_inicial'], 'fim' => $q['data_final']],
        'total_receitas' => $receitas,
        'total_despesas' => $despesas,
    ]);
});

$app->run();
