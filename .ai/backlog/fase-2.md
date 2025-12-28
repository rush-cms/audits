# Fase 2: Domínio e Modelagem

## Objetivo
Criar a estrutura de domínio com Value Objects tipados e DTOs usando Spatie Laravel Data para parsing do payload PageSpeed.

## Tarefas

- [ ] Criar diretório `app/Domain/ValueObjects/`
- [ ] Criar Value Object `Url`:
  - Validação via `filter_var(FILTER_VALIDATE_URL)`
  - Método `__toString()`
- [ ] Criar Value Object `AuditScore`:
  - Score como float (0.0–1.0)
  - `toPercentage(): int`
  - `getColor(): string` (green/orange/red)
  - `isPassing(): bool` (≥0.9)
- [ ] Criar Value Object `MetricValue`:
  - Parse de `"2.1 s"`, `"0.5 s"`, `"0.001"`
  - `toMilliseconds(): float`
  - `format(): string`
- [ ] Criar diretório `app/Data/`
- [ ] Criar DTO `AuditData`:
  - Método estático `fromPageSpeedPayload(array $payload)`
  - Tratamento de array wrapper do n8n
- [ ] Criar DTO `WebhookPayloadData`
- [ ] **Teste crítico:** Unit test parseando `pagespeed_mock.json`
- [ ] **Commit:** `feat: add value objects and audit data dto`

## Dependências

- Fase 1 concluída (spatie/laravel-data instalado)

## Critério de Aceite

- `AuditData::fromPageSpeedPayload()` parseia `pagespeed_mock.json` corretamente
- Teste unitário passa extraindo Score=100, LCP="0.6 s", FCP="0.5 s", CLS="0.001"
- PHPStan nível 8 passa sem erros
