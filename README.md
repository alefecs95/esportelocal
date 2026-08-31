# Sistema de Gestão Esportiva Municipal - Módulo de Atletas

Este projeto é composto por um Backend em Laravel e um Frontend em Vue 3.

## Pré-requisitos

- PHP 8.1+
- Composer
- Node.js & NPM
- MySQL (Laragon recomendado)

## Configuração do Backend

1. Navegue até a pasta `backend`:
   ```bash
   cd backend
   ```

2. Instale as dependências do PHP:
   ```bash
   composer install
   ```

3. Configure o arquivo `.env`:
   - O arquivo `.env` já foi configurado para conectar ao banco de dados `semel` no MySQL (usuário `root`, sem senha).
   - Verifique se as credenciais do banco estão corretas para o seu ambiente Laragon.
   - Adicione o Token da API `CadastroReceitaPessoaFisica` na chave `DIRECTD_API_TOKEN`.

4. Gere a chave da aplicação:
   ```bash
   php artisan key:generate
   ```

5. Rode as migrações do banco de dados:
   ```bash
   php artisan migrate
   ```

6. Inicie o servidor de desenvolvimento (ou use o Laragon):
   ```bash
   php artisan serve
   ```
   O backend rodará em `http://localhost:8000`.

## Configuração do Frontend

1. Navegue até a pasta `frontend`:
   ```bash
   cd frontend
   ```

2. Instale as dependências do Node:
   ```bash
   npm install
   ```

3. Configure o arquivo `.env`:
   - O arquivo `.env` foi criado com `VITE_API_URL=http://localhost:8000/api/v1/`.
   - Se o seu backend estiver rodando em outra URL (ex: via Laragon Virtual Host), altere esta variável.

4. Inicie o servidor de desenvolvimento:
   ```bash
   npm run dev
   ```
   O frontend rodará em `http://localhost:5173`.

## Funcionalidades

- **CRUD de Atletas**: Listagem, Cadastro, Edição e Exclusão.
- **Integração com API Externa**: Consulta de CPF na Receita Federal para preenchimento automático.
- **Validação**: Frontend e Backend com validação de campos obrigatórios e formato de CPF.
