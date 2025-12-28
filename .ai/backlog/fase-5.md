# Fase 5: Console Commands

## Objetivo
Criar comandos Artisan para gerenciamento do serviço headless.

## Tarefas

- [ ] Criar `app/Console/Commands/CreateTokenCommand`:
  - Assinatura: `audit:create-token {name}`
  - Cria token Sanctum para cliente
  - Exibe token gerado
- [ ] Criar `app/Console/Commands/CheckBrowserCommand`:
  - Assinatura: `audit:check-browser`
  - Tenta executar Browsershot simples
  - Reporta sucesso/falha com paths
- [ ] Criar `app/Console/Commands/PrunePdfsCommand`:
  - Assinatura: `audit:prune-pdfs`
  - Remove PDFs mais antigos que `config('audits.pdf.retention_days')`
  - Exibe contagem de arquivos removidos
- [ ] Registrar agendamento em `bootstrap/app.php`:
  - `audit:prune-pdfs` → daily
- [ ] **Commit:** `feat: add console commands for token and maintenance`

## Dependências

- Fase 4 concluída (Sanctum configurado)

## Critério de Aceite

- `php artisan audit:create-token "Test"` gera token válido
- `php artisan audit:check-browser` reporta status do Chrome
- `php artisan audit:prune-pdfs` executa sem erros
- PHPStan nível 8 passa
