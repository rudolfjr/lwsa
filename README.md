# Stock Sales API

API REST para gerenciamento de estoque e vendas de ERP, desenvolvida em Laravel 12.

## Requisitos

- Docker e Docker Compose
- Git

## Instalacao

### 1. Clone o repositorio

```bash
git clone <repository-url>
cd stock-sales-api
```

### 2. Configure o ambiente

```bash
cp .env.example .env
```

### 3. Suba os containers

```bash
docker-compose up -d --build
```

### 4. Instale as dependencias

```bash
docker-compose exec app composer install
```

### 5. Gere a chave da aplicacao

```bash
docker-compose exec app php artisan key:generate
```

### 6. Execute as migrations

```bash
docker-compose exec app php artisan migrate
```

### 7. Execute os seeders (dados de teste)

```bash
docker-compose exec app php artisan db:seed
```

### 8. Acesse a aplicacao

A API estara disponivel em: `http://localhost:8000`

## Executando Testes

```bash
docker-compose exec app php artisan test
```

## Estrutura do Projeto

```
app/
├── Console/Commands/          # Comandos Artisan (scheduler)
├── Events/                    # Eventos do sistema
├── Http/
│   ├── Controllers/Api/V1/    # Controllers da API v1
│   └── Requests/              # Form Requests (validacao)
├── Jobs/                      # Jobs para filas
├── Listeners/                 # Listeners de eventos
├── Models/                    # Eloquent Models
├── Providers/                 # Service Providers
├── Repositories/
│   ├── Contracts/             # Interfaces dos repositorios
│   └── Eloquent/              # Implementacoes Eloquent
└── Services/                  # Camada de servicos
```

## Decisoes Arquiteturais

### Banco de Dados: PostgreSQL

**Justificativa:**
- Melhor suporte a transacoes e locks (SELECT FOR UPDATE)
- Performance superior em queries complexas
- Suporte nativo a JSON para logs de auditoria
- Indices parciais para otimizacao

### Arquitetura de Camadas

```
Controller -> Service -> Repository -> Model
```

**Repository Pattern:**
- Abstrai a camada de persistencia
- Facilita testes unitarios com mocks
- Permite trocar implementacao (ex: Eloquent para Doctrine)

**Service Layer:**
- Concentra regras de negocio
- Orquestra operacoes complexas
- Gerencia transacoes

### Modelagem do Banco

```
┌─────────────┐     ┌─────────────────────┐
│  products   │────<│ inventory_movements │
├─────────────┤     ├─────────────────────┤
│ id          │     │ id                  │
│ sku (unique)│     │ product_id (FK)     │
│ name        │     │ type (entry/exit)   │
│ cost_price  │     │ quantity            │
│ sale_price  │     │ unit_cost           │
│ is_active   │     │ reference_type      │
└──────┬──────┘     │ reference_id        │
       │            │ user_id (FK)        │
       │            └─────────────────────┘
       │
┌──────┴──────┐
│  inventory  │
├─────────────┤
│ id          │
│ product_id  │
│ quantity    │
│ total_cost  │
│ total_sale  │
│ profit      │
└─────────────┘

┌─────────────┐     ┌─────────────┐
│   sales     │────<│ sale_items  │
├─────────────┤     ├─────────────┤
│ id          │     │ id          │
│ code        │     │ sale_id(FK) │
│ status      │     │ product_id  │
│ total_amount│     │ quantity    │
│ total_cost  │     │ unit_price  │
│ profit      │     │ subtotal    │
│ user_id(FK) │     │ profit      │
└─────────────┘     └─────────────┘
```

### Indices Criados

| Tabela | Indice | Justificativa |
|--------|--------|---------------|
| products | sku (unique) | Busca rapida por SKU |
| products | name | Busca por nome |
| inventory | product_id (unique) | Relacao 1:1 otimizada |
| inventory | last_movement_at | Query de estoque stale |
| sales | status, created_at | Filtros de relatorio |
| sales | created_at, status | Range queries |
| sale_items | sale_id, product_id | Join otimizado |

### Estrategias de Otimizacao

**Cache (Redis):**
- Listagem de estoque: 5 minutos TTL
- Relatorios: 5 minutos TTL com chave baseada em parametros
- Invalidacao automatica apos alteracoes

**Filas (Redis):**
- Processamento assincrono de vendas
- Retentativas automaticas (3x)
- Backoff de 10 segundos

**Concorrencia:**
- Lock pessimista (SELECT FOR UPDATE) no estoque
- Transacoes para garantir atomicidade
- Validacao pre-processamento

### Padroes de Design

| Padrao | Uso |
|--------|-----|
| Repository | Abstrai persistencia |
| Service Layer | Regras de negocio |
| Observer | Eventos de venda |
| Job/Queue | Processamento assincrono |

## Endpoints da API

### Autenticacao

```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'

# Logout
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {token}"

# Perfil
curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer {token}"
```

### Estoque

```bash
# Adicionar estoque
curl -X POST http://localhost:8000/api/v1/inventory \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "quantity": 100}'

# Listar estoque
curl -X GET http://localhost:8000/api/v1/inventory \
  -H "Authorization: Bearer {token}"
```

### Vendas

```bash
# Criar venda
curl -X POST http://localhost:8000/api/v1/sales \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"product_id": 1, "quantity": 5},
      {"product_id": 2, "quantity": 3}
    ]
  }'

# Consultar venda
curl -X GET http://localhost:8000/api/v1/sales/1 \
  -H "Authorization: Bearer {token}"
```

### Relatorios

```bash
# Relatorio de vendas
curl -X GET "http://localhost:8000/api/v1/reports/sales?start_date=2024-01-01&end_date=2024-12-31" \
  -H "Authorization: Bearer {token}"

# Com filtro por SKU
curl -X GET "http://localhost:8000/api/v1/reports/sales?start_date=2024-01-01&end_date=2024-12-31&sku=PRD-0001-AB" \
  -H "Authorization: Bearer {token}"
```

## Tarefa Agendada

O comando `inventory:archive-stale` roda diariamente as 02:00 para desativar produtos sem movimentacao em 90+ dias.

```bash
# Executar manualmente
docker-compose exec app php artisan inventory:archive-stale --days=90
```

## Usuario de Teste

Apos rodar os seeders:
- Email: `admin@example.com`
- Senha: `password`

## Melhorias Futuras

### Escalabilidade (10k -> 100k vendas)
- Particionar tabela de vendas por mes/ano
- Implementar read replicas para relatorios
- Usar Elasticsearch para buscas complexas
- Cache distribuido com Redis Cluster

### Seguranca
- Implementar rate limiting mais granular
- Adicionar 2FA para usuarios
- Criptografar dados sensiveis
- Implementar CORS adequado
- Audit log completo de todas operacoes

### Performance
- Implementar paginacao cursor-based
- Adicionar indices GIN para busca full-text
- Usar materialized views para relatorios
- Implementar cache warming

### Para 1M de vendas
- Sharding horizontal por tenant/regiao
- Separar OLTP de OLAP (data warehouse)
- Implementar event sourcing
- Usar time-series database para metricas
