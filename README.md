# 💰 Web Service de Controle Financeiro Pessoal

API REST desenvolvida em **PHP + Slim Framework 4** para gerenciar receitas, despesas, categorias e acompanhar o saldo financeiro pessoal.

---

## 📋 Sumário

- [Sobre o projeto](#-sobre-o-projeto)
- [Tecnologias](#-tecnologias)
- [Estrutura do projeto](#-estrutura-do-projeto)
- [Instalação](#-instalação)
- [Executando a API](#-executando-a-api)
- [Executando os testes](#-executando-os-testes)
- [Endpoints](#-endpoints)
- [Regras de negócio](#-regras-de-negócio)
- [Códigos de status](#-códigos-de-status-http)
- [Exemplos de uso](#-exemplos-de-uso)

---

## 🎯 Sobre o projeto

Este web service permite que qualquer aplicação (web ou mobile) realize as operações básicas de um sistema de controle financeiro pessoal:

- Cadastrar, listar, consultar, atualizar e excluir **categorias** (receita ou despesa)
- Cadastrar, listar, consultar, atualizar e excluir **lançamentos**
- Filtrar lançamentos por **período**, **categoria** e **tipo**
- Consultar o **saldo atual** e os **totais por período**

---

## 🛠 Tecnologias

- **PHP 8.1+**
- **Slim Framework 4** — microframework REST
- **PHPUnit 10** — testes automatizados
- **Composer** — gerenciamento de dependências

---

## 📁 Estrutura do projeto

```
controle-financeiro/
├── public/
│   └── index.php           # Ponto de entrada da API
├── tests/
│   └── ApiTest.php         # Testes automatizados
├── composer.json
├── phpunit.xml
└── README.md
```

---

## ⚙️ Instalação

### Pré-requisitos

- PHP 8.1 ou superior
- Composer instalado
- Extensão `ext-json` habilitada

### Passos

1. Clone o repositório:

```bash
git clone https://github.com/<seu-usuario>/controle-financeiro.git
cd controle-financeiro
```

2. Instale as dependências:

```bash
composer install
```

---

## ▶️ Executando a API

O Slim pode ser servido diretamente pelo servidor embutido do PHP:

```bash
php -S localhost:8080 -t public
```

A API estará disponível em: **http://localhost:8080**

Verifique se está funcionando:

```bash
curl http://localhost:8080/status
# {"status":"ok"}
```

---

## 🧪 Executando os testes

Os testes cobrem **sucesso**, **erro de validação** e **recurso inexistente** para cada endpoint.

```bash
# roda todos os testes
./vendor/bin/phpunit

# roda com detalhes
./vendor/bin/phpunit --testdox

# roda um grupo específico
./vendor/bin/phpunit --filter Categoria
```

---

## 🔌 Endpoints

### 🏷️ Categorias

| Método | Rota | Descrição | RF |
|---|---|---|---|
| `POST` | `/categorias` | Cadastrar categoria | RF01 |
| `GET` | `/categorias` | Listar todas as categorias | RF02 |
| `GET` | `/categorias/{id}` | Consultar categoria por ID | RF03 |
| `PUT` | `/categorias/{id}` | Atualizar categoria | RF04 |
| `DELETE` | `/categorias/{id}` | Excluir categoria | RF05 |

### 💸 Lançamentos

| Método | Rota | Descrição | RF |
|---|---|---|---|
| `POST` | `/lancamentos` | Cadastrar lançamento | RF06 |
| `GET` | `/lancamentos` | Listar lançamentos (com filtros opcionais) | RF07 |
| `GET` | `/lancamentos/{id}` | Consultar lançamento por ID | RF08 |
| `PUT` | `/lancamentos/{id}` | Atualizar lançamento | RF09 |
| `DELETE` | `/lancamentos/{id}` | Excluir lançamento | RF10 |

**Filtros disponíveis** em `GET /lancamentos`:

| Query param | Descrição | RF |
|---|---|---|
| `data_inicial` | Data mínima (YYYY-MM-DD) | RF11 |
| `data_final` | Data máxima (YYYY-MM-DD) | RF11 |
| `categoria_id` | Filtrar por categoria | RF12 |
| `tipo` | `receita` ou `despesa` | RF13 |

### 📊 Resumo financeiro

| Método | Rota | Descrição | RF |
|---|---|---|---|
| `GET` | `/resumo/saldo` | Saldo atual (receitas − despesas) | RF14 |
| `GET` | `/resumo/periodo` | Totais por período | RF15 |

### ❤️ Healthcheck

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/status` | Verifica se a API está online |

---

## 📐 Regras de negócio

| Código | Regra |
|---|---|
| **RN01** | O valor de um lançamento deve ser maior que zero |
| **RN02** | O tipo do lançamento deve ser compatível com o tipo da categoria |
| **RN03** | Não podem existir duas categorias com o mesmo nome |
| **RN04** | Não é permitido cadastrar lançamento com categoria inexistente |
| **RN05** | A data do lançamento é obrigatória e deve ser válida |

---

## 📌 Códigos de status HTTP

| Código | Significado | Quando ocorre |
|---|---|---|
| **200** | OK | Consulta/atualização bem-sucedida |
| **201** | Created | Recurso criado com sucesso |
| **204** | No Content | Exclusão bem-sucedida |
| **404** | Not Found | Recurso não encontrado |
| **422** | Unprocessable Entity | Validação falhou / regra de negócio violada |

---

## 🧩 Exemplos de uso

### 1. Cadastrar categoria

```bash
curl -X POST http://localhost:8080/categorias \
  -H "Content-Type: application/json" \
  -d '{"nome": "Salário", "tipo": "receita"}'
```

**Resposta (201):**
```json
{
  "id": 3,
  "nome": "Salário",
  "tipo": "receita"
}
```

---

### 2. Listar categorias

```bash
curl http://localhost:8080/categorias
```

**Resposta (200):**
```json
[
  { "id": 1, "nome": "Salário", "tipo": "receita" },
  { "id": 2, "nome": "Alimentação", "tipo": "despesa" }
]
```

---

### 3. Criar lançamento

```bash
curl -X POST http://localhost:8080/lancamentos \
  -H "Content-Type: application/json" \
  -d '{
    "descricao": "Salário de maio",
    "valor": 5000.00,
    "data": "2025-05-01",
    "tipo": "receita",
    "categoria_id": 1
  }'
```

**Resposta (201):**
```json
{
  "id": 3,
  "descricao": "Salário de maio",
  "valor": 5000,
  "data": "2025-05-01",
  "tipo": "receita",
  "categoria_id": 1
}
```

---

### 4. Filtrar lançamentos por período e tipo

```bash
curl "http://localhost:8080/lancamentos?data_inicial=2025-05-01&data_final=2025-05-31&tipo=despesa"
```

---

### 5. Consultar saldo atual

```bash
curl http://localhost:8080/resumo/saldo
```

**Resposta (200):**
```json
{
  "total_receitas": 5000,
  "total_despesas": 350,
  "saldo": 4650
}
```

---

### 6. Consultar totais por período

```bash
curl "http://localhost:8080/resumo/periodo?data_inicial=2025-05-01&data_final=2025-05-31"
```

**Resposta (200):**
```json
{
  "periodo": { "inicio": "2025-05-01", "fim": "2025-05-31" },
  "total_receitas": 5000,
  "total_despesas": 350
}
```

---

### 7. Erro de validação (RN02 — tipo incompatível)

```bash
curl -X POST http://localhost:8080/lancamentos \
  -H "Content-Type: application/json" \
  -d '{
    "descricao": "Mercado",
    "valor": 100,
    "data": "2025-05-10",
    "tipo": "receita",
    "categoria_id": 2
  }'
```

**Resposta (422):**
```json
{
  "erros": ["tipo do lançamento incompatível com a categoria"]
}
```

---

### 8. Excluir categoria com lançamentos vinculados (RF05)

```bash
curl -X DELETE http://localhost:8080/categorias/1
```

**Resposta (422):**
```json
{
  "erro": "Categoria possui lançamentos vinculados"
}
```

---

## 📝 Observações

- Os dados são mantidos **em memória** (arrays associativos). Ao reiniciar a API, o estado volta ao inicial — comportamento aceitável para fins didáticos.
- Todos os corpos de requisição e resposta são **JSON**.
- A API segue o padrão **REST** (métodos HTTP adequados, sem verbos na URL).

---

## 👤 Autor

Avaliativa individual da disciplina de Web Services.

**Nome:** Hadassa Maria Souza
**GitHub:** [@hadassasouza](https://github.com/souzahadassa/)