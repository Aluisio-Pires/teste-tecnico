# API de Gerenciamento de Ordens de Viagem

<p>
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/Laravel-12.8-FF2D20.svg?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12.8">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8.0">
  <img src="https://img.shields.io/badge/Redis-Alpine-DC382D.svg?style=for-the-badge&logo=redis&logoColor=white" alt="Redis">
  <img src="https://img.shields.io/badge/JWT-Autenticação-000000.svg?style=for-the-badge&logo=json-web-tokens&logoColor=white" alt="Autenticação JWT">
</p>

## Visão Geral

Esta API fornece um sistema para o gerenciamento de Ordens de Viagem. Ela permite que usuários criem, visualizem, atualizem e cancelem solicitações de viagem com permissões baseadas em papéis e autenticação.

### Principais Funcionalidades

- **Autenticação JWT**
- **Permissões baseadas em papéis**
- **Gerenciamento de ordens de viagem**
- **Filtragem e paginação**
- **Sistema de notificações**
- **Suíte completa de testes**
- **Documentação API com Swagger**

---

## Pré-requisitos

- [Docker](https://www.docker.com/get-started)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Git](https://git-scm.com/downloads)

---

## Instalação

### Orientações Iniciais
- A documentação a seguir é baseada no sistema Linux.

Faça o clone do repositório
```bash
git clone https://github.com/Aluisio-Pires/teste-tecnico.git
```

Entre na pasta do projeto
```bash
cd teste-tecnico
```

Copie o arquivo `.env.example` para `.env`
```bash
cp .env.example .env
```

### Instale as dependências

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer install
```

### Crie um alias para o Sail

#### Se você usa o Bash

```bash
# Bash
echo "alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'" >> ~/.bashrc
source ~/.bashrc
```

#### Se você usa o Zsh
```bash
# Zsh
echo "alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'" >> ~/.zshrc
source ~/.zshrc
```

### Suba os containers com Sail

```bash
sail up -d
```

### Gere a chave da aplicação

```bash
sail artisan key:generate
```

### Gere o segredo do JWT

```bash
sail artisan jwt:secret
```

### Rode as migrações e seeds (opcional)

#### Migrações

```bash
sail artisan migrate
```
#### Seeders (opcional)

```bash
sail artisan db:seed
```
#### Rodando os dois juntos

```bash
sail artisan migrate --seed 
```

### Coloque a fila para trabalhar

```bash
sail artisan queue:work
```

---

## Testes

### Rodar todos os testes (Gera um relatório de cobertura dentro da pasta `coverage`)

```bash
sail artisan test
```

### Com relatório de cobertura

```bash
sail artisan test --coverage
```

### Testes por suíte

#### Apenas os testes de funcionalidade
```bash
sail artisan test --testsuite=Feature
```

#### Apenas os testes unitários
```bash
sail artisan test --testsuite=Unit
```

---

## Qualidade de Código

### Laravel Pint

```bash
sail pint
```

ou

```bash
./vendor/bin/pint
```
ou 

```bash
sail artisan pint
```

### Rector

```bash
./vendor/bin/rector
```

ou

```bash
sail artisan rector
```

### PHPStan

```bash
./vendor/bin/phpstan analyse
```

ou

```bash
sail artisan phpstan
```

### Para usar os três comandos juntos, em sequência, utilize o comando abaixo:

```bash
sail artisan analyse
```

---

## Variáveis de Ambiente

| Variável | Descrição | Padrão |
|----------|-----------|--------|
| `APP_PORT` | Porta da aplicação Laravel | 80 |
| `VITE_PORT` | Porta do Vite | 5173 |
| `DB_CONNECTION` | Driver do banco | mysql |
| `DB_HOST` | Host do banco | mysql |
| `DB_PORT` | Porta do banco | 3306 |
| `DB_DATABASE` | Nome do banco | teste_tecnico |
| `DB_USERNAME` | Usuário | root |
| `DB_PASSWORD` | Senha | |
| `FORWARD_DB_PORT` | Redirecionamento do MySQL | 3306 |
| `FORWARD_REDIS_PORT` | Redirecionamento do Redis | 6379 |
| `JWT_SECRET` | Chave do JWT | |
| `JWT_TTL` | Tempo de vida do token (minutos) | |

---

## Autenticação

Autenticação via JWT. Para acessar endpoints protegidos:

1. Registre-se ou faça login.
2. Utilize no header o token retornado no login:

```http
Authorization: Bearer {token}
```

---

## Documentação da API

1. Inicie a aplicação
2. Acesse: `{URL}/api/documentation`

---

## Endpoints Disponíveis

| Método | Endpoint | Descrição | Permissão                        |
|--------|----------|-----------|----------------------------------|
| POST | `/api/auth/register` | Registro | Nenhuma                          |
| POST | `/api/auth/login` | Login | Nenhuma                          |
| GET | `/api/auth/me` | Dados do usuário autenticado | Autenticado                      |
| POST | `/api/auth/logout` | Logout | Autenticado                      |
| POST | `/api/auth/refresh` | Refresh token | Autenticado                      |
| GET | `/api/orders` | Listar ordens | Autenticado                      |
| POST | `/api/orders` | Criar ordem | Autenticado                      |
| GET | `/api/orders/{id}` | Visualizar ordem | Autenticado + Dono ou permissão `view-order` |
| PUT | `/api/orders/{id}` | Atualizar status | Permissão `update-order`         |
| POST | `/api/orders/{id}/cancel` | Cancelar | Permissão `delete-order` ou dono |

---

## Deploy em Produção

1. Defina as variáveis de ambiente no `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

2. Otimizar o projeto realizando cache de arquivos:

```bash
sail artisan optimize
```

---

## Parar o ambiente

```bash
# Parar containers
sail down

# Parar e remover volumes
sail down -v
```


## Extras

### Nesse projeto, foram utilizadas duas bibliotecas autorais:

Laravel Code Analyzer (simplifica o processo de análise de código com um único comando)
- [Laravel Code Analyzer](https://github.com/Aluisio-Pires/laravel-code-analyzer)

Laravel Swagger Docs (cria documentação da API com Swagger descobrindo automaticamente os arquivos que estão no padrão do Laravel)
- [Laravel Swagger Docs](https://github.com/Aluisio-Pires/laravel-swagger-docs)

### Marcos alcançados:
- 100% de cobertura de código
- PHPStan nível máximo (10)
