# Audits Microservice - Contexto do Projeto

## Objetivo

Microsserviço Laravel 12 headless para geração de relatórios de performance (Lighthouse/PageSpeed) em PDF. O serviço recebe payloads JSON do Google PageSpeed Insights via API, processa de forma assíncrona, gera PDFs whitelabel e notifica via webhook.

## Arquitetura

- **Core:** Laravel 12 API (slim)
- **PDF Engine:** Spatie Browsershot (Puppeteer/Chromium)
- **Queue:** Redis
- **Storage:** Local Public Disk / S3
- **Auth:** Laravel Sanctum (tokens)

## Princípios de Qualidade

1. **Tipagem Estrita:** `declare(strict_types=1)` em todos os arquivos
2. **Análise Estática:** PHPStan nível 8 obrigatório
3. **DTOs:** Spatie Laravel Data (sem arrays soltos)
4. **Value Objects:** Url, AuditScore, MetricValue
5. **Testes:** Pest PHP com mocks para Browsershot

## Fluxo Principal

```
POST /api/v1/scan (JSON do PageSpeed)
    → Valida e converte para AuditData (DTO)
    → Despacha GenerateAuditPdfJob
    → Job: Renderiza Blade → PDF (Browsershot)
    → Dispara Webhook com URL do PDF
```

## Fases de Implementação

| Fase | Descrição | Status |
|------|-----------|--------|
| 1 | Fundação e Dependências | ✅ Completa |
| 2 | Domínio e Modelagem | ✅ Completa |
| 3 | Camada de Aplicação | ✅ Completa |
| 4 | Interface Pública (API) | ✅ Completa |
| 5 | Console Commands | ✅ Completa |
| 6 | Testes Automatizados | ✅ Completa |

## Arquivos de Referência

- **README.md:** Regras de negócio e especificações de API
- **tests/Fixtures/pagespeed_mock.json:** Payload real do Google PageSpeed (3566 linhas)

## Mapeamento de Métricas (JSON → DTO)

| Campo | Path no JSON | Exemplo |
|-------|--------------|---------|
| Score | `lighthouseResult.categories.performance.score` | `1` (100%) |
| LCP | `lighthouseResult.audits.largest-contentful-paint.displayValue` | `"0.6 s"` |
| FCP | `lighthouseResult.audits.first-contentful-paint.displayValue` | `"0.5 s"` |
| CLS | `lighthouseResult.audits.cumulative-layout-shift.displayValue` | `"0.001"` |
| URL | `lighthouseResult.finalDisplayedUrl` | `"https://..."` |
