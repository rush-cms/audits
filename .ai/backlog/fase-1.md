# Fase 1: Fundação e Dependências

## Objetivo
Configurar a base do projeto com todas as dependências necessárias, análise estática PHPStan nível 8 e arquivo de configuração whitelabel.

## Tarefas

- [ ] Instalar pacotes Composer:
  - `spatie/laravel-data`
  - `spatie/browsershot`
  - `larastan/larastan`
  - `laravel/sanctum`
- [ ] Criar `phpstan.neon` com:
  - Nível 8
  - Includes do Larastan
  - Ignores para falsos positivos Laravel
- [ ] Criar `config/audits.php`:
  - `brand_name`
  - `logo_url`
  - `webhook.return_url`
  - `pdf.retention_days`
  - `browsershot.*`
- [ ] Atualizar `.env.example` com variáveis do README
- [ ] Executar `vendor/bin/phpstan analyse` para validar config
- [ ] **Commit:** `feat: add dependencies and phpstan config`

## Dependências

Nenhuma — esta é a fase inicial.

## Critério de Aceite

- `composer install` executa sem erros
- `vendor/bin/phpstan analyse` passa no nível 8 (projeto vazio)
- `config/audits.php` existe e retorna valores corretos
