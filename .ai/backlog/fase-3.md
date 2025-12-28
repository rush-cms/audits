# Fase 3: Camada de Aplicação

## Objetivo
Implementar os services, jobs e views responsáveis pelo processamento e geração do PDF.

## Tarefas

- [ ] Criar `app/Services/PdfGeneratorService`:
  - Recebe `AuditData`
  - Renderiza view Blade
  - Usa Browsershot para gerar PDF
  - Retorna path do arquivo
- [ ] Criar `app/Services/WebhookDispatcherService`:
  - Recebe `AuditData` e URL do PDF
  - Dispara POST para `config('audits.webhook.return_url')`
  - Monta payload conforme README
- [ ] Criar `app/Jobs/GenerateAuditPdfJob`:
  - Implements `ShouldQueue`
  - Recebe `AuditData` via construtor
  - Chama `PdfGeneratorService` e `WebhookDispatcherService`
- [ ] Criar `resources/views/reports/audit.blade.php`:
  - Header com logo e brand name
  - Score gauge (SVG inline ou QuickChart)
  - Tabela de métricas com cores
  - Footer com data e URL auditada
  - Tailwind CSS via CDN
- [ ] Criar diretório `storage/app/public/reports/`
- [ ] **Commit:** `feat: add pdf generation job and services`

## Dependências

- Fase 2 concluída (DTOs funcionando)

## Critério de Aceite

- View Blade renderiza corretamente com dados mockados
- `PdfGeneratorService` gera PDF válido (teste manual local)
- PHPStan nível 8 passa
