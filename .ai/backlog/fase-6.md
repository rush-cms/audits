# Fase 6: Testes Automatizados

## Objetivo
Garantir cobertura de testes para todos os fluxos críticos do microsserviço.

## Tarefas

- [ ] Criar `tests/Unit/AuditDataTest.php`:
  - Teste crítico: Parse de `pagespeed_mock.json`
  - Valida extração de Score, LCP, FCP, CLS, URL
- [ ] Criar `tests/Unit/ValueObjectsTest.php`:
  - Testes para `Url` (válida/inválida)
  - Testes para `AuditScore` (cores, percentage, isPassing)
  - Testes para `MetricValue` (parse e format)
- [ ] Criar `tests/Unit/PdfGeneratorServiceTest.php`:
  - Mock do Browsershot
  - Valida que HTML contém dados corretos
- [ ] Criar `tests/Feature/AuditApiTest.php`:
  - Teste com Queue::fake()
  - Valida resposta 202 e dispatch do Job
  - Testa autenticação (401 sem token)
- [ ] Criar `tests/Feature/WebhookTest.php`:
  - Mock de HTTP client
  - Valida payload enviado ao webhook
- [ ] Executar suite completa: `php artisan test`
- [ ] Executar PHPStan: `vendor/bin/phpstan analyse`
- [ ] Executar Pint: `vendor/bin/pint --dirty`
- [ ] **Commit:** `test: add full test coverage for audit service`

## Dependências

- Todas as fases anteriores concluídas

## Critério de Aceite

- Todos os testes passam
- PHPStan nível 8 passa
- Pint não reporta problemas
- Cobertura dos fluxos críticos documentada
