# Fase 4: Interface Pública (API)

## Objetivo
Expor o endpoint REST para receber payloads do PageSpeed e configurar autenticação via Sanctum.

## Tarefas

- [ ] Publicar config do Sanctum
- [ ] Criar migration para `personal_access_tokens` (se não existir)
- [ ] Adicionar trait `HasApiTokens` ao model User
- [ ] Criar `app/Http/Controllers/Api/V1/AuditController`:
  - Método `store(Request $request)`
  - Trata array wrapper do n8n
  - Converte para `AuditData`
  - Despacha `GenerateAuditPdfJob`
  - Retorna HTTP 202 com `audit_id`
- [ ] Registrar rota em `routes/api.php`:
  - `POST /api/v1/scan`
  - Middleware `auth:sanctum`
- [ ] Feature test: Endpoint recebe payload e despacha job
- [ ] **Commit:** `feat: add api endpoint for scan submission`

## Dependências

- Fase 3 concluída (Job existente)

## Critério de Aceite

- `POST /api/v1/scan` retorna 202 com token válido
- Retorna 401 sem token
- Job é enfileirado corretamente
- PHPStan nível 8 passa
